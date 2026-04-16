<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Consumer Management API
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all consumers
    if (isset($_GET['action']) && $_GET['action'] === 'get_all') {
        $stmt = $conn->prepare("SELECT c.id, c.name, c.address, c.contact, c.meter_no, IFNULL(cat.name, 'Uncategorized') AS category_name, IFNULL(cat.rate_per_kwh, 0) AS rate_per_kwh FROM consumers c LEFT JOIN categories cat ON c.category_id = cat.id");
        $stmt->execute();
        $result = $stmt->get_result();
        $consumers = [];
        while ($row = $result->fetch_assoc()) {
            $consumers[] = $row;
        }
        echo json_encode(['status' => 'success', 'data' => $consumers]);
        $stmt->close();
        exit;
    }

    // Get a single consumer by ID
    if (isset($_GET['action']) && $_GET['action'] === 'get_by_id' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $conn->prepare("SELECT c.id, c.name, c.address, c.contact, c.meter_no, c.category_id, cat.name AS category_name, cat.rate_per_kwh FROM consumers c JOIN categories cat ON c.category_id = cat.id WHERE c.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $consumer = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'data' => $consumer]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Consumer not found']);
        }
        $stmt->close();
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new consumer
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $contact = $_POST['contact'];
        $meter_no = $_POST['meter_no'];
        $category_id = $_POST['category_id'];

        $stmt = $conn->prepare("INSERT INTO consumers (name, address, contact, meter_no, category_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $name, $address, $contact, $meter_no, $category_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Consumer added successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add consumer']);
        }
        $stmt->close();
        exit;
    }

    // Update an existing consumer
    if (isset($_POST['action']) && $_POST['action'] === 'update' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $address = $_POST['address'];
        $contact = $_POST['contact'];
        $meter_no = $_POST['meter_no'];
        $category_id = $_POST['category_id'];

        $stmt = $conn->prepare("UPDATE consumers SET name = ?, address = ?, contact = ?, meter_no = ?, category_id = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $name, $address, $contact, $meter_no, $category_id, $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Consumer updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update consumer']);
        }
        $stmt->close();
        exit;
    }

    // Delete a consumer
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM consumers WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Consumer deleted successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete consumer']);
        }
        $stmt->close();
        exit;
    }
}
?>
