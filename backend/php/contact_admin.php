<?php
header('Content-Type: application/json');

require_once 'mailer.php';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

if (!$name || !$email || !$message) {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required'
    ]);
    exit;
}

// sanitize
$name = htmlspecialchars($name);
$email = htmlspecialchars($email);
$message = htmlspecialchars($message);

// send email
$sent = sendContactEmail($name, $email, $message);

if ($sent === true) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent to admin successfully!'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to send message'
    ]);
}