<?php
$host = ""; // your hostname
$user = ""; // your username
$pass = ""; // your password
$dbname = ""; // your dbname
date_default_timezone_set('Asia/Kolkata');

$conn = new mysqli($host, $user, $pass, $dbname);
if($conn->connect_error) 
{
    die("Connection failed: " . $conn->connect_error);
}
?>
