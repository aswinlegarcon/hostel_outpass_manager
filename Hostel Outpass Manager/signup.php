<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $department = $_POST['department'] ?? NULL; // Department is optional

    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($check_email);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $error_message = "Email is already registered!";
    } else {
        // Insert new user
        $sql = "INSERT INTO users (name, email, password, role, department) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $password, $role, $department);

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
            <select name="role" required>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="warden">Warden</option>
                <option value="gate_security">Gate Security</option>
            </select>
            <input type="text" name="department" placeholder="Department (Only for Students & Teachers)">
            <button type="submit">Register</button>
        </form>

        <div class="switch-container">
            <a href="login.php" class="switch-btn">Already have an account? Login here</a>
        </div>
    </div>

</body>
</html>
