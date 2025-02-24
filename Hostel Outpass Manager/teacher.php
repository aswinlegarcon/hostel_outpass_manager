<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$department = $_SESSION['department'];
$email = $_SESSION['email'];

// Fetch all pending outpass requests
$requests_query = "SELECT o.id, u.name AS student_name, u.department, o.reason, 
                   o.leave_date, o.leave_time, o.return_date, o.return_time, o.status 
                   FROM outpass_requests o 
                   JOIN users u ON o.student_id = u.id 
                   WHERE o.teacher_id = ? AND o.status = 'pending'";
$stmt = $conn->prepare($requests_query);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$requests_result = $stmt->get_result();

// Fetch Outpass History (Approved/Rejected by Teacher)
$history_query = "SELECT o.id, u.name AS student_name, u.department, o.reason, 
                         o.leave_date, o.leave_time, o.return_date, o.return_time, 
                         o.status, o.teacher_comment
                  FROM outpass_requests o 
                  JOIN users u ON o.student_id = u.id 
                  WHERE o.teacher_id = ? AND o.status != 'pending'
                  ORDER BY o.leave_date DESC";
$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("i", $teacher_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; 
    $comment = $_POST['comment'] ?? null;

    if ($action == 'approve') {
        $update_sql = "UPDATE outpass_requests SET status = 'teacher_approved', teacher_comment = ? WHERE id = ?";
    } else {
        $update_sql = "UPDATE outpass_requests SET status = 'rejected', teacher_comment = ? WHERE id = ?";
    }

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $comment, $request_id);

    if ($stmt->execute()) {
        $success_message = "Outpass request has been " . ($action == 'approve' ? "approved" : "rejected") . "!";
    } else {
        $error_message = "Error updating request: " . $stmt->error;
    }

    header("Location: teacher.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Panel | Approve Outpass</title>
    <link rel="stylesheet" href="css/teacher.css">
    <script>
        function filterHistory() {
            let searchInput = document.getElementById("searchInput").value.toLowerCase();
            let statusFilter = document.getElementById("statusFilter").value.toLowerCase();
            let tableRows = document.querySelectorAll("#historyTable tbody tr");

            tableRows.forEach(row => {
                let studentName = row.children[0].innerText.toLowerCase();
                let status = row.children[7].innerText.toLowerCase();

                if ((studentName.includes(searchInput) || searchInput === "") &&
                    (status.includes(statusFilter) || statusFilter === "all")) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</head>
<body>

    <div class="profile-container">
        <img src="profile.png" alt="Profile Icon" class="profile-icon">
        <div class="profile-info">
            <p><strong><?= $name ?></strong></p>
            <p>Department: <?= $department ?></p>
            <p>Email: <?= $email ?></p>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="container">
        <h2>Pending Outpass Requests</h2>

        <?php if (isset($success_message)) : ?>
            <p class="success"><?= $success_message; ?></p>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <p class="error"><?= $error_message; ?></p>
        <?php endif; ?>

        <table>
            <tr>
                <th>Student Name</th>
                <th>Reason</th>
                <th>Leave Date</th>
                <th>Leave Time</th>
                <th>Return Date</th>
                <th>Return Time</th>
                <th>Comment & Action</th>
            </tr>
            <?php while ($row = $requests_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_name']); ?></td>
                    <td><?= htmlspecialchars($row['reason']); ?></td>
                    <td><?= htmlspecialchars($row['leave_date']); ?></td>
                    <td><?= (!empty($row['leave_time'])) ? date("h:i A", strtotime($row['leave_time'])) : "<span style='color:gray;'>NOT SCANNED</span>"; ?></td>
                    <td><?= htmlspecialchars($row['return_date']); ?></td>
                    <td><?= (!empty($row['return_time'])) ? date("h:i A", strtotime($row['return_time'])) : "<span style='color:gray;'>NOT SCANNED</span>"; ?></td>
                    <td>
                        <form action="" method="post">
                            <input type="hidden" name="request_id" value="<?= $row['id']; ?>">
                            <textarea name="comment" placeholder="Optional comment"></textarea>
                            <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                            <button type="submit" name="action" value="reject" class="reject-btn" required>Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- History Section -->
    <div class="container">
        <h2>Outpass History</h2>

        <input type="text" id="searchInput" onkeyup="filterHistory()" placeholder="Search by student name...">
        <select id="statusFilter" onchange="filterHistory()">
            <option value="all">All</option>
            <option value="teacher_approved">Approved</option>
            <option value="rejected">Rejected</option>
        </select>

        <table id="historyTable">
            <thead>
                <tr>
                    <th>Student Name</th>
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
                        <td><?= htmlspecialchars($row['reason']); ?></td>
                        <td><?= htmlspecialchars($row['leave_date']); ?></td>
                        <td><?= (!empty($row['leave_time'])) ? date("h:i A", strtotime($row['leave_time'])) : "<span style='color:gray;'>NOT SCANNED</span>"; ?></td>
                        <td><?= htmlspecialchars($row['return_date']); ?></td>
                        <td><?= (!empty($row['return_time'])) ? date("h:i A", strtotime($row['return_time'])) : "<span style='color:gray;'>NOT SCANNED</span>"; ?></td>
                        <td><?= htmlspecialchars($row['teacher_comment'] ?? 'No Comment'); ?></td>
                        <td><?= htmlspecialchars($row['warden_comment'] ?? 'No Comment'); ?></td>
                        <td style="color: <?= ($row['status'] == 'rejected') ? 'red' : 'green'; ?>;">
                            <?= ucfirst(htmlspecialchars($row['status'])); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
