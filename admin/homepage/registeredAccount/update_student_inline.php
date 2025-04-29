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
    
    // Check if password fields are filled
    if (!empty($_POST['password']) && !empty($_POST['confirm_password'])) {
        // If passwords are provided, update everything including passwords
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
        
        // Update with passwords
        $sql = "UPDATE student SET fullname = ?, department = ?, password = ?, confirm_password = ? WHERE school_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $fullname, $department, $password, $confirm_password, $school_id);
    } else {
        // If no passwords provided, update only fullname and department
        $sql = "UPDATE student SET fullname = ?, department = ? WHERE school_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $fullname, $department, $school_id);
    }

    if ($stmt->execute()) {
        // Redirect with success message
        header("Location: student_register.php?status=success");
    } else {
        echo "Error: " . $stmt->error;
        exit;
    }
    
    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>