<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

/* ================= AUTH ================= */
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

/* ================= GET ================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $action = $_GET['action'] ?? '';

    // ACTIVE
    if ($action === 'get_all') {

        $result = $conn->query("
            SELECT * FROM categories 
            WHERE is_deleted = 0
            ORDER BY name ASC
        ");

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode(['status'=>'success','data'=>$data]);
        exit;
    }

    // TRASH
    if ($action === 'get_deleted') {

        $result = $conn->query("
            SELECT * FROM categories 
            WHERE is_deleted = 1
            ORDER BY deleted_at DESC
        ");

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode(['status'=>'success','data'=>$data]);
        exit;
    }
}

/* ================= POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* ================= ADD ================= */
    if ($action === 'add_category') {

        $name = trim($_POST['name'] ?? '');
        $rate = floatval($_POST['rate'] ?? 0);

        if ($name === '' || $rate <= 0) {
            echo json_encode(['status'=>'error','message'=>'Invalid input']);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO categories (name, rate_per_kwh, is_deleted)
            VALUES (?, ?, 0)
        ");
        $stmt->bind_param("sd", $name, $rate);
        $stmt->execute();

        echo json_encode(['status'=>'success','message'=>'Category added']);
        exit;
    }

    /* ================= UPDATE ================= */
    if ($action === 'update_rate') {

        $id = intval($_POST['id'] ?? 0);
        $rate = floatval($_POST['rate'] ?? 0);

        $stmt = $conn->prepare("
            UPDATE categories 
            SET rate_per_kwh = ?
            WHERE id = ? AND is_deleted = 0
        ");
        $stmt->bind_param("di", $rate, $id);
        $stmt->execute();

        echo json_encode(['status'=>'success','message'=>'Rate updated']);
        exit;
    }

    /* ================= DELETE (SOFT + 2FA) ================= */
    if ($action === 'delete_rate') {

        $id = intval($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || $password === '') {
            echo json_encode(['status'=>'error','message'=>'Invalid request']);
            exit;
        }

        // verify admin
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['status'=>'error','message'=>'Wrong password']);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE categories 
            SET is_deleted = 1, deleted_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(['status'=>'success','message'=>'Moved to trash']);
        exit;
    }

    /* ================= RESTORE ================= */
    if ($action === 'restore') {

        $id = intval($_POST['id'] ?? 0);

        $stmt = $conn->prepare("
            UPDATE categories 
            SET is_deleted = 0, deleted_at = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(['status'=>'success','message'=>'Restored']);
        exit;
    }

    /* ================= FORCE DELETE ================= */
    if ($action === 'force_delete') {

        $id = intval($_POST['id'] ?? 0);

        $stmt = $conn->prepare("
            DELETE FROM categories 
            WHERE id = ? AND is_deleted = 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(['status'=>'success','message'=>'Permanently deleted']);
        exit;
    }
}

echo json_encode(['status'=>'error','message'=>'Invalid request']);