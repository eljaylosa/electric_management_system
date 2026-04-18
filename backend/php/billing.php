<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Billing and Readings API
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all bills
    if (isset($_GET['action']) && $_GET['action'] === 'get_all_bills') {
        if (isCustomer()) {
            $consumer_id = $_SESSION['consumer_id'];
            $stmt = $conn->prepare("SELECT b.id, c.name AS consumer_name, r.curr_reading, r.prev_reading, b.amount, b.due_date, b.status FROM bills b JOIN readings r ON b.reading_id = r.id JOIN consumers c ON r.consumer_id = c.id WHERE c.id = ?");
            $stmt->bind_param("i", $consumer_id);
        } else {
            $stmt = $conn->prepare("SELECT b.id, IFNULL(c.name, 'Unknown') AS consumer_name, IFNULL(r.curr_reading, 0) AS curr_reading, IFNULL(r.prev_reading, 0) AS prev_reading, b.amount, b.due_date, b.status FROM bills b LEFT JOIN readings r ON b.reading_id = r.id LEFT JOIN consumers c ON r.consumer_id = c.id");
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

    // Get a single bill by ID
    if (isset($_GET['action']) && $_GET['action'] === 'get_bill_by_id' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $conn->prepare("SELECT b.id, c.name AS consumer_name, c.address, c.meter_no, r.curr_reading, r.prev_reading, b.amount, b.due_date, b.status FROM bills b JOIN readings r ON b.reading_id = r.id JOIN consumers c ON r.consumer_id = c.id WHERE b.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $bill = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'data' => $bill]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Bill not found']);
        }
        $stmt->close();
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add a new reading and generate a bill
    if (isset($_POST['action']) && $_POST['action'] === 'add_reading') {
        $consumer_id = $_POST['consumer_id'];
        $prev_reading = $_POST['prev_reading'];
        $curr_reading = $_POST['curr_reading'];
        $reading_date = $_POST['reading_date'];
        $due_date = $_POST['due_date'];

        // Get rate per kWh for the consumer's category
        $stmt = $conn->prepare("SELECT cat.rate_per_kwh FROM consumers c JOIN categories cat ON c.category_id = cat.id WHERE c.id = ?");
        $stmt->bind_param("i", $consumer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $category = $result->fetch_assoc();
            $rate_per_kwh = $category['rate_per_kwh'];
            $amount = ($curr_reading - $prev_reading) * $rate_per_kwh;

            // Insert reading
            $stmt = $conn->prepare("INSERT INTO readings (consumer_id, prev_reading, curr_reading, reading_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("idds", $consumer_id, $prev_reading, $curr_reading, $reading_date);
            if ($stmt->execute()) {
                $reading_id = $stmt->insert_id;

                // Insert bill
                $stmt = $conn->prepare("INSERT INTO bills (reading_id, amount, due_date) VALUES (?, ?, ?)");
                $stmt->bind_param("ids", $reading_id, $amount, $due_date);
                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'Reading added and bill generated successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to generate bill']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to add reading']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Consumer category not found']);
        }
        $stmt->close();
        exit;
    }

    // Record a payment
    if (isset($_POST['action']) && $_POST['action'] === 'record_payment') {
        $bill_id = $_POST['bill_id'];
        $amount_paid = $_POST['amount_paid'];
        $payment_date = $_POST['payment_date'];

        // Insert payment
        $stmt = $conn->prepare("INSERT INTO payments (bill_id, amount_paid, payment_date) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $bill_id, $amount_paid, $payment_date);
        if ($stmt->execute()) {
            // Update bill status to 'paid'
            $stmt = $conn->prepare("UPDATE bills SET status = 'paid' WHERE id = ?");
            $stmt->bind_param("i", $bill_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Payment recorded and bill status updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update bill status']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to record payment']);
        }
        $stmt->close();
        exit;
    }
}

// receipt API
if ($_GET['action'] === 'get_bill_by_id' && isset($_GET['id'])) {
    $id = $_GET['id'];

    $query = "SELECT b.*, c.name AS consumer_name, c.meter_no 
              FROM bills b
              JOIN consumers c ON b.consumer_id = c.id
              WHERE b.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'status' => 'success',
            'data' => $row
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Bill not found'
        ]);
    }
}
?>
