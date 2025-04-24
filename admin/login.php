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
    <title>Admin Login - Thesis Routing System</title>
    <style>
        :root {
            --primary: #002366;
            --primary-light: #0a3885;
            --accent: #4a6fd1;
            --light: #f5f7fd;
            --dark: #333;
            --success: #28a745;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            min-height: 100vh;
            background-color: var(--light);
        }
        
        .left-panel {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            width: 40%;
            position: relative;
            overflow: hidden;
        }
        
        .left-panel::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: -250px;
            left: -100px;
            z-index: 1;
        }
        
        .left-panel::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            top: -150px;
            right: -50px;
            z-index: 1;
        }
        
        .logo-container {
            position: relative;
            z-index: 2;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .left-panel img {
            max-width: 100px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2));
            margin-bottom: 20px;
        }
        
        .left-panel h1 {
            margin: 0;
            font-size: 28px;
            text-align: center;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }
        
        .left-panel p {
            margin: 10px 0;
            text-align: center;
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.5;
            max-width: 350px;
            position: relative;
            z-index: 2;
        }
        
        .right-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 60%;
            padding: 40px;
        }
        
        .login-card {
            background-color: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
        }
        
        .login-card h2 {
            color: var(--dark);
            font-size: 24px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }
        
        .login-card p {
            color: #666;
            text-align: center;
            margin-bottom: 25px;
            font-size: 15px;
        }
        
        .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }
        
        .login-card input {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .login-card input:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 2px rgba(74, 111, 209, 0.2);
        }
        
        .login-btn, 
        .back-btn {
            width: 100%;
            padding: 14px;
            margin-bottom: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .login-btn {
            background-color: var(--accent);
            color: white;
        }
        
        .login-btn:hover {
            background-color: #3a5fc1;
            transform: translateY(-2px);
        }
        
        .back-btn {
            background-color: #f0f0f0;
            color: #555;
        }
        
        .back-btn:hover {
            background-color: #e0e0e0;
        }
        
        .login-icon {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .login-icon span {
            background-color: var(--light);
            color: var(--primary);
            font-size: 30px;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #777;
            font-size: 14px;
        }
        
        @media (max-width: 900px) {
            body {
                flex-direction: column;
            }
            
            .left-panel, .right-panel {
                width: 100%;
                padding: 30px 20px;
            }
            
            .left-panel {
                padding-top: 40px;
                padding-bottom: 40px;
            }
        }
    </style>
</head>

<body>
    <div class="left-panel">
        <div class="logo-container">
            <img src="../assets/logo.png" alt="SMCC Logo">
            <h1>Saint Michael College of Caraga</h1>
            <p>Brgy 4, Atupan St., Nasipit, Agusan del Norte</p>
        </div>
    </div>

    <div class="right-panel">
        <div class="login-card">
            <div class="login-icon">
                <span>👨‍💼</span>
            </div>
            <h2>Admin Login</h2>
            <p>Enter your credentials to access the system</p>
            
            <form action="login.php" method="POST">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button class="login-btn" type="submit">Login</button>
                <button class="back-btn" type="button" onclick="window.location.href='../index.php'">Back to Home</button>
            </form>
        </div>
        
        <div class="footer">
            <p>© 2025 Saint Michael College of Caraga | All Rights Reserved</p>
        </div>
    </div>
</body>
</html>