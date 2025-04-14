<?php
// Start the session
session_start();

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

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $school_id = $_POST['school_id'];
    $password = $_POST['password'];

    // Validate input
    if (empty($school_id) || empty($password)) {
        echo "<script>alert('School ID and Password are required!'); window.history.back();</script>";
    } else {
        // Prepare SQL query
        $stmt = $conn->prepare("SELECT * FROM adviser WHERE school_id = ?");
        $stmt->bind_param("s", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch adviser data
            $adviser = $result->fetch_assoc();

            // Verify password by direct comparison (since it's plain text now)
            if ($password === $adviser['password']) {
                // Store adviser details in session
                $_SESSION['adviser_id'] = $adviser['adviser_id']; // Store adviser ID
                $_SESSION['school_id'] = $adviser['school_id'];
                $_SESSION['fullname'] = $adviser['fullname'];

                // Redirect to adviser dashboard
                header("Location: homepage/homepage.php");
                exit();
            } else {
                echo "<script>alert('Incorrect password!'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Adviser not found!'); window.history.back();</script>";
        }

        $stmt->close();
    }
}

// Close connection
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
            <h2>Adviser</h2>
            <form action="login.php" method="POST">
                <input type="text" name="school_id" placeholder="School ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <button class="login-btn" type="submit">Login</button>
                <button class="back-btn" type="button" onclick="window.location.href='../index.php'">Back</button>
            </form>
        </div>
    </div>
</body>

</html>

