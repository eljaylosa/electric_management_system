<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

/* ================= GET ================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $action = $_GET['action'] ?? '';

    // ACTIVE CATEGORIES
    if ($action === 'get_all') {

        $sql = "SELECT id, name 
                FROM categories 
                WHERE is_deleted = 0
                ORDER BY name ASC";

        $result = $conn->query($sql);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode(['status'=>'success','data'=>$data]);
        exit;
    }

    // TRASH (DELETED)
    if ($action === 'get_deleted') {

        $sql = "SELECT id, name, deleted_at 
                FROM categories 
                WHERE is_deleted = 1
                ORDER BY deleted_at DESC";

        $result = $conn->query($sql);
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

    // ================= ADD CATEGORY =================
    if ($action === 'add_category') {

        $name = trim($_POST['name']);
        $rate = floatval($_POST['rate']);

        $stmt = $conn->prepare("
            INSERT INTO categories (name, rate_per_kwh, is_deleted)
            VALUES (?, ?, 0)
        ");
        $stmt->bind_param("sd", $name, $rate);

        echo json_encode([
            'status' => $stmt->execute() ? 'success' : 'error',
            'message' => $stmt->execute() ? 'Added successfully' : $stmt->error
        ]);
        exit;
    }

    // ================= UPDATE RATE =================
    if ($action === 'update_rate') {

        $id = intval($_POST['id']);
        $rate = floatval($_POST['rate']);

        $stmt = $conn->prepare("
            UPDATE categories 
            SET rate_per_kwh = ?
            WHERE id = ? AND is_deleted = 0
        ");
        $stmt->bind_param("di", $rate, $id);

        echo json_encode([
            'status' => $stmt->execute() ? 'success' : 'error',
            'message' => $stmt->execute() ? 'Updated successfully' : $stmt->error
        ]);
        exit;
    }

    // ================= SOFT DELETE + 2FA =================
    if ($action === 'delete') {

        $id = intval($_POST['id']);
        $password = $_POST['password'] ?? '';

        // verify admin password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $_SESSION['user_id']);
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

        echo json_encode([
            'status' => $stmt->execute() ? 'success' : 'error',
            'message' => 'Moved to trash'
        ]);
        exit;
    }

    // ================= RESTORE =================
    if ($action === 'restore') {

        $id = intval($_POST['id']);

        $stmt = $conn->prepare("
            UPDATE categories
            SET is_deleted = 0, deleted_at = NULL
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);

        echo json_encode([
            'status' => $stmt->execute() ? 'success' : 'error',
            'message' => 'Restored successfully'
        ]);
        exit;
    }

    // ================= FORCE DELETE =================
    if ($action === 'force_delete') {

        $id = intval($_POST['id']);

        $stmt = $conn->prepare("
            DELETE FROM categories
            WHERE id = ? AND is_deleted = 1
        ");
        $stmt->bind_param("i", $id);

        echo json_encode([
            'status' => $stmt->execute() ? 'success' : 'error',
            'message' => 'Permanently deleted'
        ]);
        exit;
    }
}

echo json_encode(['status'=>'error','message'=>'Invalid action']);