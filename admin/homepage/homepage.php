<?php
session_start(); // Start the session

// Check if the user is not logged in (session variable is not set)
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../../logout.php");
    exit; // Ensure no further code is executed
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Routing System</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">
        <header class="header">
            <div class="logo-container">
                <img src="../../assets/logo.png" alt="Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>
        <div class="top-bar">
            <div class="homepage">
                <a href="../homepage/homepage.php">Home Page</a>
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
                            <li><a href="titleproposal/route1.php">Route 1</a></li>
                            <li><a href="titleproposal/route2.php">Route 2</a></li>
                            <li><a href="titleproposal/route3.php">Route 3</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Final Defense</div>
                        <ul>
                            <li><a href="final/route1.php">Route 1</a></li>
                            <li><a href="final/route2.php">Route 2</a></li>
                            <li><a href="final/route3.php">Route 3</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Register Account</div>
                        <ul>
                            <li><a href="registeraccount/panel.php">Panel</a></li>
                            <li><a href="registeraccount/adviser.php">Adviser</a></li>
                            <li><a href="registeraccount/student_register.php">Student</a></li>
                        </ul>
                    </div>
                </div>
                <div class="logout">
                    <a href="../../logout.php">Logout</a>
                </div>
            </nav>

        </div>
    </div>
</body>

</html>