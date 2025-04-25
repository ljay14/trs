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

$sql = "SELECT fullname, department, school_id, password, confirm_password FROM student ORDER BY fullname ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Thesis Routing System</title>
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
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-container h1 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
        }
        
        .success-alert {
            display: none;
            background-color: #d4edda;
            color: #155724;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            border-left: 5px solid var(--success);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            background-color: white;
        }
        
        table th, table td {
            padding: 0.75rem;
            text-align: left;
            border: 1px solid var(--border);
        }
        
        table th {
            background-color: var(--light);
            color: var(--primary);
            font-weight: 600;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        table tr:hover {
            background-color: #f5f5f5;
        }
        
        button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        button[type="button"] {
            background-color: var(--accent);
            color: white;
        }
        
        button[type="submit"] {
            background-color: var(--success);
            color: white;
        }
        
        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        input[type="text"], input[type="password"] {
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            width: 100%;
            font-size: 0.9rem;
        }
        
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(74, 111, 209, 0.2);
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
            
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
    <script>
        window.onload = function () {
            // Show success message if status is success
            var status = new URLSearchParams(window.location.search).get('status');
            if (status === 'success') {
                document.getElementById('success-alert').style.display = 'block';
                
                // Auto-hide the success message after 5 seconds
                setTimeout(function() {
                    document.getElementById('success-alert').style.display = 'none';
                }, 5000);
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
                    <h1>List of Registered Students</h1>

                    <div id="success-alert" class="success-alert">
                        <strong>Success!</strong> Student updated successfully!
                    </div>

                    <?php if ($result->num_rows > 0): ?>
                        <table border="1" cellpadding="10" cellspacing="0">
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
                                    <tr id="row_<?= $row['school_id'] ?>">
                                        <form action="update_student_inline.php" method="POST" id="form_<?= $row['school_id'] ?>">
                                            <td>
                                                <span id="fullname_text_<?= $row['school_id'] ?>"><?= htmlspecialchars($row['fullname']) ?></span>
                                                <input type="text" name="fullname" value="<?= htmlspecialchars($row['fullname']) ?>" id="fullname_input_<?= $row['school_id'] ?>" style="display:none;" required>
                                            </td>
                                            <td>
                                                <span id="department_text_<?= $row['school_id'] ?>"><?= htmlspecialchars($row['department']) ?></span>
                                                <input type="text" name="department" value="<?= htmlspecialchars($row['department']) ?>" id="department_input_<?= $row['school_id'] ?>" style="display:none;" required>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($row['school_id']) ?>
                                                <input type="hidden" name="school_id" value="<?= htmlspecialchars($row['school_id']) ?>">
                                            </td>
                                            <td>
                                                <span id="password_text_<?= $row['school_id'] ?>"><?= htmlspecialchars($row['password']) ?></span>
                                                <div id="password_inputs_<?= $row['school_id'] ?>" style="display:none;">
                                                    <input type="text" name="password" value="<?= htmlspecialchars($row['password']) ?>" id="password_input_<?= $row['school_id'] ?>" placeholder="New Password" required oninput="checkPasswordMatch('<?= $row['school_id'] ?>')">
                                                    <br>
                                                    <input type="text" name="confirm_password" id="confirm_password_input_<?= $row['school_id'] ?>" placeholder="Confirm Password" required oninput="checkPasswordMatch('<?= $row['school_id'] ?>')">
                                                    <br>
                                                    <small id="mismatch_<?= $row['school_id'] ?>" style="color:red; display:none;">Passwords do not match!</small>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" onclick="enableEdit('<?= $row['school_id'] ?>')" id="edit_btn_<?= $row['school_id'] ?>">Edit</button>
                                                <button type="submit" style="display:none;" id="save_btn_<?= $row['school_id'] ?>" disabled>Save</button>
                                                <button type="button" style="display:none;" onclick="cancelEdit('<?= $row['school_id'] ?>')" id="cancel_btn_<?= $row['school_id'] ?>">Cancel</button>
                                            </td>
                                        </form>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No students have been registered yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function enableEdit(school_id) {
            document.getElementById('fullname_text_' + school_id).style.display = 'none';
            document.getElementById('department_text_' + school_id).style.display = 'none';
            document.getElementById('password_text_' + school_id).style.display = 'none';

            document.getElementById('fullname_input_' + school_id).style.display = 'inline';
            document.getElementById('department_input_' + school_id).style.display = 'inline';
            document.getElementById('password_inputs_' + school_id).style.display = 'block';

            document.getElementById('edit_btn_' + school_id).style.display = 'none';
            document.getElementById('save_btn_' + school_id).style.display = 'inline';
            document.getElementById('cancel_btn_' + school_id).style.display = 'inline';
        }

        function cancelEdit(school_id) {
            document.getElementById('fullname_text_' + school_id).style.display = 'inline';
            document.getElementById('department_text_' + school_id).style.display = 'inline';
            document.getElementById('password_text_' + school_id).style.display = 'inline';

            document.getElementById('fullname_input_' + school_id).style.display = 'none';
            document.getElementById('department_input_' + school_id).style.display = 'none';
            document.getElementById('password_inputs_' + school_id).style.display = 'none';

            document.getElementById('edit_btn_' + school_id).style.display = 'inline';
            document.getElementById('save_btn_' + school_id).style.display = 'none';
            document.getElementById('cancel_btn_' + school_id).style.display = 'none';

            document.getElementById('mismatch_' + school_id).style.display = 'none';
        }

        function checkPasswordMatch(school_id) {
            var password = document.getElementById('password_input_' + school_id).value;
            var confirm_password = document.getElementById('confirm_password_input_' + school_id).value;
            var mismatchText = document.getElementById('mismatch_' + school_id);
            var saveBtn = document.getElementById('save_btn_' + school_id);

            if (password !== confirm_password) {
                mismatchText.style.display = 'block';
                saveBtn.disabled = true;
            } else {
                mismatchText.style.display = 'none';
                saveBtn.disabled = false;
            }
        }
    </script>
</body>
</html>