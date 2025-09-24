<?php
session_start();
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            echo "<script>window.location.href='dashboard.php';</script>";
        } else {
            echo "<script>alert('Invalid password');</script>";
        }
    } else {
        echo "<script>alert('No user found with that email');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - ScheduleEasy</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(to right, #6b7280, #d1d5db); color: #1f2937; }
        .container { max-width: 600px; margin: 50px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        h2 { text-align: center; color: #1e40af; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 5px; }
        .btn { display: block; width: 100%; padding: 12px; background: #1e40af; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #1e3a8a; }
        .link { text-align: center; margin-top: 15px; }
        .link a { color: #1e40af; text-decoration: none; font-weight: bold; }
        .link a:hover { text-decoration: underline; }
        @media (max-width: 768px) { .container { margin: 20px; padding: 15px; } }
    </style>
</head>
<body>
    <div class="container">
        <h2>Log In</h2>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Log In</button>
        </form>
        <div class="link">
            <a href="javascript:window.location.href='signup.php'">Don't have an account? Sign Up</a>
        </div>
    </div>
</body>
</html>
