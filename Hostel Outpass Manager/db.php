<?php
$host = "mysql.selfmade.ninja";
$user = "aswinlegarcon1";
$pass = "12345678";
$dbname = "aswinlegarcon1_outpass_system";
date_default_timezone_set('Asia/Kolkata');

$conn = new mysqli($host, $user, $pass, $dbname);
if($conn->connect_error) 
{
    die("Connection failed: " . $conn->connect_error);
}
?>
