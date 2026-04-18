<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
}

// Login API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role, consumer_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['consumer_id'] = $user['consumer_id'];
            echo json_encode(['status' => 'success', 'message' => 'Login successful', 'role' => $user['role']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
    $stmt->close();
    exit;
}

// Signup API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $meter_no = $_POST['meter_no'];

    // First, check if the meter number exists in consumers table
    $stmt = $conn->prepare("SELECT id FROM consumers WHERE meter_no = ?");
    $stmt->bind_param("s", $meter_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $consumer = $result->fetch_assoc();
        $consumer_id = $consumer['id'];

        // Check if this consumer already has an account
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE consumer_id = ?");
        $stmt_check->bind_param("i", $consumer_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo json_encode(['status' => 'error', 'message' => 'An account already exists for this meter number']);
        } else {
            // Create the user account
            $stmt_insert = $conn->prepare("INSERT INTO users (username, password, role, consumer_id) VALUES (?, ?, 'customer', ?)");
            $stmt_insert->bind_param("ssi", $username, $password, $consumer_id);
            if ($stmt_insert->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Account created successfully. Please login.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Username already taken or registration failed']);
            }
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Meter number not found. Please contact support.']);
    }
    exit;
}

// Logout API
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: ../../frontend/html/login.html");
    exit;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is customer
function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}
?>
