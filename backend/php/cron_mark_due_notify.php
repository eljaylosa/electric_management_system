<?php
require_once 'config.php';
require_once 'notification_helper.php';

/*
    Sends notification ONLY ONCE
    when a bill becomes overdue
*/

$stmt = $conn->prepare("
    SELECT b.id, b.amount, b.due_date, c.user_id
    FROM bills b
    JOIN readings r ON b.reading_id = r.id
    JOIN consumers c ON r.consumer_id = c.id
    WHERE b.status = 'unpaid'
    AND DATE(b.due_date) < CURDATE()
    AND NOT EXISTS (
        SELECT 1 FROM notifications n
        WHERE n.receiver_id = c.user_id
        AND n.type = 'due_bill'
        AND n.message LIKE CONCAT('%', b.amount, '%')
    )
");

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    sendNotification(
        $conn,
        0,
        $row['user_id'],
        "⚠️ Your bill (₱{$row['amount']}) is now DUE.",
        "due_bill"
    );
}

echo "Due notifications processed";
?>