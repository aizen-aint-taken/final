<?php
session_start();

// Check authentication
if (!isset($_SESSION['usertype']) || !in_array($_SESSION['usertype'], ['a', 'sa'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include('../config/conn.php');

header('Content-Type: application/json');

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    switch ($_POST['action']) {
        case 'fetch_deliveries':
            try {
                $stmt = $conn->query("SELECT * FROM library_deliveries ORDER BY DeliveryID DESC");
                $deliveries = $stmt->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $deliveries]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            break;

        case 'add_delivery':
            try {
                // Validate required fields
                $required_fields = ['title_and_grade_level', 'quantity_delivered', 'quantity_allocated', 'date_of_delivery', 'name_of_school_delivery_site'];
                foreach ($required_fields as $field) {
                    if (empty($_POST[$field]) && $_POST[$field] !== '0') {
                        echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $field]);
                        exit;
                    }
                }

                $title_grade = trim($_POST['title_and_grade_level']);
                $qty_delivered = (int)$_POST['quantity_delivered'];
                $qty_allocated = (int)$_POST['quantity_allocated'];
                $delivery_date = $_POST['date_of_delivery'];
                $delivery_site = trim($_POST['name_of_school_delivery_site']);

                // Validate date format
                $date_obj = DateTime::createFromFormat('Y-m-d', $delivery_date);
                if (!$date_obj || $date_obj->format('Y-m-d') !== $delivery_date) {
                    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
                    exit;
                }

                $stmt = $conn->prepare("INSERT INTO library_deliveries (title_and_grade_level, quantity_delivered, quantity_allocated, date_of_delivery, name_of_school_delivery_site) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("siiss", $title_grade, $qty_delivered, $qty_allocated, $delivery_date, $delivery_site);

                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => '✅ Delivery record added successfully', 'id' => $conn->insert_id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add delivery record: ' . $stmt->error]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'update_delivery':
            try {
                // Validate required fields
                if (empty($_POST['delivery_id']) || empty($_POST['title_and_grade_level'])) {
                    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                    exit;
                }

                $delivery_id = (int)$_POST['delivery_id'];
                $title_grade = trim($_POST['title_and_grade_level']);
                $qty_delivered = (int)$_POST['quantity_delivered'];
                $qty_allocated = (int)$_POST['quantity_allocated'];
                $delivery_date = $_POST['date_of_delivery'];
                $delivery_site = trim($_POST['name_of_school_delivery_site']);

                // Validate date format if provided
                if (!empty($delivery_date)) {
                    $date_obj = DateTime::createFromFormat('Y-m-d', $delivery_date);
                    if (!$date_obj || $date_obj->format('Y-m-d') !== $delivery_date) {
                        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
                        exit;
                    }
                }

                $stmt = $conn->prepare("UPDATE library_deliveries SET title_and_grade_level=?, quantity_delivered=?, quantity_allocated=?, date_of_delivery=?, name_of_school_delivery_site=? WHERE DeliveryID=?");
                $stmt->bind_param("siissi", $title_grade, $qty_delivered, $qty_allocated, $delivery_date, $delivery_site, $delivery_id);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true, 'message' => '✅ Delivery record updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'No changes made or record not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update delivery record: ' . $stmt->error]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'delete_delivery':
            try {
                if (empty($_POST['delivery_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Missing delivery ID']);
                    exit;
                }

                $delivery_id = (int)$_POST['delivery_id'];

                $stmt = $conn->prepare("DELETE FROM library_deliveries WHERE DeliveryID=?");
                $stmt->bind_param("i", $delivery_id);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        echo json_encode(['success' => true, 'message' => '🗑️ Delivery record deleted successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Record not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete delivery record: ' . $stmt->error]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_delivery':
            try {
                if (empty($_POST['delivery_id'])) {
                    echo json_encode(['success' => false, 'message' => 'Missing delivery ID']);
                    exit;
                }

                $delivery_id = (int)$_POST['delivery_id'];

                $stmt = $conn->prepare("SELECT * FROM library_deliveries WHERE DeliveryID=?");
                $stmt->bind_param("i", $delivery_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $delivery = $result->fetch_assoc();
                    echo json_encode(['success' => true, 'data' => $delivery]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Delivery record not found']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        case 'get_stats':
            try {
                // Get total deliveries
                $total_stmt = $conn->query("SELECT COUNT(*) as total FROM library_deliveries");
                $total_result = $total_stmt->fetch_assoc();

                // Get total quantity delivered
                $qty_stmt = $conn->query("SELECT SUM(quantity_delivered) as total_delivered, SUM(quantity_allocated) as total_allocated FROM library_deliveries");
                $qty_result = $qty_stmt->fetch_assoc();

                // Get deliveries by site
                $site_stmt = $conn->query("SELECT name_of_school_delivery_site, COUNT(*) as count FROM library_deliveries GROUP BY name_of_school_delivery_site ORDER BY count DESC");
                $site_data = [];
                while ($row = $site_stmt->fetch_assoc()) {
                    $site_data[] = $row;
                }

                $stats = [
                    'total_deliveries' => $total_result['total'],
                    'total_delivered' => $qty_result['total_delivered'] ?? 0,
                    'total_allocated' => $qty_result['total_allocated'] ?? 0,
                    'by_site' => $site_data
                ];

                echo json_encode(['success' => true, 'data' => $stats]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
