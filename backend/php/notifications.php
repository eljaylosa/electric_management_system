<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

/* ================= GET NOTIFICATIONS ================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_notifications') {

    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE receiver_id = ? 
        ORDER BY created_at DESC
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    echo json_encode([
        "status" => "success",
        "data" => $result->fetch_all(MYSQLI_ASSOC)
    ]);
    exit;
}


/* ================= MARK SINGLE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {

    $id = $_POST['id'];

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(["status" => "success"]);
    exit;
}


/* ================= MARK ALL ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE receiver_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    echo json_encode(["status" => "success"]);
    exit;
}


/* ================= DEFAULT ================= */
echo json_encode([
    "status" => "error",
    "message" => "Invalid request"
]);