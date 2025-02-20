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
$requests_query = "SELECT o.id, u.id AS student_id, u.name AS student_name, u.department, 
                   o.reason, o.leave_date, o.leave_time, o.return_date, o.return_time, o.status 
                   FROM outpass_requests o 
                   JOIN users u ON o.student_id = u.id 
                   WHERE o.status = 'teacher_approved'";
$requests_result = $conn->query($requests_query);

// Handle Approval/Rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action == 'approve') {
        $update_sql = "UPDATE outpass_requests SET status = 'warden_approved' WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();

        // Fetch student details
        $student_query = "SELECT student_id FROM outpass_requests WHERE id = ?";
        $stmt = $conn->prepare($student_query);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $student_id = $student['student_id'];

        // Store gate pass in student’s profile
        $insert_gatepass = "INSERT INTO student_gatepass (student_id, outpass_id, status) VALUES (?, ?, 'approved')";
        $stmt = $conn->prepare($insert_gatepass);
        $stmt->bind_param("ii", $student_id, $request_id);
        $stmt->execute();

        // Insert into gate security table
        $insert_gate_security = "INSERT INTO gate_approvals (outpass_id, student_id, status) VALUES (?, ?, 'pending')";
        $stmt = $conn->prepare($insert_gate_security);
        $stmt->bind_param("ii", $request_id, $student_id);
        $stmt->execute();

        $success_message = "Outpass approved and sent to the student & gate security!";
    }
    else {
        $update_sql = "UPDATE outpass_requests SET status = 'rejected' WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();

        $success_message = "Outpass request has been rejected!";
    }
}

// Refresh request list
$requests_result = $conn->query($requests_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warden Panel | Approve Outpass</title>
    <link rel="stylesheet" href="css/warden.css">
</head>
<body>

    <!-- Profile Section -->
    <div class="profile-container">
        <img src="profile.png" alt="Profile Icon" class="profile-icon">
        <div class="profile-info">
            <p><strong><?= $name ?></strong></p>
            <p>Role: Warden</p>
            <p>Email: <?= $email ?></p>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
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
                        <form action="" method="post">
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
