<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo '<script>window.location.href = "login.php";</script>';
    exit;
}

$user_id = $_SESSION['user_id'];

// Validate user_id exists in users table
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_exists = $stmt->fetchColumn();
    if ($user_exists == 0) {
        $error = "Invalid user session. Please log in again.";
        echo '<script>window.location.href = "login.php";</script>';
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error validating user: " . $e->getMessage();
    error_log("User validation failed for user_id $user_id: " . $e->getMessage());
}

// Handle booking cancellation (redirect to new file)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    echo '<script>window.location.href = "cancel_appointment.php?id=' . htmlspecialchars($booking_id) . '";</script>';
    exit;
}

// Fetch availability
$stmt = $pdo->prepare("SELECT * FROM availability WHERE user_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
$stmt->execute([$user_id]);
$availabilities = $stmt->fetchAll();

// Fetch bookings with debugging
try {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date, start_time");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
    error_log("Fetched " . count($bookings) . " bookings for user_id $user_id");
} catch (PDOException $e) {
    $error = "Database error fetching bookings: " . $e->getMessage();
    error_log("Booking fetch failed for user_id $user_id: " . $e->getMessage());
    $bookings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ScheduleEasy</title>
    <style>
        body {
            background: linear-gradient(135deg, #6b48ff, #00ddeb);
            font-family: Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .dashboard {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 800px;
        }
        h2, h3 {
            color: #6b48ff;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
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
            text-align: center;
            margin-bottom: 10px;
        }
        .error { color: red; }
        .success { color: green; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #6b48ff;
            color: white;
        }
        .cancel-btn {
            background: #ff4d4d;
        }
        .cancel-btn:hover {
            background: #e63939;
        }
        @media (max-width: 600px) {
            .dashboard {
                margin: 10px;
                padding: 15px;
            }
            table {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h2>Your Dashboard</h2>
        <p><a href="#" onclick="redirectToLogout()">Logout</a></p>
        
        <h3>Add Availability</h3>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <p><button class="btn" onclick="redirectToAddSlots()">Add New Slot</button></p>

        <h3>Your Availability</h3>
        <?php if (empty($availabilities)): ?>
            <p>No availability set. <button class="btn" onclick="redirectToAddSlots()">Add a Slot</button></p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                </tr>
                <?php foreach ($availabilities as $avail): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($avail['day_of_week']); ?></td>
                        <td><?php echo htmlspecialchars($avail['start_time']); ?></td>
                        <td><?php echo htmlspecialchars($avail['end_time']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <h3>Your Bookings</h3>
        <?php if (empty($bookings)): ?>
            <p>No bookings found. <button class="btn" onclick="redirectToBook()">Book a Meeting</button> or check if availability is set.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Visitor</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['visitor_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['visitor_email']); ?></td>
                        <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
                        <td><?php echo htmlspecialchars($booking['start_time'] . ' - ' . $booking['end_time']); ?></td>
                        <td><?php echo htmlspecialchars($booking['status']); ?></td>
                        <td>
                            <?php if ($booking['status'] != 'cancelled'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" name="cancel_booking" class="btn cancel-btn">Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <script>
        function redirectToLogout() {
            window.location.href = 'logout.php';
        }
        function redirectToAddSlots() {
            window.location.href = 'add_slots.php';
        }
        function redirectToBook() {
            window.location.href = 'book_appointment.php';
        }
    </script>
</body>
</html>
