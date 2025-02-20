<?php
session_start();
include 'db.php';

// Ensure only teachers can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$department = $_SESSION['department'];
$email = $_SESSION['email'];

// Fetch all pending outpass requests assigned to this teacher
$requests_query = "SELECT o.id, u.name AS student_name, u.department, o.reason, 
                   o.leave_date, o.leave_time, o.return_date, o.return_time, o.status 
                   FROM outpass_requests o 
                   JOIN users u ON o.student_id = u.id 
                   WHERE o.teacher_id = '$teacher_id' AND o.status = 'pending'";
$requests_result = $conn->query($requests_query);

// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action == 'approve') {
        $update_sql = "UPDATE outpass_requests SET status = 'teacher_approved' WHERE id = ?";
    } else {
        $update_sql = "UPDATE outpass_requests SET status = 'rejected' WHERE id = ?";
    }

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $request_id);
    
    if ($stmt->execute()) {
        $success_message = "Outpass request has been " . ($action == 'approve' ? "approved" : "rejected") . "!";
    } else {
        $error_message = "Error updating request: " . $conn->error;
    }
}

// Refresh request list after any action
$requests_result = $conn->query($requests_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Panel | Approve Outpass</title>
    <link rel="stylesheet" href="css/teacher.css">
</head>
<body>

    <!-- Profile Section -->
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
                <th>Leave</th>
                <th>Return</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $requests_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= $row['student_name']; ?></td>
                    <td><?= $row['reason']; ?></td>
                    <td><?= $row['leave_date'] . " " . $row['leave_time']; ?></td>
                    <td><?= $row['return_date'] . " " . $row['return_time']; ?></td>
                    <td>
                        <form action="" method="post" class="action-form">
                            <input type="hidden" name="request_id" value="<?= $row['id']; ?>">
                            <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                            <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

</body>
</html>
