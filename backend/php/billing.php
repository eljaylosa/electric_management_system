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
            $consumer_id = $_SESSION['consumer_id'];

            $stmt = $conn->prepare("
                SELECT b.id,
                       c.name AS consumer_name,
                       c.meter_no,
                       r.curr_reading,
                       r.prev_reading,
                       b.amount,
                       b.due_date,
                       b.status
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
                       IFNULL(c.meter_no, '-') AS meter_no,
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

    /* ================= PAYMENTS (FIXED) ================= */
    if (isset($_GET['action']) && $_GET['action'] === 'get_all_payments') {

        $stmt = $conn->prepare("
            SELECT 
                p.id,
                c.name AS consumer_name,
                b.amount,
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
}

/* =========================
   POST REQUESTS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

            $prev_reading = $_POST['prev_reading'];
            $curr_reading = $_POST['curr_reading'];

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
                    INSERT INTO bills (reading_id, amount, due_date)
                    VALUES (?, ?, ?)
                ");
                $stmt2->bind_param("ids", $reading_id, $amount, $due_date);

                if ($stmt2->execute()) {
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

        // GET BILL AMOUNT FIRST
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

        // INSERT PAYMENT
        $stmt = $conn->prepare("
            INSERT INTO payments (bill_id, amount_paid, payment_date)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("ids", $bill_id, $amount_paid, $payment_date);

        if ($stmt->execute()) {

            // UPDATE BILL STATUS
            $stmt2 = $conn->prepare("UPDATE bills SET status = 'paid' WHERE id = ?");
            $stmt2->bind_param("i", $bill_id);

            if ($stmt2->execute()) {
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

    // get recent activities
    if (isset($_POST['action']) && $_POST['action'] === 'get_recent_activities') {

        $stmt = $conn->prepare("
            SELECT 
                'payment' AS type,
                c.name AS consumer_name,
                b.amount,
                p.payment_date AS date,
                'success' AS status
            FROM payments p
            JOIN bills b ON p.bill_id = b.id
            JOIN readings r ON b.reading_id = r.id
            JOIN consumers c ON r.consumer_id = c.id
    
            UNION ALL
    
            SELECT 
                'bill' AS type,
                c.name AS consumer_name,
                b.amount,
                b.due_date AS date,
                b.status AS status
            FROM bills b
            JOIN readings r ON b.reading_id = r.id
            JOIN consumers c ON r.consumer_id = c.id
    
            ORDER BY date DESC
            LIMIT 5
        ");
    
        $stmt->execute();
        $result = $stmt->get_result();
    
        $activities = [];
    
        while ($row = $result->fetch_assoc()) {
    
            $activities[] = [
                "description" => ucfirst($row['type']) . " - " . $row['consumer_name'] . " (₱" . $row['amount'] . ")",
                "date" => $row['date'],
                "status" => $row['status']
            ];
        }
    
        echo json_encode([
            'status' => 'success',
            'data' => $activities
        ]);
    
        $stmt->close();
        exit;
    }
        
}
?>