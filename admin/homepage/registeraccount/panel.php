<?php
session_start(); // Start session to check admin login

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../logout.php");
    exit;
}

// Database connection
$host = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "trs"; // Replace with your database name

$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $department = $conn->real_escape_string($_POST['department']);
    
    // Sanitize and normalize position input
    $position = trim(strtolower($conn->real_escape_string($_POST['position'])));
    $school_id = $conn->real_escape_string($_POST['school_id']); // Changed from username to school_id
    $password = $_POST['password']; // Get password as plain text

    // Generate a unique panel_id
    $panel_id = uniqid("PANEL_");

    // Ensure the position is valid
    $valid_positions = ['panel1', 'panel2', 'panel3', 'panel4'];
    if (!in_array($position, $valid_positions)) {
        echo "<script>alert('Invalid position! Please select a valid position.');</script>";
        exit;
    }

    // Insert data into the database
    $sql = "INSERT INTO panel (panel_id, school_id, password, fullname, department, position) 
            VALUES ('$panel_id', '$school_id', '$password', '$fullname', '$department', '$position')";

    if ($conn->query($sql) === TRUE) {
        // Redirect with success status
        header("Location: panel.php?status=success");
        exit;
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}


// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Routing System</title>
    <link rel="stylesheet" href="adminstyle.css">
    <style>
        .form-container {
    max-width: 700px;
    background-color: #ccc;
    margin: auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: left;
}

.form-container h1 {
    text-align: center;
    color: #003399;
    margin-bottom: 20px;
}

.form-container label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
}

.form-container input {
    width: 97%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

/* Centering the button */
.form-container .button-container {
    display: flex;
    justify-content: center;
}

.form-container button {
    background-color: #003399;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1rem;
    width: 100px;
}

.form-container button:hover {
    background-color: #001a4d;
}

.alert {
    padding: 15px;
    margin: 15px 0;
    border-radius: 5px;
    font-size: 16px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
}

.vl {
    border-left: 2px solid;
    height: 50px;
    margin-right: 20px;
}

.user-info a{
    color: black;
    margin-right: 20px;
    padding: 10px;
    background-color: #879ecc;
    border-radius: 5px;
    font-weight: bold;
}
    </style>
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
            <a href="panel_register.php" ;">Registered Account</a>
                <div class="vl"></div>
                <span class="role">Admin:</span>
                <span class="user-name"><?php echo isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Guest'; ?></span>
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
                            <li><a href="../titleproposal/finaldocu.php">Final Document</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Final Defense</div>
                        <ul>
                        <li><a href="../final/route1.php">Route 1</a></li>
                            <li><a href="../final/route2.php">Route 2</a></li>
                            <li><a href="../final/route3.php">Route 3</a></li>
                            <li><a href="../final/finaldocu.php">Final Document</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Register Account</div>
                        <ul>
                            <li><a href="../registeraccount/panel.php">Panel</a></li>
                            <li><a href="../registeraccount/adviser.php">Adviser</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Registered Account</div>
                        <ul>
                            <li><a href="../registeredaccount/panel_register.php">Panel</a></li>
                            <li><a href="../registeredaccount/adviser_register.php">Adviser</a></li>
                            <li><a href="../registeredaccount/student_register.php">Student</a></li>
                        </ul>
                    </div>
                </div>
                <div class="logout">
                <a href="../../../logout.php">Logout</a>
                </div>
            </nav>
            <div class="content">
                <div class="form-container">
                    <h1>Panelist Registration</h1>
                    
                    <!-- Success Message -->
                    <div id="success-alert" class="alert alert-success" style="display:none;">
                        <strong>Success!</strong> Panelist registered successfully.
                    </div>
                    
                    <hr>
                    <form action="panel.php" method="POST">
    <label for="fullname">Complete Name</label>
    <input type="text" id="fullname" name="fullname" placeholder="First name / Middle name / Last name" required>

    <label for="department">Department</label>
    <input type="text" id="department" name="department" required>

    <label for="position">Position</label>
    <input type="text" id="position" name="position" placeholder="Panel1, Panel2, Panel3, Panel4" required>

    <label for="school_id">School ID</label>
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
