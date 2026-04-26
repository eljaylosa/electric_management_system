<?php
require_once 'config.php';

header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');
$address = trim($_POST['address'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$category_id = intval($_POST['category_id'] ?? 0);

if (!$name || !$address || !$email || !$category_id) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing fields'
    ]);
    exit;
}

/* CHECK DUPLICATE PENDING REQUEST */
$check = $conn->prepare("
    SELECT id FROM consumer_requests 
    WHERE email=? AND status='pending'
");
$check->bind_param("s", $email);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'You already have a pending request'
    ]);
    exit;
}

/* INSERT REQUEST (WITH CATEGORY) */
$stmt = $conn->prepare("
    INSERT INTO consumer_requests 
    (full_name, address, contact, email, category_id, status)
    VALUES (?, ?, ?, ?, ?, 'pending')
");

$stmt->bind_param("ssssi", $name, $address, $contact, $email, $category_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Request submitted successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => $stmt->error
    ]);
}