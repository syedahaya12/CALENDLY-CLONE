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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $visitor_name = filter_input(INPUT_POST, 'visitor_name', FILTER_SANITIZE_STRING);
    $visitor_email = filter_input(INPUT_POST, 'visitor_email', FILTER_SANITIZE_EMAIL);
    $booking_date = filter_input(INPUT_POST, 'booking_date', FILTER_SANITIZE_STRING);
    $start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $end_time = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);

    // Debug input values
    error_log("Booking attempt - User ID: $user_id, Name: $visitor_name, Email: $visitor_email, Date: $booking_date, Start: $start_time, End: $end_time");

    // Validate inputs
    if (empty($visitor_name) || empty($visitor_email) || empty($booking_date) || empty($start_time) || empty($end_time)) {
        $error = "All fields are required.";
    } else {
        $start_datetime = DateTime::createFromFormat('Y-m-d H:i', $booking_date . ' ' . $start_time, new DateTimeZone('Europe/Istanbul'));
        $end_datetime = DateTime::createFromFormat('Y-m-d H:i', $booking_date . ' ' . $end_time, new DateTimeZone('Europe/Istanbul'));
        if ($start_datetime === false || $end_datetime === false || $end_datetime <= $start_datetime) {
            $error = "Invalid date or time range. Please use a valid format (YYYY-MM-DD HH:MM) and ensure end time is after start time.";
        } else {
            try {
                // Check availability
                $day_of_week = $start_datetime->format('l');
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM availability WHERE user_id = ? AND day_of_week = ? AND start_time <= ? AND end_time >= ?");
                $stmt->execute([$user_id, $day_of_week, $start_time, $end_time]);
                $is_available = $stmt->fetchColumn();

                if ($is_available == 0) {
                    $error = "Selected time slot is not available. Please choose a different slot.";
                } else {
                    // Check for existing bookings
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND booking_date = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?)) AND status != 'cancelled'");
                    $stmt->execute([$user_id, $booking_date, $end_time, $start_time, $start_time, $end_time]);
                    $conflict = $stmt->fetchColumn();

                    if ($conflict > 0) {
                        $error = "This time slot is already booked. Please select another time.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, visitor_name, visitor_email, booking_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
                        $stmt->execute([$user_id, $visitor_name, $visitor_email, $booking_date, $start_time, $end_time]);
                        $success = "Booking confirmed! You’ll receive a confirmation soon.";
                        // Optional: Uncomment to send email (requires mail setup)
                        // mail($visitor_email, "Booking Confirmation", "Your booking on $booking_date from $start_time to $end_time is confirmed.");
                        echo '<script>window.location.href = "dashboard.php";</script>';
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                error_log("Booking insert failed for user $user_id: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Booking - ScheduleEasy</title>
    <style>
        body {
            background: linear-gradient(135deg, #6b48ff, #00ddeb);
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .booking-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        h2 {
            color: #6b48ff;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .btn {
            padding: 12px;
            background: #6b48ff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a3de6;
        }
        .error, .success {
            margin-bottom: 10px;
        }
        .error { color: red; }
        .success { color: green; }
        @media (max-width: 600px) {
            .booking-container {
                margin: 10px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <h2>Confirm Your Booking</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="visitor_name">Your Name</label>
                <input type="text" id="visitor_name" name="visitor_name" required>
            </div>
            <div class="form-group">
                <label for="visitor_email">Your Email</label>
                <input type="email" id="visitor_email" name="visitor_email" required>
            </div>
            <input type="hidden" name="booking_date" value="<?php echo date('Y-m-d'); ?>">
            <input type="hidden" name="start_time" value="14:52">
            <input type="hidden" name="end_time" value="15:52">
            <button type="submit" class="btn">Confirm Booking</button>
        </form>
        <p><a href="#" onclick="redirectToHome()">Cancel</a></p>
    </div>
    <script>
        function redirectToHome() {
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>
