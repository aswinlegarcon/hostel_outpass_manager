<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $department = $_POST['department'] ?? NULL;
    
    // Additional fields for students
    $year_of_study = $_POST['year_of_study'] ?? NULL;
    $room_no = $_POST['room_no'] ?? NULL;
    $roll_no = $_POST['roll_no'] ?? NULL;
    $hostel_name = $_POST['hostel_name'] ?? NULL;

    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($check_email);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $error_message = "Email is already registered!";
    } else {
        // Insert user based on role
        if ($role === "student") {
            $sql = "INSERT INTO users (name, email, password, role, department, year_of_study, room_no, roll_no, hostel_name) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssss", $name, $email, $password, $role, $department, $year_of_study, $room_no, $roll_no, $hostel_name);
        } else {
            $sql = "INSERT INTO users (name, email, password, role, department) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $name, $email, $password, $role, $department);
        }

        if ($stmt->execute()) {
            $success_message = "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            $error_message = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup | KPRCAS Outpass</title>
    <link rel="stylesheet" href="css/login.css">
    <script>
        function toggleStudentFields() {
            let role = document.getElementById("role").value;
            let studentFields = document.getElementById("studentFields");

            if (role === "student") {
                studentFields.style.display = "block";
            } else {
                studentFields.style.display = "none";
            }
        }
    </script>
</head>
<body>

    <h1>KPRCAS OUTPASS</h1>

    <div class="container signup">
        <h2>Sign Up</h2>
        
        <?php if (isset($error_message)) : ?>
            <p style="color: red;"><?= $error_message; ?></p>
        <?php endif; ?>

        <?php if (isset($success_message)) : ?>
            <p style="color: green;"><?= $success_message; ?></p>
        <?php endif; ?>

        <form action="" method="post">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>

            <select name="role" id="role" onchange="toggleStudentFields()" required>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="warden">Warden</option>
                <option value="gate_security">Gate Security</option>
            </select>

            <input type="text" name="department" placeholder="Department (Only for Students & Teachers)">

            <!-- Student-specific fields -->
            <div id="studentFields" style="display: none;">
                <input type="text" name="year_of_study" placeholder="Year of Study">
                <input type="text" name="room_no" placeholder="Room No">
                <input type="text" name="roll_no" placeholder="Roll No">
                <input type="text" name="hostel_name" placeholder="Hostel Name">
            </div>

            <button type="submit">Register</button>
        </form>

        <div class="switch-container">
            <a href="login.php" class="switch-btn">Already have an account? Login here</a>
        </div>
    </div>

</body>
</html>
