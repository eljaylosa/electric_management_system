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
$id = $_SESSION['consumer_id'] ?? null;

if (!$id) {
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
        SELECT name, address, meter_no, profile_pic
        FROM consumers
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt->bind_result($name, $address, $meter_no, $profile_pic);

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
            'profile_pic' => $profile_pic
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
  $username = $_POST['username'] ?? null;

  $conn->begin_transaction();

  try {

      /* ================= consumers ================= */
      $stmt1 = $conn->prepare("
          UPDATE consumers 
          SET name=?, address=? 
          WHERE id=?
      ");

      if (!$stmt1) {
          throw new Exception($conn->error);
      }

      $stmt1->bind_param("ssi", $name, $address, $id);

      if (!$stmt1->execute()) {
          throw new Exception($stmt1->error);
      }

      /* ================= users ================= */
      if (!empty($username)) {

          $stmt2 = $conn->prepare("
              UPDATE users 
              SET username=? 
              WHERE consumer_id=?
          ");

          if (!$stmt2) {
              throw new Exception($conn->error);
          }

          $stmt2->bind_param("si", $username, $id);

          if (!$stmt2->execute()) {
              throw new Exception($stmt2->error);
          }
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

    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid session']);
        exit;
    }

    if (empty($old) || empty($newPlain)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing fields']);
        exit;
    }

    /* ================= GET PASSWORD FROM USERS ================= */
    $stmt = $conn->prepare("SELECT password FROM users WHERE consumer_id=?");

    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $id);

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

    $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE consumer_id=?");

    if (!$stmt2) {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit;
    }

    $stmt2->bind_param("si", $newHash, $id);

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
            WHERE id=?
        ");
        $stmt->bind_param("si", $fileName, $id);
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
            SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as paid,
            SUM(CASE WHEN status='unpaid' THEN amount ELSE 0 END) as unpaid
        FROM bills b
        JOIN readings r ON b.reading_id = r.id
        WHERE r.consumer_id = ?
    ");

    $stmt->bind_param("i", $id);
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
?>