<?php
session_start();
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure only wardens can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'warden') {
    header("Location: login.php");
    exit();
}

$warden_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$email = $_SESSION['email'];

// Fetch all teacher-approved outpass requests
$requests_query = "SELECT o.id, u.name AS student_name, u.email AS student_email, u.department, 
                   o.reason, o.leave_date, o.leave_time, o.return_date, o.return_time, o.status, o.teacher_comment 
                   FROM outpass_requests o 
                   JOIN users u ON o.student_id = u.id 
                   WHERE o.status = 'teacher_approved'";
$stmt = $conn->prepare($requests_query);
$stmt->execute();
$requests_result = $stmt->get_result();

// Handle Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $warden_comment = trim($_POST['warden_comment'] ?? '');

    if ($action == 'approve') {
        $update_sql = "UPDATE outpass_requests SET status = 'warden_approved', warden_comment = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $warden_comment, $request_id);
        $stmt->execute();

        // Store gate pass in student's profile
        $insert_gatepass = "INSERT INTO gate_approvals (outpass_id, student_id, status) 
                            SELECT id, student_id, 'pending' FROM outpass_requests WHERE id = ?";
        $stmt = $conn->prepare($insert_gatepass);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();

        $success_message = "Outpass approved and sent to the gate!";
    } else {
        if (empty($warden_comment)) {
            $error_message = "Rejection requires a comment!";
        } else {
            $update_sql = "UPDATE outpass_requests SET status = 'rejected', warden_comment = ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $warden_comment, $request_id);
            $stmt->execute();

            $success_message = "Outpass request has been rejected!";
        }
    }

    header("Location: warden.php");
    exit();
}

// Fetch all processed outpass requests for history
$history_query = "SELECT o.id, u.name AS student_name, u.department, 
                  o.reason, o.leave_date, o.leave_time, o.return_date, o.return_time, o.status, o.teacher_comment, o.warden_comment
                  FROM outpass_requests o 
                  JOIN users u ON o.student_id = u.id 
                  WHERE o.status IN ('warden_approved', 'rejected', 'invalid')";
