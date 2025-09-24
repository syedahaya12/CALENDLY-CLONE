<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'])) {
    $booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);

    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status != 'cancelled'");
        $stmt->execute([$booking_id, $user_id]);
        $rows_affected = $stmt->rowCount();

        if ($rows_affected > 0) {
            $success = "Appointment cancelled successfully.";
        } else {
            $error = "No valid appointment found to cancel.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Cancel appointment failed for user $user_id, booking_id $booking_id: " . $e->getMessage());
    }
    echo '<script>window.location.href = "dashboard.php";</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Appointment - ScheduleEasy</title>
    <style>
        body {
            background: linear-gradient(135deg, #6b48ff, #00ddeb);
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .cancel-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 {
            color: #6b48ff;
            margin-bottom: 20px;
        }
        .error, .success {
            margin-bottom: 20px;
        }
        .error { color: red; }
        .success { color: green; }
        .btn {
            padding: 12px;
            background: #ff4d4d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #e63939;
        }
        @media (max-width: 600px) {
            .cancel-container {
                margin: 10px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="cancel-container">
        <h2>Cancel Appointment</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="booking_id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">
            <button type="submit" class="btn">Confirm Cancellation</button>
        </form>
        <p><a href="#" onclick="redirectToDashboard()">Back</a></p>
    </div>
    <script>
        function redirectToDashboard() {
            window.location.href = 'dashboard.php';
        }
    </script>
</body>
</html>
