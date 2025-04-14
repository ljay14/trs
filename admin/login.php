<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "trs"; // Replace with your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query to check if the user exists
    $sql = "SELECT * FROM admin WHERE username = '$username' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Fetch user details
        $admin = $result->fetch_assoc();

        // Set session variables
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['fullname'] = $admin['fullname'];

        // Redirect to admin dashboard
        header("Location: homepage/homepage.php");
        exit();
    } else {
        // Invalid credentials
        echo "<script>alert('Invalid Username or Password!'); window.location.href='login.php';</script>";
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
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="left-panel">
        <img src="../assets/logo.png" alt="Logo">
        <h1>Saint Michael College of Caraga</h1>
        <p>Brgy 4, Atupan St., Nasipit, Agusan del Norte</p>
    </div>

    <div class="right-panel">
        <div class="login-card">
            <h2>Admin</h2>
            <form action="login.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button class="login-btn" type="submit">Login</button>
                <button class="back-btn" type="button" onclick="window.location.href='../index.php'">Back</button>
            </form>
        </div>
    </div>
</body>
</html>
