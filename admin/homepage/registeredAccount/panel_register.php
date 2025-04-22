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

$sql = "SELECT fullname, department, school_id, password, position FROM panel ORDER BY fullname ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Routing System</title>
    <link rel="stylesheet" href="adminstyle.css">
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
            Panel updated successfully!
        </div>

        <?php if ($result->num_rows > 0): ?>
            <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Position</th>
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
                            <form action="update_panel_inline.php" method="POST" id="form_<?= $row['school_id'] ?>">
                                <td>
                                    <span id="position_text_<?= $row['school_id'] ?>"><?= htmlspecialchars($row['position']) ?></span>
                                    <input type="text" name="position" value="<?= htmlspecialchars($row['position']) ?>" id="position_input_<?= $row['school_id'] ?>" style="display:none;" required>
                                </td>
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
                                    <input type="password" name="password" id="password_input_<?= $row['school_id'] ?>" style="display:none;" placeholder="Enter New Password">
                                </td>
                                <td>
                                    <button type="button" onclick="enableEdit('<?= $row['school_id'] ?>')" id="edit_btn_<?= $row['school_id'] ?>">Edit</button>
                                    <button type="submit" style="display:none;" id="save_btn_<?= $row['school_id'] ?>">Save</button>
                                    <button type="button" style="display:none;" onclick="cancelEdit('<?= $row['school_id'] ?>')" id="cancel_btn_<?= $row['school_id'] ?>">Cancel</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No panelists have been registered yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    function enableEdit(school_id) {
        document.getElementById('position_text_' + school_id).style.display = 'none';
        document.getElementById('fullname_text_' + school_id).style.display = 'none';
        document.getElementById('department_text_' + school_id).style.display = 'none';
        document.getElementById('password_text_' + school_id).style.display = 'none';

        document.getElementById('position_input_' + school_id).style.display = 'inline';
        document.getElementById('fullname_input_' + school_id).style.display = 'inline';
        document.getElementById('department_input_' + school_id).style.display = 'inline';
        document.getElementById('password_input_' + school_id).style.display = 'inline';

        document.getElementById('edit_btn_' + school_id).style.display = 'none';
        document.getElementById('save_btn_' + school_id).style.display = 'inline';
        document.getElementById('cancel_btn_' + school_id).style.display = 'inline';
    }

    function cancelEdit(school_id) {
        document.getElementById('position_text_' + school_id).style.display = 'inline';
        document.getElementById('fullname_text_' + school_id).style.display = 'inline';
        document.getElementById('department_text_' + school_id).style.display = 'inline';
        document.getElementById('password_text_' + school_id).style.display = 'inline';

        document.getElementById('position_input_' + school_id).style.display = 'none';
        document.getElementById('fullname_input_' + school_id).style.display = 'none';
        document.getElementById('department_input_' + school_id).style.display = 'none';
        document.getElementById('password_input_' + school_id).style.display = 'none';

        document.getElementById('edit_btn_' + school_id).style.display = 'inline';
        document.getElementById('save_btn_' + school_id).style.display = 'none';
        document.getElementById('cancel_btn_' + school_id).style.display = 'none';
    }
    </script>


        </div>
    </div>
</body>
</html>