$history_stmt = $conn->prepare($history_query);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Panel | Approve Outpass</title>
    <link rel="stylesheet" href="css/warden.css">
    
    <!-- EmailJS Integration -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    <script type="text/javascript">
        // Initialize EmailJS
        (function() {
            emailjs.init("BylsfVs_890Bfmuu3");
        })();
        
        function sendStatusEmail(studentName, studentEmail, status, reason, leaveDate, returnDate, wardenComment) {
            const templateParams = {
                to_email: studentEmail,
                student_name: studentName,
                status: status === 'approve' ? 'Approved' : 'Rejected',
                reason: reason,
                leave_date: leaveDate,
                return_date: returnDate,
                warden_comment: wardenComment,
                approved: status === 'approve'
            };

            document.getElementById('loadingOverlay').style.display = 'flex';

            return emailjs.send('service_3zsxckq', 'template_ph3ytu8', templateParams)
                .then(function(response) {
                    console.log('SUCCESS!', response.status, response.text);
                    document.getElementById('loadingOverlay').style.display = 'none';
                    return true;
                })
                .catch(function(error) {
                    console.log('FAILED...', error);
                    document.getElementById('loadingOverlay').style.display = 'none';
                    alert('Failed to send email notification. The status has been updated in the system.');
                    return true;
                });
        }

        async function handleFormSubmit(form) {
            if (form.action.value === "reject" && form.warden_comment.value.trim() === "") {
                alert("Rejection requires a comment!");
                return false;
            }

            const studentName = form.getAttribute('data-student-name');
            const studentEmail = '23bai007@kprcas.ac.in';
            const reason = form.getAttribute('data-reason');
            const leaveDate = form.getAttribute('data-leave-date');
            const returnDate = form.getAttribute('data-return-date');

            try {
                await sendStatusEmail(
                    studentName,
                    studentEmail,
                    form.action.value,
                    reason,
                    leaveDate,
                    returnDate,
                    form.warden_comment.value
                );
                return true;
            } catch (error) {
                console.error('Error sending email:', error);
                return true; // Still submit the form even if email fails
            }
        }

        function searchHistory() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll("#historyTable tbody tr");

            rows.forEach(row => {
                let department = row.cells[1].innerText.toLowerCase();
                row.style.display = department.includes(input) ? "" : "none";
            });
        }
    </script>

    <style>
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="loading-content">
            <p>Sending email notification...</p>
        </div>
    </div>

    <div class="profile-container">
        <img src="profile.png" alt="Profile Icon" class="profile-icon">
        <div class="profile-info">
            <p><strong><?= htmlspecialchars($name) ?></strong></p>
            <p>Role: Warden</p>
            <p>Email: <?= htmlspecialchars($email) ?></p>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Pending Outpass Requests</h2>

        <?php if (isset($error_message)) : ?>
            <p class="error"><?= htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <table>
            <tr>
                <th>Student Name</th>
                <th>Department</th>
                <th>Reason</th>
                <th>Leave Date</th>
                <th>Leave Time</th>
                <th>Return Date</th>
                <th>Return Time</th>
                <th>Teacher Comment</th>
                <th>Warden Comment & Action</th>
            </tr>
            <?php while ($row = $requests_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_name']); ?></td>
                    <td><?= htmlspecialchars($row['department']); ?></td>
                    <td><?= htmlspecialchars($row['reason']); ?></td>
                    <td><?= htmlspecialchars($row['leave_date']); ?></td>
                    <td><?= (!empty($row['leave_time'])) ? date("h:i A", strtotime($row['leave_time'])) : "<span style='color:gray;'>Not Scanned</span>"; ?></td>
                    <td><?= htmlspecialchars($row['return_date']); ?></td>
                    <td><?= (!empty($row['return_time'])) ? date("h:i A", strtotime($row['return_time'])) : "<span style='color:gray;'>Not Scanned</span>"; ?></td>
                    <td><?= htmlspecialchars($row['teacher_comment'] ?? 'No Comment'); ?></td>
                    <td>
                        <form action="" method="post" 
                              onsubmit="return handleFormSubmit(this)"
                              data-student-name="<?= htmlspecialchars($row['student_name']); ?>"
                              data-student-email="<?= htmlspecialchars($row['student_email']); ?>"
                              data-reason="<?= htmlspecialchars($row['reason']); ?>"
                              data-leave-date="<?= htmlspecialchars($row['leave_date']); ?>"
                              data-return-date="<?= htmlspecialchars($row['return_date']); ?>">
                            <input type="hidden" name="request_id" value="<?= $row['id']; ?>">
                            <textarea name="warden_comment" placeholder="Required for rejection"></textarea>
                            <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                            <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- History Section -->
    <div class="container">
        <h2>Outpass History</h2>
        <input type="text" id="searchInput" onkeyup="searchHistory()" placeholder="Search by Department...">

        <table id="historyTable">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Department</th>
                    <th>Reason</th>
                    <th>Leave Date</th>
                    <th>Leave Time</th>
                    <th>Return Date</th>
                    <th>Return Time</th>
                    <th>Teacher Comment</th>
                    <th>Warden Comment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $history_result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_name']); ?></td>
                        <td><?= htmlspecialchars($row['department']); ?></td>
                        <td><?= htmlspecialchars($row['reason']); ?></td>
                        <td><?= htmlspecialchars($row['leave_date']); ?></td>
                        <td><?= (!empty($row['leave_time'])) ? date("h:i A", strtotime($row['leave_time'])) : "<span style='color:gray;'>Not Scanned</span>"; ?></td>
                        <td><?= htmlspecialchars($row['return_date']); ?></td>
                        <td><?= (!empty($row['return_time'])) ? date("h:i A", strtotime($row['return_time'])) : "<span style='color:gray;'>Not Scanned</span>"; ?></td>
                        <td><?= htmlspecialchars($row['teacher_comment'] ?? 'No Comment'); ?></td>
                        <td><?= htmlspecialchars($row['warden_comment'] ?? 'No Comment'); ?></td>
                        <td><?= ucfirst(htmlspecialchars($row['status'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>