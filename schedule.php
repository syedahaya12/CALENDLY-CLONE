<?php
session_start();
require 'db.php';

// Get user ID from query parameter (default to 1 for testing)
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

// Fetch availability for the user
$stmt = $pdo->prepare("SELECT * FROM availability WHERE user_id = ?");
$stmt->execute([$user_id]);
$availabilities = $stmt->fetchAll();

// Generate available time slots for the next 7 days
$time_slots = [];
$today = new DateTime();
for ($i = 0; $i < 7; $i++) {
    $date = (clone $today)->modify("+$i days");
    $day_name = $date->format('l');
    foreach ($availabilities as $avail) {
        if ($avail['day_of_week'] == $day_name) {
            $start = new DateTime($date->format('Y-m-d') . ' ' . $avail['start_time']);
            $end = new DateTime($date->format('Y-m-d') . ' ' . $avail['end_time']);
            while ($start < $end) {
                $slot_end = (clone $start)->modify('+30 minutes');
                if ($slot_end <= $end) {
                    // Check if slot is already booked
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND booking_date = ? AND start_time = ? AND status != 'cancelled'");
                    $stmt->execute([$user_id, $date->format('Y-m-d'), $start->format('H:i:s')]);
                    $booked = $stmt->fetchColumn();
                    if ($booked == 0) {
                        $time_slots[$date->format('Y-m-d')][] = [
                            'start' => $start->format('H:i'),
                            'end' => $slot_end->format('H:i')
                        ];
                    }
                }
                $start = $slot_end;
            }
        }
    }
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $visitor_name = filter_input(INPUT_POST, 'visitor_name', FILTER_SANITIZE_STRING);
    $visitor_email = filter_input(INPUT_POST, 'visitor_email', FILTER_SANITIZE_EMAIL);
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Validate booking against availability
    $is_valid = false;
    foreach ($availabilities as $avail) {
        $avail_start = new DateTime($booking_date . ' ' . $avail['start_time']);
        $avail_end = new DateTime($booking_date . ' ' . $avail['end_time']);
        $slot_start = new DateTime($booking_date . ' ' . $start_time);
        $slot_end = new DateTime($booking_date . ' ' . $end_time);
        if ($avail['day_of_week'] == (new DateTime($booking_date))->format('l') && 
            $slot_start >= $avail_start && $slot_end <= $avail_end) {
            $is_valid = true;
            break;
        }
    }

    if (!$is_valid) {
        $error = "Selected time slot is not available.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO bookings (user_id, visitor_name, visitor_email, booking_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
            $stmt->execute([$user_id, $visitor_name, $visitor_email, $booking_date, $start_time, $end_time]);
            $success = "Booking confirmed! You’ll receive an email soon.";
            // Optional: Send confirmation email (requires mail setup)
            // mail($visitor_email, "Booking Confirmation", "Your booking on $booking_date from $start_time to $end_time is confirmed.");
        } catch (PDOException $e) {
            $error = "Error booking: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Meeting - ScheduleEasy</title>
    <style>
        body {
            background: linear-gradient(135deg, #6b48ff, #00ddeb);
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .schedule-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        h2, h4 {
            color: #6b48ff;
            text-align: center;
            margin-bottom: 20px;
        }
        .slot {
            padding: 10px;
            margin: 5px;
            background: #f0f0f0;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            transition: background 0.3s;
        }
        .slot:hover {
            background: #e0e0e0;
        }
        .form-group {
            margin-bottom: 15px;
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
        }
        .btn {
            width: 100%;
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
            text-align: center;
            margin-bottom: 10px;
        }
        .error { color: red; }
        .success { color: green; }
        @media (max-width: 600px) {
            .schedule-container {
                margin: 10px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="schedule-container">
        <h2>Book a Meeting</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <h3>Available Time Slots</h3>
        <?php if (empty($time_slots)): ?>
            <p>No available time slots.</p>
        <?php else: ?>
            <?php foreach ($time_slots as $date => $slots): ?>
                <h4><?php echo htmlspecialchars($date); ?></h4>
                <?php foreach ($slots as $slot): ?>
                    <div class="slot" onclick="selectSlot('<?php echo $date; ?>', '<?php echo $slot['start']; ?>', '<?php echo $slot['end']; ?>')">
                        <?php echo htmlspecialchars($slot['start'] . ' - ' . $slot['end']); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </form>
        <p><a href="#" onclick="redirectToHome()">Back to Home</a></p>
    </div>
    <script>
        function selectSlot(date, start, end) {
            document.getElementById('booking_date').value = date;
            document.getElementById('start_time').value = start;
            document.getElementById('end_time').value = end;
            document.getElementById('booking_form').style.display = 'block';
        }
        function redirectToHome() {
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>
