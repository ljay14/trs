<?php
// Start session
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logout.php");
    exit;
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get form fields
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $school_id = mysqli_real_escape_string($conn, $_POST['school_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    // Update adviser in the database
    $sql = "UPDATE student SET fullname = ?, department = ?, password = ?, confirm_password = ?  WHERE school_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $fullname, $department, $password, $confirm_password, $school_id);

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