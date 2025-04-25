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

    // Update adviser in the database
    $sql = "UPDATE adviser SET fullname = ?, department = ?, password = ? WHERE school_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $fullname, $department, $password, $school_id);

    if ($stmt->execute()) {
        // Redirect with success message
        header("Location: adviser_register.php?status=success");
    } else {
        echo "Error: " . $stmt->error;
        exit;
    }
    
    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
