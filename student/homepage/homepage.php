<?php
session_start(); // Start the session

// Check if the user is not logged in (session variable is not set)
if (!isset($_SESSION['student_id'])) {
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
    <title>Student Dashboard - Thesis Routing System</title>
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
        
        .welcome-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .welcome-card h1 {
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .welcome-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="logo-container">
                <img src="../../assets/logo.png" alt="SMCC Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>
        <div class="top-bar">
            <div class="homepage">
                <a href="homepage.php">Home Page</a>
            </div>
            <div class="user-info">
                <span class="role">Student:</span>
                <span class="user-name"><?php echo isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Guest'; ?></span>
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
                            <li><a href="titleproposal/finaldocu.php">Final Document</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Final Defense</div>
                        <ul>
                            <li><a href="final/route1.php">Route 1</a></li>
                            <li><a href="final/route2.php">Route 2</a></li>
                            <li><a href="final/route3.php">Route 3</a></li>
                            <li><a href="final/finaldocu.php">Final Document</a></li>
                        </ul>
                    </div>
                </div>
                <div class="logout">
                    <a href="../../logout.php">Logout</a>
                </div>
            </nav>
            <div class="content">
                <div class="welcome-card">
                    <h1>Welcome to Thesis Routing System</h1>
                    <p>Select an option from the sidebar to manage your thesis documents and track your progress through the routing system.</p>
                </div>
                <!-- Additional content will go here -->
            </div>
        </div>
    </div>
</body>
</html>