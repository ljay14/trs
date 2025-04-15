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
    <style>
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body */
body {
    display: flex;
    height: 100vh;
    font-family: Arial, sans-serif;
}

/* Left Panel */
.left-panel {
    background-color: #002366;
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    width: auto;
}

.left-panel img {
    max-width: 80px;
    margin-bottom: 20px;
}

.left-panel h1 {
    margin: 0;
    font-size: 30px;
    text-align: center;
}

.left-panel p {
    margin: 5px 0;
    text-align: center;
    font-size: 20px;
}

/* Right Panel */
.right-panel {
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #f9f9f9;
    width: 70%;
    height: 100%;
    padding: 20px;
}

/* Login Card */
.login-card {
    background: #d3d3d3;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
}

/* Form & Buttons */

input {
    padding: 15px;
    width: 100%;
    margin: 8px 0;
    border: none; 
    border-radius: 5px;
}

.login-btn,
.register-btn,
.back-btn {
    background-color: #4caf50;
    border: none;
    color: white;
    text-align: center;
    cursor: pointer;
    padding: 10px 20px;
    margin: 8px 0;
    border-radius: 5px;
    width: 100%;
    font-weight: bold;
    font-size: medium;
}

.right-panel h2 {
    margin-bottom: 10px;
    font-size: 24px;
    color: #333;
    text-align: center;
}



/* Responsive */
@media (max-width: 768px) {
    body {
        flex-direction: column;
    }

    .left-panel,
    .right-panel {
        width: 100%;
    }
}

    </style>
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