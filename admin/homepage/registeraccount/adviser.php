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
    <title>Adviser Registration - Thesis Routing System</title>
    <style>
        :root {
            --primary: #002366;
            --primary-light: #0a3885;
            --accent: #4a6fd1;
            --light: #f5f7fd;
            --dark: #333;
            --success: #28a745;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border: #e0e0e0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
        }
        
        .logo-container {
            display: flex;
            align-items: center;
        }
        
        .logo-container img {
            height: 50px;
            margin-right: 15px;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 2rem;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid var(--border);
        }
        
        .homepage a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .homepage a:hover {
            color: var(--primary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info a {
            color: white;
            margin-right: 15px;
            padding: 8px 15px;
            background-color: var(--accent);
            border-radius: 5px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .user-info a:hover {
            background-color: var(--primary);
        }
        
        .vl {
            border-left: 2px solid var(--border);
            height: 25px;
            margin: 0 15px;
        }
        
        .role {
            font-weight: 600;
            margin-right: 5px;
            color: var(--primary);
        }
        
        .user-name {
            color: var(--dark);
        }
        
        .main-content {
            display: flex;
            flex: 1;
        }
        
        .sidebar {
            width: 250px;
            background-color: white;
            padding: 1.5rem 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid var(--border);
        }
        
        .menu-section {
            margin-bottom: 1.5rem;
        }
        
        .menu-title {
            font-weight: 600;
            color: var(--primary);
            padding: 0.5rem 1.5rem;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .sidebar ul {
            list-style: none;
        }
        
        .sidebar li {
            margin-bottom: 0.25rem;
        }
        
        .sidebar a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar a:hover {
            background-color: var(--light);
            color: var(--accent);
            border-left: 3px solid var(--accent);
        }
        
        .logout {
            padding: 0 1.5rem;
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
        }
        
        .logout a {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
            color: #555;
            padding: 0.75rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .logout a:hover {
            background-color: #e0e0e0;
            transform: translateY(-2px);
        }
        
        .content {
            flex: 1;
            padding: 2rem;
            position: relative;
            overflow: auto;
        }
        
        .form-container {
            max-width: 700px;
            background-color: white;
            margin: auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            text-align: left;
        }
        
        .form-container h1 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
        }
        
        .form-container label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-container input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1.25rem;
            border: 1px solid var(--border);
            border-radius: 5px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        .form-container input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(74, 111, 209, 0.2);
        }
        
        .form-container .button-container {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
        }
        
        .form-container button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .form-container button:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem 0;
            }
            
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-info {
                margin-top: 0.5rem;
            }
            
            .form-container {
                padding: 1.5rem;
            }
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
                <img src="../../../assets/logo.png" alt="SMCC Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>
        <div class="top-bar">
            <div class="homepage">
                <a href="../../homepage/homepage.php">Home Page</a>
            </div>
            <div class="user-info">
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