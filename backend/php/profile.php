<?php

session_start();
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

/* ================= SAFE ID ================= */
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Session missing']);
    exit;
}

/* ================= GET PROFILE ================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['action']) &&
    $_GET['action'] === 'get_profile'
) {

    $stmt = $conn->prepare("
        SELECT c.name, c.address, c.meter_no, c.profile_pic, u.email
        FROM consumers c
        JOIN users u ON c.user_id = u.id
        WHERE u.id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $stmt->bind_result($name, $address, $meter_no, $profile_pic, $email);

    if (!$stmt->fetch()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No profile found'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'name' => $name,
            'address' => $address,
            'meter_no' => $meter_no,
            'profile_pic' => $profile_pic,
            'email' => $email
        ]
    ]);
    exit;
}

/* ================= UPDATE PROFILE ================= */
if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['action']) &&
  $_POST['action'] === 'update_profile'
) {

  $name = $_POST['name'] ?? '';
  $address = $_POST['address'] ?? '';
  $email = $_POST['email'] ?? '';
  $username = $_POST['username'] ?? null;

  if (empty($name) || empty($email)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Name and Email required'
    ]);
    exit;
}

  $conn->begin_transaction();

  try {

      /* ================= consumers ================= */
        $stmt1 = $conn->prepare("
        UPDATE consumers 
        SET name=?, address=? 
        WHERE user_id=?
        ");

        $stmt1->bind_param("ssi", $name, $address, $user_id);

        if (!$stmt1->execute()) {
            throw new Exception($stmt1->error);
        }

      
      /* ================= users ================= */
        $stmt2 = $conn->prepare("
        UPDATE users 
        SET email=?
        WHERE id=?
        ");

        if (empty($email)) {
            throw new Exception("Email required");
        }

        $stmt2->bind_param("ss", $email, $user_id);

        if (!$stmt2->execute()) {
            throw new Exception($stmt2->error);
        }

        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Profile updated'
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

/* ================= CHANGE PASSWORD ================= */
if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['action']) &&
  $_POST['action'] === 'change_password'
) {

    $old = $_POST['old'] ?? '';
    $newPlain = $_POST['new'] ?? '';

    if (!$user_id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid session']);
        exit;
    }

    if (empty($old) || empty($newPlain)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
        exit;
    }

    /* ================= GET PASSWORD FROM USERS ================= */
    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $user_id);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        exit;
    }

    $stmt->bind_result($db_password);

    if (!$stmt->fetch() || !$db_password) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    $stmt->close();

    /* ================= VERIFY OLD PASSWORD ================= */
    if (!password_verify($old, $db_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Wrong password']);
        exit;
    }

    /* ================= UPDATE USERS TABLE ================= */
    $newHash = password_hash($newPlain, PASSWORD_DEFAULT);

    $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE id=?");

    if (!$stmt2) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $stmt2->bind_param("si", $newHash, $user_id);

    if (!$stmt2->execute()) {
        echo json_encode(['status' => 'error', 'message' => $stmt2->error]);
        exit;
    }

    $stmt2->close();

    echo json_encode(['status' => 'success', 'message' => 'Password updated']);
    exit;
}

/* ================= UPLOAD PROFILE PIC ================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'upload_pic'
) {

    if (!isset($_FILES['image'])) {
        echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
        exit;
    }

    $fileName = time() . "_" . basename($_FILES['image']['name']);
    $target = "../../uploads/" . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {

        $stmt = $conn->prepare("
            UPDATE consumers 
            SET profile_pic=? 
            WHERE user_id=?
        ");
        $stmt->bind_param("si", $fileName, $user_id);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Uploaded']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
    }

    exit;
}

/* ================= SUMMARY ================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['action']) &&
    $_GET['action'] === 'summary'
) {

    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_bills,
            SUM(CASE WHEN b.status='paid' THEN b.amount ELSE 0 END) as paid,
            SUM(CASE WHEN b.status='unpaid' THEN b.amount ELSE 0 END) as unpaid
        FROM bills b
        JOIN readings r ON b.reading_id = r.id
        JOIN consumers c ON r.consumer_id = c.id
        WHERE c.user_id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $stmt->bind_result($total_bills, $paid, $unpaid);
    $stmt->fetch();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_bills' => $total_bills ?? 0,
            'paid' => $paid ?? 0,
            'unpaid' => $unpaid ?? 0
        ]
    ]);

    exit;
}

// get username in profile section
if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['action']) &&
    $_GET['action'] === 'get_username'
) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($username);
    if ($stmt->fetch()) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'username' => $username
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
    exit;
}

// add update username in update profile section
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'update_username'
) {
    $username = $_POST['username'] ?? null;

    if (!$username) {
        echo json_encode(['status' => 'error', 'message' => 'Username required']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET username=? WHERE id=?");
    $stmt->bind_param("si", $username, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Username updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    exit;
}

?>