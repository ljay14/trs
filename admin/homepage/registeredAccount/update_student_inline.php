<?php
// Start session
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logout.php");
    exit;
}

// Database connection
include '../../../connection.php';

// Get form data
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form fields
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $school_id = mysqli_real_escape_string($conn, $_POST['school_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if passwords match
    if ($password != $confirm_password) {
        echo "Passwords do not match.";
        exit;
    }

    // Check if password was provided
    if (!empty($password)) {
        // Update student with password
        $sql = "UPDATE student SET fullname = ?, department = ?, password = ?, confirm_password = ?, email = ? WHERE school_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $fullname, $department, $password, $confirm_password, $email, $school_id);
    } else {
        // Update student without changing password
        $sql = "UPDATE student SET fullname = ?, department = ?, email = ? WHERE school_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $fullname, $department, $email, $school_id);
    }

    if ($stmt->execute()) {
        // Redirect with success message
        header("Location: student_register.php?status=success");
        exit;
    } else {
        echo "Error: " . $stmt->error;
        exit;
    }
}

// Close the connection
$stmt->close();
$conn->close();
?>