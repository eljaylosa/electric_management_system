<?php
session_start();
require_once 'config.php';
require_once 'mailer.php';

header('Content-Type: application/json');


// Login API
if ($_POST['action'] === 'login') {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // ✅ get consumer_id from consumers table
            $stmt = $conn->prepare("SELECT id FROM consumers WHERE user_id = ?");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $_SESSION['consumer_id'] = $row['id'];
            }

            echo json_encode([
                'status' => 'success',
                'role' => $user['role']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }

    exit;
}
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
//     $username = $_POST['username'];
//     $password = $_POST['password'];

//     $stmt = $conn->prepare("SELECT id, username, password, role, consumer_id FROM users WHERE username = ?");
//     $stmt->bind_param("s", $username);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result->num_rows === 1) {
//         $user = $result->fetch_assoc();
//         if (password_verify($password, $user['password'])) {
//             $_SESSION['user_id'] = $user['id'];
//             $_SESSION['username'] = $user['username'];
//             $_SESSION['role'] = $user['role'];
//             $_SESSION['consumer_id'] = $user['consumer_id'];
//             echo json_encode(['status' => 'success', 'message' => 'Login successful', 'role' => $user['role']]);
//         } else {
//             echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
//         }
//     } else {
//         echo json_encode(['status' => 'error', 'message' => 'User not found']);
//     }
//     $stmt->close();
//     exit;
// }

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
            $stmt_insert = $conn->prepare("
            INSERT INTO users (username, password, role) 
            VALUES (?, ?, 'customer')
            ");
            $stmt_insert->bind_param("ss", $username, $password);

            if ($stmt_insert->execute()) {
                $new_user_id = $stmt_insert->insert_id;
            
                // Link to consumer
                $stmt_link = $conn->prepare("
                    UPDATE consumers SET user_id = ? WHERE id = ?
                ");
                $stmt_link->bind_param("ii", $new_user_id, $consumer_id);
                $stmt_link->execute();
            
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Account created successfully. Please login.'
                ]);
            }
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Meter number not found. Please contact support.']);
    }
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'forgot_password') {

    

    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        echo json_encode(["status" => "error", "message" => "Email required"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Email not found"]);
        exit;
    }

    $user = $result->fetch_assoc();
    $user_id = $user['id'];

    $otp = strval(random_int(100000, 999999));
    $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    $stmt = $conn->prepare("
        UPDATE users 
        SET reset_otp = ?, otp_expiry = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $otp, $expiry, $user_id);
    $stmt->execute();

    // 🔥 DEBUG THIS
    $mailResult = sendOTPEmail($email, $otp);

    if (!$mailResult) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to send email (SMTP issue)"
        ]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "message" => "OTP sent successfully"
    ]);
    exit;
}

if ($_POST['action'] === 'verify_otp') {

    $email = $_POST['email'];
    $otp = $_POST['otp'];

    // 1. Get user first
    $stmt = $conn->prepare("SELECT id, reset_otp, otp_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }

    $user = $result->fetch_assoc();

    // 2. Check OTP match
    if ($user['reset_otp'] !== $otp) {
        echo json_encode(["status" => "error", "message" => "Invalid OTP"]);
        exit;
    }

    // 3. Check expiry
    if (strtotime($user['otp_expiry']) < time()) {
        echo json_encode(["status" => "error", "message" => "OTP expired"]);
        exit;
    }

    // 4. Mark OTP as used
    $stmt = $conn->prepare("UPDATE users SET reset_otp = NULL, otp_expiry = NULL WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();

    echo json_encode(["status" => "success", "message" => "OTP verified"]);
    exit;
}

if ($_POST['action'] === 'reset_password') {

    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE users 
        SET password = ?, reset_otp = NULL, otp_expiry = NULL
        WHERE email = ?
    ");
    $stmt->bind_param("ss", $password, $email);
    $stmt->execute();

    echo json_encode(["status" => "success", "message" => "Password updated"]);
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
