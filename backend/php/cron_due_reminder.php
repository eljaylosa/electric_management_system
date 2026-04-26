<?php
require_once 'config.php';
require_once 'notification_helper.php';

// get admin
$admin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();
$admin_id = $admin['id'];

// get bills due in 2 days (you can change interval)
$stmt = $conn->prepare("
    SELECT b.id, b.due_date, c.user_id
    FROM bills b
    JOIN readings r ON b.reading_id = r.id
    JOIN consumers c ON r.consumer_id = c.id
    WHERE b.status = 'unpaid'
    AND DATEDIFF(b.due_date, CURDATE()) = 2
    AND NOT EXISTS (
        SELECT 1 FROM notifications n
        WHERE n.receiver_id = c.user_id
        AND n.type = 'reminder'
        AND DATE(n.created_at) = CURDATE()
    )
");

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    sendNotification(
        $conn,
        $admin_id,
        $row['user_id'],
        "Reminder: Your bill is due on " . $row['due_date'],
        "reminder"
    );
}

echo "Reminder job executed";