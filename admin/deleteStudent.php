<?php
require_once '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_id']) && !empty($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];

        $conn->begin_transaction();

        try {

            $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                throw new Exception("Student not found.");
            }

            $email = $result->fetch_assoc()['email'];


            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();

            if ($stmt->affected_rows == 0) {
                throw new Exception("Failed to delete student from users table.");
            }

            // Delete from webuser table
            $stmt = $conn->prepare("DELETE FROM webuser WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            if ($stmt->affected_rows == 0) {
                throw new Exception("No matching webuser found for deletion.");
            }


            $conn->commit();
            header('Location: ../admin/student.php?success=Student deleted successfully');
            exit;
        } catch (Exception $e) {

            $conn->rollback();
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "No student ID provided for deletion.";
    }
}
