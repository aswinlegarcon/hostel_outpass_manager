<?php
session_start();
include 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Kolkata');

// Ensure only gate security can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gate_security') {
    header("Location: login.php");
    exit();
}

$name = $_SESSION['name'];
$email = $_SESSION['email'];

// Ensure MySQL session is in IST
$conn->query("SET time_zone = '+05:30'");

// Fetch new outpasses (Not yet scanned)
$new_outpass_query = "SELECT g.id, g.outpass_id, u.name AS student_name, u.department, 
                             o.reason, o.leave_date, o.return_date, g.status
                      FROM gate_approvals g
                      JOIN outpass_requests o ON g.outpass_id = o.id
                      JOIN users u ON g.student_id = u.id
                      WHERE g.status = 'pending' AND g.exit_time IS NULL";
$new_outpass_result = $conn->query($new_outpass_query);

// Fetch 1-time scanned outpasses (Only Exit Scanned)
$scanned_outpass_query = "SELECT g.id, g.outpass_id, u.name AS student_name, u.department, 
                                  o.reason, o.leave_date, o.return_date, g.exit_time, g.status
                           FROM gate_approvals g
                           JOIN outpass_requests o ON g.outpass_id = o.id
                           JOIN users u ON g.student_id = u.id
                           WHERE g.status = 'pending' AND g.exit_time IS NOT NULL AND g.return_time IS NULL";
$scanned_outpass_result = $conn->query($scanned_outpass_query);

// Fetch history of invalid outpasses (Both Entry & Exit Scanned)
$invalid_outpass_query = "SELECT g.id, g.outpass_id, u.name AS student_name, u.department, 
                                  o.reason, o.leave_date, o.return_date, g.exit_time, g.return_time, g.status
                           FROM gate_approvals g
                           JOIN outpass_requests o ON g.outpass_id = o.id
                           JOIN users u ON g.student_id = u.id
                           WHERE g.status = 'completed'";
$invalid_outpass_result = $conn->query($invalid_outpass_query);

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
        if (empty($record['exit_time'])) {
            // First scan - Mark exit time (leave time) in IST
            $update_sql = "UPDATE outpass_requests SET leave_time = TIME(NOW()) WHERE id = ?";
            $update_gate_sql = "UPDATE gate_approvals SET exit_time = NOW() WHERE outpass_id = ?";
            $success_message = "Exit time recorded!";
        } else {
            // Second scan - Mark re-entry (return time) in IST and complete the outpass
            $update_sql = "UPDATE outpass_requests SET return_time = TIME(NOW()), status = 'invalid' WHERE id = ?";
            $update_gate_sql = "UPDATE gate_approvals SET return_time = NOW(), status = 'completed' WHERE outpass_id = ?";
            $success_message = "Re-entry recorded! Outpass is now invalid.";
        }

        // Update leave/return time in `outpass_requests`
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $outpass_id);
        $stmt->execute();

        // Update gate approvals to reflect scan time
        $stmt = $conn->prepare($update_gate_sql);
        $stmt->bind_param("i", $outpass_id);
        $stmt->execute();
    } else {
        $error_message = "Invalid outpass!";
    }

    header("Location: gate.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Security | Verify Outpass</title>
    <link rel="stylesheet" href="css/gate.css">
    <script>
        function searchHistory() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll("#historyTable tbody tr");

            rows.forEach(row => {
                let department = row.cells[1].innerText.toLowerCase();
                row.style.display = department.includes(input) ? "" : "none";
            });
        }
    </script>
</head>
<body>

    <div class="profile-container">
        <img src="profile.png" alt="Profile Icon" class="profile-icon">
        <div class="profile-info">
            <p><strong><?= htmlspecialchars($name) ?></strong></p>
            <p>Role: Gate Security</p>
            <p>Email: <?= htmlspecialchars($email) ?></p>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Section: New Outpasses -->
    <div class="container">
        <h2>New Outpasses (Not Scanned)</h2>
        <table>
            <tr>
                <th>Student Name</th>
                <th>Department</th>
                <th>Reason</th>
                <th>Leave Date</th>
                <th>Return Date</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $new_outpass_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_name']); ?></td>
                    <td><?= htmlspecialchars($row['department']); ?></td>
                    <td><?= htmlspecialchars($row['reason']); ?></td>
                    <td><?= htmlspecialchars($row['leave_date']); ?></td>
                    <td><?= htmlspecialchars($row['return_date']); ?></td>
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

    <!-- Section: 1 Time Scanned Outpasses -->
    <div class="container">
        <h2>1 Time Scanned Outpasses (Exit Scanned)</h2>
        <table>
            <tr>
                <th>Student Name</th>
                <th>Department</th>
                <th>Reason</th>
                <th>Leave Date</th>
                <th>Exit Time</th>
                <th>Return Date</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $scanned_outpass_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_name']); ?></td>
                    <td><?= htmlspecialchars($row['department']); ?></td>
                    <td><?= htmlspecialchars($row['reason']); ?></td>
                    <td><?= htmlspecialchars($row['leave_date']); ?></td>
                    <td><?= date("h:i A", strtotime($row['exit_time'])); ?></td>
                    <td><?= htmlspecialchars($row['return_date']); ?></td>
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

    <!-- Section: Invalid Outpasses History -->
    <div class="container">
        <h2>Invalid Outpasses (History)</h2>
        <input type="text" id="searchInput" onkeyup="searchHistory()" placeholder="Search by Department...">
        <table id="historyTable">
            <tr>
                <th>Student Name</th>
                <th>Department</th>
                <th>Reason</th>
                <th>Leave Date</th>
                <th>Exit Time</th>
                <th>Return Date</th>
                <th>Return Time</th>
            </tr>
            <?php while ($row = $invalid_outpass_result->fetch_assoc()) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_name']); ?></td>
                    <td><?= htmlspecialchars($row['department']); ?></td>
                    <td><?= htmlspecialchars($row['reason']); ?></td>
                    <td><?= htmlspecialchars($row['leave_date']); ?></td>
                    <td><?= date("h:i A", strtotime($row['exit_time'])); ?></td>
                    <td><?= htmlspecialchars($row['return_date']); ?></td>
                    <td><?= date("h:i A", strtotime($row['return_time'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
<!-- jpi.selfmade.one -->
</body>
</html>
