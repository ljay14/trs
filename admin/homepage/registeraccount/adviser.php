<?php
// Start the session
session_start(); // Start session to check admin login

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

// Check if form is submitted
// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $fullname = $_POST['fullname'];
    $department = $_POST['department'];
    $school_id = $_POST['school_id']; // Changed from username to school_id
    $password = $_POST['password']; // Get password as plain text

    // Input validation
    if (empty($fullname) || empty($department) || empty($school_id) || empty($password)) {
        echo "<script>alert('All fields are required!'); window.history.back();</script>";
    } else {
        // Sanitize input
        $fullname = mysqli_real_escape_string($conn, $fullname);
        $department = mysqli_real_escape_string($conn, $department);
        $school_id = mysqli_real_escape_string($conn, $school_id);
        $password = mysqli_real_escape_string($conn, $password);

        // SQL query to insert data into the database
        $sql = "INSERT INTO adviser (fullname, department, school_id, password) 
                VALUES ('$fullname', '$department', '$school_id', '$password')";

        if ($conn->query($sql) === TRUE) {
            // Redirect to the same page with a success status in the URL
            header("Location: adviser.php?status=success");
            exit();
        } else {
            echo "<script>alert('Error: " . addslashes($conn->error) . "'); window.history.back();</script>";
        }
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
    <link rel="stylesheet" href="styles.css">
    <script>
        // JavaScript to hide the alert initially and show it upon success
        window.onload = function () {
            var status = new URLSearchParams(window.location.search).get('status');
            if (status === 'success') {
                document.getElementById('success-alert').style.display = 'block';
            }
        }
    </script>
</head>

<body>
    <div class="container">
        <header class="header">
            <div class="logo-container">
                <img src="../../../assets/logo.png" alt="Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>
        <div class="top-bar">
            <div class="homepage">
                <a href="../../homepage/homepage.php">Home Page</a>
            </div>
            <div class="user-info">
                <span class="role">Admin:</span>
                <span
                    class="user-name"><?php echo isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Guest'; ?></span>
            </div>
        </div>
        <div class="main-content">
            <nav class="sidebar">
                <div class="menu">
                <div class="menu-section">
                        <div class="menu-title">Research Proposal</div>
                        <ul>
                            <li><a href="../titleproposal/route1.php">Route 1</a></li>
                            <li><a href="../titleproposal/route2.php">Route 2</a></li>
                            <li><a href="../titleproposal/route3.php">Route 3</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Final Defense</div>
                        <ul>
                        <li><a href="../final/route1.php">Route 1</a></li>
                            <li><a href="../final/route2.php">Route 2</a></li>
                            <li><a href="../final/route3.php">Route 3</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Register Account</div>
                        <ul>
                            <li><a href="../registeraccount/panel.php">Panel</a></li>
                            <li><a href="../registeraccount/adviser.php">Adviser</a></li>
                        </ul>
                    </div>
                </div>
                <div class="logout">
                <a href="../../../logout.php">Logout</a>
                </div>
            </nav>
            <div class="content">
                <div class="form-container">
                    <h1>Adviser Registration</h1>

                    <!-- Success Message -->
                    <div id="success-alert" class="alert alert-success" style="display:none;">
                        <strong>Success!</strong> New adviser registered successfully.
                    </div>

                    <form action="adviser.php" method="POST">
                        <label for="fullname">Complete Name</label>
                        <input type="text" id="fullname" name="fullname"
                            placeholder="First name / Middle name / Last name" required>

                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" required>

                        <label for="school_id">School ID</label> <!-- Changed from Username -->
                        <input type="text" id="school_id" name="school_id" required>

                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>

                        <div class="button-container">
                            <button type="submit">Register</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>