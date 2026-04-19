<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Check login
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// ==========================
// GET REQUESTS
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (!isset($_GET['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'No action specified']);
        exit;
    }

    switch ($_GET['action']) {

        // 🔹 GET ALL BILLS
        case 'get_all_bills':

            if (isCustomer()) {
                $consumer_id = $_SESSION['consumer_id'];

                $stmt = $conn->prepare("
                    SELECT b.id, c.name AS consumer_name, 
                           r.curr_reading, r.prev_reading, 
                           b.amount, b.due_date, b.status
                    FROM bills b
                    JOIN readings r ON b.reading_id = r.id
                    JOIN consumers c ON r.consumer_id = c.id
                    WHERE c.id = ?
                ");
                $stmt->bind_param("i", $consumer_id);

            } else {
                $stmt = $conn->prepare("
                    SELECT b.id, 
                           IFNULL(c.name, 'Unknown') AS consumer_name,
                           IFNULL(r.curr_reading, 0) AS curr_reading,
                           IFNULL(r.prev_reading, 0) AS prev_reading,
                           b.amount, b.due_date, b.status
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


        // 🔹 GET BILL BY ID (WITH FULL DETAILS / RECEIPT)
        case 'get_bill_by_id':

            if (!isset($_GET['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Missing bill ID']);
                exit;
            }

            $id = $_GET['id'];

            $stmt = $conn->prepare("
                SELECT b.id, c.name AS consumer_name, 
                       c.address, c.meter_no,
                       r.curr_reading, r.prev_reading,
                       b.amount, b.due_date, b.status
                FROM bills b
                JOIN readings r ON b.reading_id = r.id
                JOIN consumers c ON r.consumer_id = c.id
                WHERE b.id = ?
            ");
            $stmt->bind_param("i", $id);

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                echo json_encode([
                    'status' => 'success',
                    'data' => $result->fetch_assoc()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Bill not found'
                ]);
            }

            $stmt->close();
            exit;


        // 🔹 GET RECENT BILLS (DASHBOARD)
        case 'get_recent_bills':

            $query = "
                SELECT b.id, c.name AS consumer_name, 
                       b.amount, b.due_date, b.status
                FROM bills b
                JOIN readings r ON b.reading_id = r.id
                JOIN consumers c ON r.consumer_id = c.id
                ORDER BY b.id DESC
                LIMIT 5
            ";

            $result = $conn->query($query);

            $bills = [];
            while ($row = $result->fetch_assoc()) {
                $bills[] = $row;
            }

            echo json_encode([
                'status' => 'success',
                'data' => $bills
            ]);
            exit;


        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            exit;
    }
}

// ==========================
// POST REQUESTS
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'No action specified']);
        exit;
    }

    switch ($_POST['action']) {

        // 🔹 ADD READING + GENERATE BILL
        case 'add_reading':

            $consumer_id = $_POST['consumer_id'];
            $prev_reading = $_POST['prev_reading'];
            $curr_reading = $_POST['curr_reading'];
            $reading_date = $_POST['reading_date'];
            $due_date = $_POST['due_date'];

            // ✅ VALIDATION
            if ($curr_reading < $prev_reading) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid reading values']);
                exit;
            }

            // Get rate
            $stmt = $conn->prepare("
                SELECT cat.rate_per_kwh
                FROM consumers c
                JOIN categories cat ON c.category_id = cat.id
                WHERE c.id = ?
            ");
            $stmt->bind_param("i", $consumer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows !== 1) {
                echo json_encode(['status' => 'error', 'message' => 'Consumer category not found']);
                exit;
            }

            $rate = $result->fetch_assoc()['rate_per_kwh'];
            $amount = ($curr_reading - $prev_reading) * $rate;

            // Insert reading
            $stmt = $conn->prepare("
                INSERT INTO readings (consumer_id, prev_reading, curr_reading, reading_date)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("idds", $consumer_id, $prev_reading, $curr_reading, $reading_date);

            if (!$stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to add reading']);
                exit;
            }

            $reading_id = $stmt->insert_id;

            // Insert bill
            $stmt = $conn->prepare("
                INSERT INTO bills (reading_id, amount, due_date, status)
                VALUES (?, ?, ?, 'unpaid')
            ");
            $stmt->bind_param("ids", $reading_id, $amount, $due_date);

            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Reading added and bill generated'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to generate bill']);
            }

            $stmt->close();
            exit;


        // 🔹 RECORD PAYMENT
        case 'record_payment':

            $bill_id = $_POST['bill_id'];
            $amount_paid = $_POST['amount_paid'];
            $payment_date = $_POST['payment_date'];

            // Insert payment
            $stmt = $conn->prepare("
                INSERT INTO payments (bill_id, amount_paid, payment_date)
                VALUES (?, ?, ?)
            ");
            $stmt->bind_param("ids", $bill_id, $amount_paid, $payment_date);

            if (!$stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to record payment']);
                exit;
            }

            // Update bill status
            $stmt = $conn->prepare("
                UPDATE bills SET status = 'paid' WHERE id = ?
            ");
            $stmt->bind_param("i", $bill_id);

            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Payment recorded successfully'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update bill']);
            }

            $stmt->close();
            exit;


        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            exit;
    }
}
?>