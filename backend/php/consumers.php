<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'mailer.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

/* ================= GET ================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    // get_consumer_info (get only the consumer name and meter_no) ✅ FIXED
    if ($action === 'get_consumer_info') {
        $stmt = $conn->prepare("
            SELECT name, meter_no 
            FROM consumers 
            WHERE user_id = ? AND is_deleted = 0
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $consumer = $result->fetch_assoc();

        if (!$consumer) {
            echo json_encode(['status' => 'error', 'message' => 'Consumer not found']);
            exit;
        }

        echo json_encode(['status' => 'success', 'data' => $consumer]);
        exit;
    }


    if ($action === 'get_all') {

        $sql = "SELECT c.id, c.name, c.address, c.contact, c.meter_no,
                       c.category_id,
                       IFNULL(cat.name, 'Uncategorized') AS category_name
                FROM consumers c
                LEFT JOIN categories cat ON c.category_id = cat.id
                WHERE c.is_deleted = 0";
    
        $result = $conn->query($sql);
    
        if (!$result) {
            echo json_encode([
                "status" => "error",
                "message" => $conn->error
            ]);
            exit;
        }
    
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    
        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
        exit;
    }

    if ($action === 'get_by_id') {

        $id = (int)($_GET['id'] ?? 0);
    
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            exit;
        }
    
        $stmt = $conn->prepare("
            SELECT 
                c.id, 
                c.name, 
                c.address, 
                c.contact, 
                c.meter_no, 
                c.category_id,
                c.user_id,
                COALESCE(u.email, '') AS email
            FROM consumers c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = ? 
            AND c.is_deleted = 0
            LIMIT 1
        ");
    
        $stmt->bind_param("i", $id);
        $stmt->execute();
    
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
    
        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Consumer not found']);
            exit;
        }
    
        echo json_encode([
            'status' => 'success',
            'data' => $row
        ]);
        exit;
    }
    
    // GET DELETED CONSUMERS ✅ FIXED
    if ($action === 'get_deleted') {
        $stmt = $conn->prepare("
            SELECT c.id, c.name, c.address, c.contact, c.meter_no,
                   IFNULL(cat.name, 'Uncategorized') AS category_name,
                   c.deleted_at
            FROM consumers c
            LEFT JOIN categories cat ON c.category_id = cat.id
            WHERE c.is_deleted = 1
            ORDER BY c.deleted_at DESC
        ");
    
        $stmt->execute();
        $result = $stmt->get_result();
    
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }
}

/* ================= POST ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {

        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
    
        if (empty($name) || empty($address)) {
            echo json_encode(['status' => 'error', 'message' => 'Name and Address required']);
            exit;
        }
    
        $username = strtolower(str_replace(' ', '_', $name));
    
        $conn->begin_transaction();
    
        try {
    
            // 1. CREATE USER
            // $password = password_hash("123456", PASSWORD_DEFAULT);
            $passwordPlain = bin2hex(random_bytes(4)); // 8-character random password
            $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);

            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Email already exists'
                ]);
                exit;
            }
    
            $stmtUser = $conn->prepare("
                INSERT INTO users (username, password, email, role)
                VALUES (?, ?, ?, 'customer')
            ");
            $stmtUser->bind_param("sss", $username, $passwordHash, $email);
            $stmtUser->execute();
    
            $user_id = $stmtUser->insert_id;
    
            // 2. CREATE CONSUMER (WITHOUT meter_no FIRST)
            $stmt = $conn->prepare("
                INSERT INTO consumers (name, address, contact, category_id, user_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssii", $name, $address, $contact, $category_id, $user_id);
            $stmt->execute();
    
            $consumer_id = $stmt->insert_id;
    
            // 3. GENERATE METER NO
            $meter_no = "MTR-" . str_pad($consumer_id, 6, "0", STR_PAD_LEFT);
    
            // 4. UPDATE CONSUMER WITH METER NO
            $update = $conn->prepare("
                UPDATE consumers 
                SET meter_no = ? 
                WHERE id = ?
            ");
            $update->bind_param("si", $meter_no, $consumer_id);
            $update->execute();
    
            $conn->commit();

            sendCredentialsEmail($email, $username, $passwordPlain, $meter_no);
    
            echo json_encode([
                'status' => 'success',
                'message' => 'Created successfully!',
                'meter_no' => $meter_no
            ]);
    
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    
        exit;
    }

    // UPDATE FULLY FIXED
    if ($action === 'update') {

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $meter_no = trim($_POST['meter_no'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
    
        if ($id <= 0 || empty($name) || empty($address) || empty($meter_no)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ID, Name, Address, and Meter No required'
            ]);
            exit;
        }
    
        // 🔥 GET consumer user_id
        $getUser = $conn->prepare("SELECT user_id FROM consumers WHERE id = ?");
        $getUser->bind_param("i", $id);
        $getUser->execute();
        $res = $getUser->get_result();
        $row = $res->fetch_assoc();
    
        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Consumer not found']);
            exit;
        }
    
        $consumer_user_id = $row['user_id'];
    
        // UPDATE consumers
        $stmt = $conn->prepare("
            UPDATE consumers 
            SET name=?, address=?, contact=?, meter_no=?, category_id=?
            WHERE id=?
        ");
        $stmt->bind_param("ssssii", $name, $address, $contact, $meter_no, $category_id, $id);
        $success = $stmt->execute();
    
        // UPDATE users email (ONLY ONCE)
        if ($success) {
            $updateUser = $conn->prepare("
                UPDATE users 
                SET email = ?
                WHERE id = ?
            ");
            $updateUser->bind_param("si", $email, $consumer_user_id);
            $updateUser->execute();
        }
    
        echo json_encode([
            'status' => $success ? 'success' : 'error',
            'message' => $success ? 'Updated successfully!' : $stmt->error,
            'affected_rows' => $stmt->affected_rows ?? 0
        ]);
    
        exit;
    }

   
    // consumers.php
    if ($action === 'delete') {

        $id = intval($_POST['id'] ?? 0);
        $inputPassword = $_POST['password'] ?? '';
    
        if ($id <= 0 || empty($inputPassword)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request'
            ]);
            exit;
        }
    
        // Verify admin password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
    
        if (!$user || !password_verify($inputPassword, $user['password'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Incorrect password'
            ]);
            exit;
        }
    
        // Soft delete WITH user protection
        $stmt = $conn->prepare("
            UPDATE consumers 
            SET is_deleted = 1, deleted_at = NOW() 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
    
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Consumer deleted successfully'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Delete failed or not authorized'
            ]);
        }
    
        exit; 
    }

    // RESTORE CONSUMER
    if ($action === 'restore') {
        $id = intval($_POST['id'] ?? 0);
    
        $stmt = $conn->prepare("
            UPDATE consumers 
            SET is_deleted = 0, deleted_at = NULL 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
    
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Consumer restored']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Restore failed']);
        }
        exit;
    }

    // FORCE DELETE CONSUMER
    if ($action === 'force_delete') {
        $id = intval($_POST['id'] ?? 0);
    
        $stmt = $conn->prepare("
            DELETE FROM consumers 
            WHERE id = ? AND is_deleted = 1
        ");
        $stmt->bind_param("i", $id);
    
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Permanently deleted']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delete failed']);
        }
        exit;
    }

    // Invalid action
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>