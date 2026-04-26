<?php

function sendNotification($conn, $sender_id, $receiver_id, $message, $type) {

    // basic validation
    if(empty($receiver_id) || empty($message)) return false;

    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (sender_id, receiver_id, message, type, is_read, created_at) 
        VALUES (?, ?, ?, ?, 0, NOW())
    ");

    if(!$stmt){
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("iiss", $sender_id, $receiver_id, $message, $type);

    if(!$stmt->execute()){
        error_log("Execute failed: " . $stmt->error);
        return false;
    }

    return true;
}
?>