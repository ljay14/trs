<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve and sanitize inputs
    $school_id = mysqli_real_escape_string($conn, $_POST['school_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query to fetch user details
    $sql = "SELECT * FROM student WHERE school_id = '$school_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // User exists, fetch data
        $row = $result->fetch_assoc();
        $stored_password = $row['password'];  // Plain-text password stored in database

        // Compare entered password with stored plain-text password
        if ($password === $stored_password) {
            // Set session variables
            $_SESSION['student_id'] = $row['student_id'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['school_id'] = $row['school_id'];

            // Redirect to homepage.html
            header("Location: homepage/homepage.php");
            exit;
        } else {
            echo "<script>alert('Invalid password!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('No account found with the provided ID number.'); window.history.back();</script>";
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Routing System</title>
    <link rel="stylesheet" href="stylelogin.css">
</head>

<body>
    <div class="left-panel">
        <img src="../assets/logo.png" alt="Logo">
        <h1>Saint Michael College of Caraga</h1>
        <p>Brgy 4, Atupan St., Nasipit, Agusan del Norte</p>
    </div>

    <div class="right-panel">
        <div class="login-card">
            <h2>Student</h2>
            <form action="login.php" method="POST">
                <input type="text" name="school_id" placeholder="ID number" required>
                <input type="password" name="password" placeholder="Password" required>
                <button class="login-btn" type="submit">Login</button>
                <button class="register-btn" type="button"
                    onclick="window.location.href='register.php'">Register</button>
                <button class="back-btn" type="button" onclick="window.location.href='../index.php'">Back</button>
            </form>

        </div>
    </div>
</body>
</html>