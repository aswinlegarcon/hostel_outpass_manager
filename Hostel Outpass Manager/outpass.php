<?php
session_start();
include 'db.php';

$student_id = $_SESSION['user_id'];
$result = $conn->query("SELECT * FROM outpass_requests WHERE student_id='$student_id' AND warden_approved=TRUE");

if ($row = $result->fetch_assoc()) {
    echo "<h2>Outpass Approved</h2>";
    echo "<p>Outpass ID: " . $row['id'] . "</p>";
} else {
    echo "<p>No approved outpasses.</p>";
}
?>
