<?php
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='dashboard.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScheduleEasy - Book Meetings Effortlessly</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(to right, #6b7280, #d1d5db); color: #1f2937; }
        header { background: #1e40af; color: white; padding: 20px; text-align: center; }
        header h1 { margin: 0; font-size: 2.5em; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        .welcome { text-align: center; padding: 50px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .welcome h2 { font-size: 2em; color: #1e40af; }
        .welcome p { font-size: 1.2em; color: #4b5563; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px; background: #1e40af; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; }
        .btn:hover { background: #1e3a8a; }
        .links { text-align: center; margin-top: 20px; }
        .links a { color: #1e40af; text-decoration: none; font-weight: bold; margin: 0 10px; }
        .links a:hover { text-decoration: underline; }
        @media (max-width: 768px) { .welcome { padding: 20px; } .welcome h2 { font-size: 1.5em; } .btn { padding: 10px 20px; } }
    </style>
</head>
<body>
    <header>
        <h1>ScheduleEasy</h1>
    </header>
    <div class="container">
        <div class="welcome">
            <h2>Welcome to ScheduleEasy</h2>
            <p>Book meetings effortlessly with our intuitive scheduling platform. Set your availability, share your link, and let others book time with you!</p>
            <a href="javascript:window.location.href='schedule.php'" class="btn">Book a Meeting</a>
            <div class="links">
                <a href="javascript:window.location.href='signup.php'">Sign Up</a> |
                <a href="javascript:window.location.href='login.php'">Log In</a>
            </div>
        </div>
    </div>
</body>
</html>
