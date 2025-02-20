<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ensure session variables exist
$student_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Unknown';
$department = $_SESSION['department'] ?? 'Not Assigned';
$email = $_SESSION['email'] ?? 'Not Available';

// Fetch history of previous outpasses
$history_query = "SELECT * FROM outpass_requests WHERE student_id=? ORDER BY created_at DESC";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$history_result = $stmt->get_result();

// Fetch approved gate pass
$approved_query = "SELECT * FROM student_gatepass WHERE student_id=? LIMIT 1";
$approved_stmt = $conn->prepare($approved_query);
$approved_stmt->bind_param("i", $student_id);
$approved_stmt->execute();
$approved_result = $approved_stmt->get_result();
$approved_pass = $approved_result->fetch_assoc();

// Handle outpass request submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reason = $_POST['reason'];
    $leave_date = $_POST['leave_date'];
    $leave_time = $_POST['leave_time'];
    $return_date = $_POST['return_date'];
    $return_time = $_POST['return_time'];

    // Validate input fields
    if (empty($reason) || empty($leave_date) || empty($leave_time) || empty($return_date) || empty($return_time)) {
        $error_message = "All fields are required!";
    } else {
        // Get the department teacher
        $query = "SELECT id FROM users WHERE role='teacher' AND department=? LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $department);
        $stmt->execute();
        $result = $stmt->get_result();
        $teacher = $result->fetch_assoc();

        if ($teacher) {
            $teacher_id = $teacher['id'];

            // Insert into outpass_requests table
            $sql = "INSERT INTO outpass_requests (student_id, department, reason, leave_date, leave_time, return_date, return_time, teacher_id, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issssssi", $student_id, $department, $reason, $leave_date, $leave_time, $return_date, $return_time, $teacher_id);

            if ($stmt->execute()) {
                $success_message = "Outpass request submitted!";
            } else {
                $error_message = "Database error: " . $conn->error;
            }
        } else {
            $error_message = "No teacher assigned for your department.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | KPRCAS Outpass</title>
    <link rel="stylesheet" href="css/student.css">
    <script>
        function showSection(sectionId) {
            document.getElementById("request-outpass").style.display = "none";
            document.getElementById("view-outpass").style.display = "none";
            document.getElementById(sectionId).style.display = "block";
        }
    </script>
</head>
<body>

    <!-- Profile Section -->
    <div class="profile-container">
        <img src="profile.png" alt="Profile Icon" class="profile-icon">
        <div class="profile-info">
            <p><strong><?= htmlspecialchars($name) ?></strong></p>
            <p>Department: <?= htmlspecialchars($department) ?></p>
            <p>Email: <?= htmlspecialchars($email) ?></p>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Buttons for Navigation -->
    <div class="button-container">
        <button onclick="showSection('request-outpass')">Request Outpass</button>
        <button onclick="showSection('view-outpass')">View Approved Outpasses</button>
    </div>

    <!-- Section: View Approved Outpasses -->
    <div class="container" id="view-outpass">
        <h2>Approved Gate Pass</h2>
        <?php if ($approved_pass) : ?>
            <p class="success">✅ Your outpass has been approved! You can use it at the gate.</p>
        <?php else : ?>
            <p class="error">❌ No approved outpass found.</p>
        <?php endif; ?>

        <h2>Outpass History</h2>
        <table>
            <tr>
                <th>Reason</th>
                <th>Leave</th>
                <th>Return</th>
                <th>Status</th>
            </tr>
            <?php while ($row = $history_result->fetch_assoc()) : ?>
                <tr>
                   
                    <td><?= htmlspecialchars($row['reason']); ?></td>
                    <td><?= htmlspecialchars($row['leave_date'] . " " . $row['leave_time']); ?></td>
                    <td><?= htmlspecialchars($row['return_date'] . " " . $row['return_time']); ?></td>
                    <td style="color: <?= ($row['status'] == 'invalid') ? 'red' : 'black'; ?>;">
                    <?= ucfirst(htmlspecialchars($row['status'])); ?>
                </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <!-- Section: Request Outpass -->
    <div class="container" id="request-outpass" style="display: none;">
        <h2>Request Outpass</h2>

        <?php if (isset($success_message)) : ?>
            <p class="success"><?= htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <p class="error"><?= htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form action="" method="post">
            <label for="reason">Reason for Outpass:</label>
            <textarea name="reason" required></textarea>

            <label for="leave_date">Leave Date:</label>
            <input type="date" name="leave_date" required>

            <label for="leave_time">Leave Time:</label>
            <input type="time" name="leave_time" required>

            <label for="return_date">Return Date:</label>
            <input type="date" name="return_date" required>

            <label for="return_time">Return Time:</label>
            <input type="time" name="return_time" required>

            <button type="submit">Submit Request</button>
        </form>
    </div>

</body>
</html>
