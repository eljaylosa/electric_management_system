<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

/* =========================
   GET REQUESTS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    /* ================= BILLS ================= */
    if (isset($_GET['action']) && $_GET['action'] === 'get_all_bills') {

        if (isCustomer()) {
            $user_id = $_SESSION['user_id'];

            $stmt = $conn->prepare("
                SELECT b.id,
                    c.name AS consumer_name,
                    c.meter_no,
                    c.address,
                    r.curr_reading,
                    r.prev_reading,
                    b.amount,
                    b.due_date,
                    b.status
                FROM bills b
                JOIN readings r ON b.reading_id = r.id
                JOIN consumers c ON r.consumer_id = c.id
                JOIN users u ON c.user_id = u.id
                WHERE u.id = ?
            ");
            $stmt->bind_param("i", $user_id);

        } else {
            // ✅ FIXED: Added meter_no & address for receipts
            $stmt = $conn->prepare("
                SELECT b.id,
                       IFNULL(c.name, 'Unknown') AS consumer_name,
                       IFNULL(c.meter_no, '-') AS meter_no,
                       IFNULL(c.address, 'N/A') AS address,
                       IFNULL(r.curr_reading, 0) AS curr_reading,
                       IFNULL(r.prev_reading, 0) AS prev_reading,
                       b.amount,
                       b.due_date,
                       b.status
                FROM bills b
                LEFT JOIN readings r ON b.reading_id = r.id
                LEFT JOIN consumers c ON r.consumer_id = c.id
            ");
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $bills = [];
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }

        echo json_encode(['status' => 'success', 'data' => $bills]);
        $stmt->close();
        exit;
    }

    /* ================= BILL BY ID ================= */
    if (isset($_GET['action']) && $_GET['action'] === 'get_bill_by_id' && isset($_GET['id'])) {

        $stmt = $conn->prepare("
            SELECT b.id,
                   c.name AS consumer_name,
                   c.address,
                   c.meter_no,
                   r.curr_reading,
                   r.prev_reading,
                   b.amount,
                   b.due_date,
                   b.status
            FROM bills b
            JOIN readings r ON b.reading_id = r.id
            JOIN consumers c ON r.consumer_id = c.id
            WHERE b.id = ?
        ");

        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            echo json_encode(['status' => 'success', 'data' => $result->fetch_assoc()]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Bill not found']);
        }

        $stmt->close();
        exit;
    }

    /* ================= RECENT BILLS ================= */
    if (isset($_GET['action']) && $_GET['action'] === 'get_recent_bills') {

        $stmt = $conn->prepare("
            SELECT b.id,
                   c.name AS consumer_name,
                   b.amount,
                   b.due_date,
                   b.status
            FROM bills b
            JOIN readings r ON b.reading_id = r.id
            JOIN consumers c ON r.consumer_id = c.id
            ORDER BY b.due_date DESC
            LIMIT 5
        ");

        $stmt->execute();
        $result = $stmt->get_result();

        $bills = [];
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }

        echo json_encode(['status' => 'success', 'data' => $bills]);
        $stmt->close();
        exit;
    }

    /* ================= PAYMENTS ================= */
    if (isset($_GET['action']) && $_GET['action'] === 'get_all_payments') {

        $stmt = $conn->prepare("
            SELECT 
                p.*,
                c.name AS consumer_name,
                b.amount,
                b.id AS bill_id,
                p.amount_paid,
                p.payment_date
            FROM payments p
            JOIN bills b ON p.bill_id = b.id
            JOIN readings r ON b.reading_id = r.id
            JOIN consumers c ON r.consumer_id = c.id
            ORDER BY p.payment_date DESC
        ");

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);

        $stmt->close();
        exit;
    } 

    // get due consumers bills for dashboard 
    if (isset($_GET['action']) && $_GET['action'] === 'get_due_bills') {

        $stmt = $conn->prepare("
            SELECT DISTINCT c.id,
                c.name AS consumer_name
            FROM bills b
            JOIN readings r ON b.reading_id = r.id
            JOIN consumers c ON r.consumer_id = c.id
            WHERE b.status = 'unpaid' AND b.due_date < CURDATE()
        ");

        $stmt->execute();
        $result = $stmt->get_result();

        $bills = [];
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }

        echo json_encode(['status' => 'success', 'data' => $bills]);
        $stmt->close();
        exit;
    }
    
    
}

/* =========================
   POST REQUESTS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // back up
    /* ================= ADD READING ================= */
    if (isset($_POST['action']) && $_POST['action'] === 'add_reading') {

        $consumer_id = $_POST['consumer_id'];
        $prev_reading = $_POST['prev_reading'];
        $curr_reading = $_POST['curr_reading'];
        $reading_date = $_POST['reading_date'];
        $due_date = $_POST['due_date'];

        $stmt = $conn->prepare("
            SELECT cat.rate_per_kwh
            FROM consumers c
            JOIN categories cat ON c.category_id = cat.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $consumer_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $rate = $result->fetch_assoc()['rate_per_kwh'];

            if ($curr_reading < $prev_reading) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Current reading cannot be lower than previous reading'
                ]);
                exit;
            }

            $amount = ($curr_reading - $prev_reading) * $rate;

            $stmt = $conn->prepare("
                INSERT INTO readings (consumer_id, prev_reading, curr_reading, reading_date)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("idds", $consumer_id, $prev_reading, $curr_reading, $reading_date);

            if ($stmt->execute()) {

                $reading_id = $stmt->insert_id;

                $stmt2 = $conn->prepare("
                    INSERT INTO bills (reading_id, amount, due_date, status)
                    VALUES (?, ?, ?, 'unpaid')
                ");
                $stmt2->bind_param("ids", $reading_id, $amount, $due_date);

                if ($stmt2->execute()) {
                    require_once 'notification_helper.php';

                    $getUser = $conn->prepare("SELECT user_id FROM consumers WHERE id = ?");
                    $getUser->bind_param("i", $consumer_id);
                    $getUser->execute();

                    // $receiver_id = $userResult['user_id'];
                    // $sender_id = $_SESSION['user_id'];

                    $userResult = $getUser->get_result()->fetch_assoc();
                    $receiver_id = $userResult['user_id'];

                    sendNotification(
                        $conn,
                        $sender_id = $_SESSION['user_id'] ?? null,
                        $receiver_id,
                        "New bill generated. Amount: ₱" . number_format($amount, 2),
                        "billing"
                    );  

                    // sendNotification(
                    //     $conn,
                    //     $sender_id,
                    //     $receiver_id,
                    //     "New bill generated. Amount: ₱" . number_format($amount, 2),
                    //     "bill"
                    // );

                    echo json_encode(['status' => 'success', 'message' => 'Reading and bill created']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Bill creation failed']);
                }

                $stmt2->close();

            } else {
                echo json_encode(['status' => 'error', 'message' => 'Reading insert failed']);
            }

        } else {
            echo json_encode(['status' => 'error', 'message' => 'Consumer category not found']);
        }

        $stmt->close();
        exit;
    }

    /* ================= RECORD PAYMENT ================= */
    if (isset($_POST['action']) && $_POST['action'] === 'record_payment') {

        $bill_id = $_POST['bill_id'];
        $payment_date = $_POST['payment_date'];

        $stmt = $conn->prepare("SELECT amount FROM bills WHERE id = ?");
        $stmt->bind_param("i", $bill_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Bill not found']);
            exit;
        }

        $row = $result->fetch_assoc();
        $amount_paid = $row['amount'];

        $stmt->close();

        $stmt = $conn->prepare("
            INSERT INTO payments (bill_id, amount_paid, payment_date)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("ids", $bill_id, $amount_paid, $payment_date);

        if ($stmt->execute()) {

            $stmt2 = $conn->prepare("UPDATE bills SET status = 'paid' WHERE id = ?");
            $stmt2->bind_param("i", $bill_id);

            if ($stmt2->execute()) {

                require_once 'notification_helper.php';

                $getUser = $conn->prepare("
                    SELECT u.id AS user_id
                    FROM consumers c
                    JOIN users u ON c.user_id = u.id
                    JOIN readings r ON c.id = r.consumer_id
                    JOIN bills b ON r.id = b.reading_id
                    WHERE b.id = ?
                ");
                $getUser->bind_param("i", $bill_id);
                $getUser->execute();
                $userResult = $getUser->get_result()->fetch_assoc();

                $sender_id = $userResult['user_id'];

                $admin = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();
                $receiver_id = $admin['id'];

                sendNotification(
                    $conn,
                    $sender_id,
                    $receiver_id,
                    "Payment has been made for Bill ID: " . $bill_id,
                    "payment"
                );

                echo json_encode(['status' => 'success', 'message' => 'Payment recorded']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Bill update failed']);
            }

            $stmt2->close();

        } else {
            echo json_encode(['status' => 'error', 'message' => 'Payment failed']);
        }

        $stmt->close();
        exit;
    }
}
?>