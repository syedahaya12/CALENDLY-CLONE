<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $day = filter_var($_POST['day'] ?? '', FILTER_DEFAULT);
    $start_time = filter_var($_POST['start_time'] ?? '', FILTER_DEFAULT);
    $end_time = filter_var($_POST['end_time'] ?? '', FILTER_DEFAULT);

    // Debug input values
    error_log("Add slot attempt - User ID: $user_id, Day: $day, Start: $start_time, End: $end_time");

    // Validate inputs
    if (empty($day) || empty($start_time) || empty($end_time)) {
        $error = "All fields are required.";
    } else {
        $start_datetime = DateTime::createFromFormat('H:i', $start_time, new DateTimeZone('Europe/Istanbul'));
        $end_datetime = DateTime::createFromFormat('H:i', $end_time, new DateTimeZone('Europe/Istanbul'));
        if ($start_datetime === false || $end_datetime === false || $end_datetime <= $start_datetime) {
            $error = "Invalid time range. Please ensure end time is after start time and use HH:MM format.";
        } else {
            try {
                // Check for overlapping slots
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM availability WHERE user_id = ? AND day_of_week = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))");
                $stmt->execute([$user_id, $day, $end_time, $start_time, $start_time, $end_time]);
                $overlap = $stmt->fetchColumn();

                if ($overlap > 0) {
                    $error = "This time slot overlaps with an existing availability.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO availability (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $day, $start_time, $end_time]);
                    error_log("Slot added successfully - User ID: $user_id, Day: $day, Start: $start_time, End: $end_time");
                    $success = "Availability slot added successfully!";
                    echo '<script>window.location.href = "dashboard.php";</script>';
                    exit;
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
                error_log("Slot insertion failed for user $user_id: " . $e->getMessage());
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
    <title>Add Availability Slots - ScheduleEasy</title>
    <style>
        body {
            background: linear-gradient(135deg, #6b48ff, #00ddeb);
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .slots-container {
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
        select, input[type="time"] {
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
            .slots-container {
                margin: 10px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="slots-container">
        <h2>Add Availability Slots</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="day">Day of Week</label>
                <select id="day" name="day" required>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday" selected>Sunday</option>
                </select>
            </div>
            <div class="form-group">
                <label for="start_time">Start Time</label>
                <input type="time" id="start_time" name="start_time" value="19:40" required>
            </div>
            <div class="form-group">
                <label for="end_time">End Time</label>
                <input type="time" id="end_time" name="end_time" value="20:40" required>
            </div>
            <button type="submit" class="btn">Add Slot</button>
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
