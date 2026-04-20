<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

/* ================= CHECK LOGIN ================= */
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

/* ================= GET ALL RATES ================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all') {

    $result = $conn->query("SELECT * FROM categories");

    if (!$result) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
    exit;
}

/* ================= UPDATE RATE ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update_rate') {

    $id = $_POST['id'] ?? null;
    $rate = $_POST['rate'] ?? null;

    if (!$id || !$rate) {
        echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE categories 
        SET rate_per_kwh=? 
        WHERE id=?
    ");

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $stmt->bind_param("di", $rate, $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Rate updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    exit;
}

/* ================= ADD CATEGORY ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add_category') {

    $name = $_POST['name'] ?? '';
    $rate = $_POST['rate'] ?? '';

    if ($name === '' || $rate === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO categories (name, rate_per_kwh)
        VALUES (?, ?)
    ");

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $stmt->bind_param("sd", $name, $rate);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Category added']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    exit;
}

/* ================= DELETE CATEGORY (OPTIONAL) ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_category') {

    $id = $_POST['id'] ?? null;

    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Deleted']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
?>