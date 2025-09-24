<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}
$user_id = $_SESSION['user_id'];
$bookings = $pdo->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY booking_date, start_time");
$bookings->execute([$user_id]);
$bookings = $bookings->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['cancel'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['cancel'], $user_id]);
    echo "<script>alert('Booking cancelled!'); window.location.href='bookings.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Bookings - ScheduleEasy</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: linear-gradient(to right, #6b7280, #d1d5db); color: #1f2937; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; }
        h2 { text-align: center; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #d1d5db; }
        th { background: #1e40af; color: white; }
        .cancel-btn { display: inline-block; padding: 12px 24px; background: #dc2626; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; transition: background 0.3s; text-decoration: none; }
        .cancel-btn:hover { background: #b91c1c; }
        .back-btn { display: inline-block; padding: 12px 24px; background: #1e40af; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; transition: background 0.3s; text-decoration: none; }
        .back-btn:hover { background: #1e3a8a; }
        @media (max-width: 768px) { table { font-size: 0.9em; } .container { padding: 15px; } }
    </style>
</head>
<body>
    <div class="container">
        <h2>Your Bookings</h2>
        <table>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Visitor</th>
                <th>Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php
            if (count($bookings) > 0) {
                foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo $booking['booking_date']; ?></td>
                        <td><?php echo $booking['start_time'] . ' - ' . $booking['end_time']; ?></td>
                        <td><?php echo $booking['visitor_name']; ?></td>
                        <td><?php echo $booking['visitor_email']; ?></td>
                        <td><?php echo $booking['status']; ?></td>
                        <td>
                            <?php if ($booking['status'] == 'confirmed'): ?>
                                <a href="bookings.php?cancel=<?php echo $booking['id']; ?>" class="cancel-btn">Cancel</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach;
            } else {
                echo "<tr><td colspan='6' style='text-align: center; padding: 15px;'>No bookings found.</td></tr>";
            }
            ?>
        </table>
        <div style="text-align: center; margin-top: 20px;">
            <a href="javascript:window.location.href='dashboard.php'" class="back-btn">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
