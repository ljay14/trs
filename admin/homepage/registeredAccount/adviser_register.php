<?php
// Start the session
session_start();

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

$sql = "SELECT fullname, department, school_id FROM adviser ORDER BY fullname ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Routing System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-alert {
            display: none;
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 20px;
            border-left: 5px solid #28a745;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .form-container {
            max-width: 1200px;
            background-color: #ccc;
            margin: auto;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: left;
            border: none;
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
            width: 90%;
            padding: 10px;
            margin-bottom: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;

        }

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

        .user-info a {
            color: black;
            margin-right: 20px;
            padding: 10px;
            background-color: #879ecc;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
    <script>
        window.onload = function () {
            // Show success message if status is success
            var status = new URLSearchParams(window.location.search).get('status');
            if (status === 'success') {
                document.getElementById('success-alert').style.display = 'block';
            }
        }

        // Function to enable the save button when a password is entered
        function enableSave(school_id) {
            var passwordField = document.getElementById('password_' + school_id);
            var saveButton = document.getElementById('save_' + school_id);
            if (passwordField.value.trim() !== "") {
                saveButton.disabled = false;  // Enable the save button
            } else {
                saveButton.disabled = true;  // Disable the save button when no password is entered
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
                <div class="vl"></div>
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

                    <h1>List of Registered Panel</h1>

                    <div id="success-alert" class="success-alert">
                        Adviser updated successfully!
                    </div>

                    <?php if ($result->num_rows > 0): ?>
                        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Department</th>
                                    <th>School ID</th>
                                    <th>Password</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <form action="update_adviser_inline.php" method="POST">
                                            <td>
                                                <input type="text" name="fullname"
                                                    value="<?= htmlspecialchars($row['fullname']) ?>" required>
                                            </td>
                                            <td>
                                                <input type="text" name="department"
                                                    value="<?= htmlspecialchars($row['department']) ?>" required>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['school_id']) ?>
                                                <input type="hidden" name="school_id"
                                                    value="<?= htmlspecialchars($row['school_id']) ?>">
                                            </td>
                                            <td>
                                                <input type="password" name="password" placeholder="New Password"
                                                    id="password_<?= $row['school_id'] ?>"
                                                    oninput="enableSave('<?= $row['school_id'] ?>')">
                                            </td>
                                            <td>
                                                <button type="submit" id="save_<?= $row['school_id'] ?>" disabled>Save</button>
                                            </td>
                                        </form>

                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No advisers have been registered yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>

</html>