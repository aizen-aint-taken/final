<?php
require '../config/conn.php';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = intval($_POST['id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $sect = mysqli_real_escape_string($conn, $_POST['sect']);
    $advicer = mysqli_real_escape_string($conn, $_POST['advicer']);
    $new_email = filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL);

    if ($new_email === false) {
        die("Invalid email format.");
    }


    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($old_email);
    $stmt->fetch();
    $stmt->close();


    $conn->begin_transaction();

    try {

        $stmt = $conn->prepare("UPDATE `users` SET name=?, age=?, year=?, sect=?, advicer=?, email=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $age, $year, $sect, $advicer, $new_email, $id);
        $stmt->execute();

        // Update webuser table - both name and email if needed
        if ($old_email !== $new_email) {
            $stmt2 = $conn->prepare("UPDATE `webuser` SET email=?, name=? WHERE email=?");
            $stmt2->bind_param("sss", $new_email, $name, $old_email);
            $stmt2->execute();
        } else {
            // Just update the name if email hasn't changed
            $stmt2 = $conn->prepare("UPDATE `webuser` SET name=? WHERE email=?");
            $stmt2->bind_param("ss", $name, $old_email);
            $stmt2->execute();
        }

        $conn->commit();
        header("Location: ../admin/student.php");
        exit();
    } catch (Exception $e) {

        $conn->rollback();
        die("Error updating student: " . $e->getMessage());
    }
}
