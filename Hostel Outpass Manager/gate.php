<?php
session_start();
include 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure only gate security can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gate_security') {
    header("Location: login.php");
    exit();
}

$name = $_SESSION['name'];
$email = $_SESSION['email'];

// Fetch all approved outpasses that are still valid (not completed)
$approved_query = "SELECT g.id, g.outpass_id, u.name AS student_name, u.department, o.reason, 
                          o.leave_date, o.leave_time, o.return_date, o.return_time, g.status 
                   FROM gate_approvals g
                   JOIN outpass_requests o ON g.outpass_id = o.id
                   JOIN users u ON g.student_id = u.id
                   WHERE g.status = 'pending'";
$approved_result = $conn->query($approved_query);

// Handle gate security scans
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $outpass_id = $_POST['outpass_id'];

    // Fetch the current scan status
    $check_scan = "SELECT * FROM gate_approvals WHERE outpass_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($check_scan);
    $stmt->bind_param("i", $outpass_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();

    if ($record) {
        if (is_null($record['exit_time'])) {
            // First scan - Mark exit time
            $update_sql = "UPDATE gate_approvals SET exit_time = NOW() WHERE outpass_id = ?";
            $success_message = "Exit time recorded!";
        } else {
            // Second scan - Mark re-entry and complete the outpass
            $update_sql = "UPDATE gate_approvals SET return_time = NOW(), status = 'completed' WHERE outpass_id = ?";
            $success_message = "Re-entry recorded! Outpass is now invalid.";
        }

        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $outpass_id);
        $stmt->execute();
    } else {
        $error_message = "Invalid outpass!";
    }
}

// Refresh the list after scanning
$approved_result = $conn->query($approved_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Security | Verify Outpass</title>
    <link rel="stylesheet" href="css/gate.css">
</head>
<body>

    <!-- Profile Section -->
    <div class="profile-container">
        <img src="profile.png" alt="Profile Icon" class="profile-icon">
        <div class="profile-info">
            <p><strong><?= htmlspecialchars($name) ?></strong></p>
            <p>Role: Gate Security</p>
            <p>Email: <?= htmlspecialchars($email) ?></p>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Approved Outpasses</h2>

        <?php if (isset($success_message)) : ?>
            <p class="success"><?= htmlspecialchars($success_message); ?></p>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <p class="error"><?= htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <table>
            <tr>
                <th>Student Name</th>
                <th>Department</th>
                <th>Reason</th>
                <th>Leave</th>
                <th>Return</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $approved_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_name']); ?></td>
                    <td><?= htmlspecialchars($row['department']); ?></td>
                    <td><?= htmlspecialchars($row['reason']); ?></td>
                    <td><?= htmlspecialchars($row['leave_date'] . " " . $row['leave_time']); ?></td>
                    <td><?= htmlspecialchars($row['return_date'] . " " . $row['return_time']); ?></td>
                    <td>
                        <form action="" method="post">
                            <input type="hidden" name="outpass_id" value="<?= $row['outpass_id']; ?>">
                            <button type="submit" class="scan-btn">Scan</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

</body>
</html>
