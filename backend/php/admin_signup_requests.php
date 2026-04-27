<?php
// SHOW ALL ERRORS - CRITICAL!
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ================= GET REQUESTS ================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $conn->prepare("
            SELECT id, full_name, address, contact, email, category_id,  status, created_at
            FROM consumer_requests
            WHERE status='pending'
            ORDER BY created_at DESC
        ");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'data' => $data,
            'count' => count($data) // Debug info
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage(),
            'debug' => 'GET request failed'
        ]);
    }
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

/* ================= APPROVE ================= */
if ($action === 'approve_request') {
    require_once 'mailer.php';

    $stmt = $conn->prepare("SELECT * FROM consumer_requests WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();

    if (!$req) {
        echo json_encode(['status'=>'error','message'=>'Request not found']);
        exit;
    }

    /* CHECK USER EXISTS */
    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $req['email']);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status'=>'error','message'=>'User already exists']);
        exit;
    }

    /* SAFE METER NO */
    $result = $conn->query("SELECT MAX(id) AS max_id FROM consumers");
    $row = $result->fetch_assoc();
    $nextId = ($row['max_id'] ?? 0) + 1;

    $meter_no = "MTR-" . str_pad($nextId, 6, "0", STR_PAD_LEFT);

    $username = strtolower(str_replace(' ', '_', $req['full_name']));
    $passwordPlain = bin2hex(random_bytes(4)); // 8-character random password
    $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {

        /* CREATE USER */
        $stmt = $conn->prepare("
            INSERT INTO users (username, password, role, email)
            VALUES (?, ?, 'customer', ?)
        ");
        $stmt->bind_param("sss", $username, $passwordHash, $req['email']);
        $stmt->execute();

        $user_id = $conn->insert_id;

        /* CREATE CONSUMER */
        $stmt = $conn->prepare("
            INSERT INTO consumers (name, address, contact, meter_no, user_id, category_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssii",
            $req['full_name'],
            $req['address'],
            $req['contact'],
            $meter_no,
            $user_id,
            $req['category_id']
        );
        $stmt->execute();

        /* UPDATE REQUEST */
        $stmt = $conn->prepare("
            UPDATE consumer_requests SET status='approved' WHERE id=?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $conn->commit();

        /* SEND EMAIL */
        sendCredentialsEmail(
            $req['email'],
            $username,
            $passwordPlain,
            $meter_no
        );

        echo json_encode([
            'status' => 'success',
            'message' => 'Approved + account created',
            'credentials' => [
                'username' => $username,
                'password' => $passwordPlain,
                'email' => $req['email'],
                'meter_no' => $meter_no
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }

    exit;
}

/* ================= REJECT ================= */
if ($action === 'reject_request') {

    $stmt = $conn->prepare("
        UPDATE consumer_requests SET status='rejected' WHERE id=?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode([
        'status' => 'success',
        'message' => 'Request rejected'
    ]);

    exit;
}