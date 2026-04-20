<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

/* GET ALL CATEGORIES */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_all') {

    $sql = "SELECT id, name FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);

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