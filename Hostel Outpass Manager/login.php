<?php
session_start();
include 'db.php';

// Destroy any existing session before logging in
session_unset();
session_destroy();
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department'] = $user['department'];
        $_SESSION['email'] = $user['email'];

        // Redirect based on role
        switch ($user['role']) {
            case 'student': header("Location: student.php"); break;
            case 'teacher': header("Location: teacher.php"); break;
            case 'warden': header("Location: warden.php"); break;
            case 'gate_security': header("Location: gate.php"); break;
        }
        exit();
    } else {
        $error_message = "Invalid email or password!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | KPRCAS Outpass</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

    <h1>KPRCAS OUTPASS</h1>

    <div class="container login">
        <h2>Login</h2>
        
        <?php if (isset($error_message)) : ?>
            <p style="color: red;"><?= $error_message; ?></p>
        <?php endif; ?>

        <form action="" method="post">
            <input type="email" name="email" placeholder="Enter your email" required>
            <input type="password" name="password" placeholder="Enter your password" required>
            <button type="submit">Login</button>
        </form>
        
        <div class="switch-container">
            <a href="signup.php" class="switch-btn">Don't have an account? Sign up</a>
        </div>
    </div>

</body>
</html>
