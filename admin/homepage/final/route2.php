<?php
session_start(); // Start the session

// Check if the user is not logged in (session variable is not set)
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../../../logout.php");
    exit;
}

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch departments
$departments = [];
$deptQuery = "SELECT DISTINCT department FROM student";
$deptResult = $conn->query($deptQuery);
if ($deptResult->num_rows > 0) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Initialize variables
$panel = [];
$adviser = [];
$files = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department'])) {
    $selectedDepartment = $_POST['department'];
    $_SESSION['selected_department'] = $selectedDepartment;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Load data for selected department
if (isset($_SESSION['selected_department'])) {
    $selectedDepartment = $_SESSION['selected_department'];

    // Fetch panel data
    $panelStmt = $conn->prepare("SELECT * FROM panel WHERE department = ?");
    $panelStmt->bind_param("s", $selectedDepartment);
    $panelStmt->execute();
    $panelResult = $panelStmt->get_result();
    while ($row = $panelResult->fetch_assoc()) {
        $panel[$row['position']][] = $row['fullname'];
    }
    $panelStmt->close();

    // Fetch adviser data
    $adviserStmt = $conn->prepare("SELECT * FROM adviser WHERE department = ?");
    $adviserStmt->bind_param("s", $selectedDepartment);
    $adviserStmt->execute();
    $adviserResult = $adviserStmt->get_result();
    while ($row = $adviserResult->fetch_assoc()) {
        $adviser[] = $row['fullname'];
    }
    $adviserStmt->close();

    // Fetch files
// Fetch files
$fileStmt = $conn->prepare(
    "SELECT 
        docuRoute2 AS filepath, 
        docuRoute2 AS filename, 
        date_submitted,
        controlNo,
        group_number,
        fullname,
        title,
        student_id, panel1_id, panel2_id, panel3_id, panel4_id, adviser_id
     FROM route2final_files 
     WHERE department = ?"
);

    
    $fileStmt->bind_param("s", $selectedDepartment);
    $fileStmt->execute();
    $fileResult = $fileStmt->get_result();
    while ($row = $fileResult->fetch_assoc()) {
        $files[] = [
            'filepath' => $row['filepath'],
            'filename' => $row['filename'],
            'controlNo' => $row['controlNo'],
            'group_number' => $row['group_number'],
            'fullname' => $row['fullname'],
            'student_id' => $row['student_id'],
            'panel1_id' => $row['panel1_id'],
            'panel2_id' => $row['panel2_id'],
            'panel3_id' => $row['panel3_id'],
            'panel4_id' => $row['panel4_id'],
            'adviser_id' => $row['adviser_id'],
            'title' => $row['title']
        ];
    }
    
    $fileStmt->close();
}

// Handle file submission for panelists and adviser
// Handle file submission for panelists and adviser
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selected_files'])) {
    $selectedFiles = $_POST['selected_files'];
    $panel1 = $_POST['panel1'] ?? null;
    $panel2 = $_POST['panel2'] ?? null;
    $panel3 = $_POST['panel3'] ?? null;
    $panel4 = $_POST['panel4'] ?? null;

    // Validation
    if (empty($selectedFiles)) {
        echo "<script>alert('Please select at least one file.');</script>";
    } elseif (empty($panel1) && empty($panel2) && empty($panel3) && empty($panel4)) {
        echo "<script>alert('Please select at least one panel.');</script>";
    } else {
        foreach ($selectedFiles as $filePath) {
            $fileName = $filePath; // Use the full path stored in the DB

            // Check if the file exists in DB
            $checkStmt = $conn->prepare("SELECT * FROM route2final_files WHERE docuRoute2 = ?");
            $checkStmt->bind_param("s", $fileName);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $fileExists = $result->num_rows > 0;
            $checkStmt->close();

            if ($fileExists) {
                // Update panel and adviser and set date_submitted
                $dateNow = date('Y-m-d H:i:s'); // Get the current date and time
                $updatePanelStmt = $conn->prepare("UPDATE route2final_files 
                    SET panel1_id = ?, panel2_id = ?, panel3_id = ?, panel4_id = ?, date_submitted = ? 
                    WHERE docuRoute2 = ?");
                $updatePanelStmt->bind_param("iiiiss", $panel1, $panel2, $panel3, $panel4, $dateNow, $fileName);
                $updatePanelStmt->execute();
                $updatePanelStmt->close();

                echo "<script>alert('Successfully Submitted.');</script>";
            } else {
                echo "<script>alert('File $fileName not found in the database.');</script>";
            }
        }
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route 2 - Thesis Routing System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
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

        .navigation {
            display: flex;
            align-items: center;
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

        .dropdown-container {
            display: flex;
            gap: 8px;
            margin-left: 15px;
        }

        .dropdown-container select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid var(--border);
            background-color: white;
            font-family: inherit;
            cursor: pointer;
        }

        #external-submit-button {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        #external-submit-button:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .vl {
            border-left: 1px solid var(--border);
            height: 20px;
            margin: 0 10px;
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

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background-color: white;
            box-shadow: var(--shadow);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        tr:hover {
            background-color: rgba(0, 0, 0, 0.03);
        }

        /* Button Styling */
        button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .view-button {
            background-color: var(--accent);
            color: white;
            margin-right: 0.5rem;
        }

        .view-button:hover {
            background-color: var(--primary);
        }

        /* Modal Styling */
        .modal {
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            width: 95%;
            height: 90%;
            display: flex;
            flex-direction: column;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .modal-layout {
            display: flex;
            height: 100%;
            width: 100%;
        }

        .file-preview-section,
        .routing-form-section {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
        }

        .file-preview-section {
            border-right: 1px solid var(--border);
        }

        .routing-form-section {
            background-color: #f9f9f9;
            font-size: 0.85rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 5px;
            margin-bottom: 10px;
        }

        .form-row label {
            font-size: 0.75rem;
        }

        .form-row input,
        .form-row textarea {
            padding: 4px;
            font-size: 0.75rem;
            min-height: 24px;
        }

        .form-input-row input,
        .form-input-row textarea {
            font-size: 0.75rem;
            min-height: 24px;
            text-align: center;
        }

        .form-grid-container {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .form-grid-container > div {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            font-size: 0.8rem;
            border: 1px solid var(--border);
            background-color: white;
            text-align: center;
        }

        .form-grid-container > div:first-child,
        .form-grid-container > div:nth-child(2),
        .form-grid-container > div:nth-child(3),
        .form-grid-container > div:nth-child(4),
        .form-grid-container > div:nth-child(5),
        .form-grid-container > div:nth-child(6),
        .form-grid-container > div:nth-child(7) {
            background-color: var(--primary-light);
            color: white;
            font-weight: 600;
        }

        .form-grid-container input,
        .form-grid-container textarea {
            width: 100%;
            height: 100%;
            padding: 4px;
            font-size: 0.75rem;
            box-sizing: border-box;
            border: none;
            outline: none;
            resize: none;
        }

        .form-input-row textarea {
            resize: vertical;
            min-height: 24px;
        }

        .close-button {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
            color: var(--dark);
            transition: all 0.3s;
            z-index: 1000;
        }

        .close-button:hover {
            color: var(--accent);
        }

        .feedback-cell {
            max-height: 120px;
            overflow-y: auto;
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
            
            .modal-layout {
                flex-direction: column;
            }
            
            .file-preview-section {
                border-right: none;
                border-bottom: 1px solid var(--border);
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border-left-color: var(--accent);
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        /* Table with checkboxes */
        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .selected {
            background-color: var(--light);
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <header class="header">
            <div class="logo-container">
                <img src="../../../assets/logo.png" alt="SMCC Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>

        <!-- Top Navigation Bar -->
        <div class="top-bar">
            <div class="navigation">
            <div class="homepage">
                <a href="../homepage.php">Home Page</a>
            </div>

                <!-- Department Dropdown -->
                <div class="dropdown-container">
                    <form method="POST" style="display: inline;">
                        <select name="department" onchange="this.form.submit()">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= htmlspecialchars($department) ?>"
                                    <?= isset($selectedDepartment) && $selectedDepartment == $department ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($department) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <!-- Panel Dropdowns -->
                    <select id="panel1-dropdown" name="panel1">
                        <option value="">Panel 1</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel1'");
                            $panelStmt->bind_param("s", $selectedDepartment);
                            $panelStmt->execute();
                            $panelResult = $panelStmt->get_result();
                            while ($row = $panelResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                    <?= htmlspecialchars($row['fullname']) ?>
                                </option>
                            <?php endwhile;
                            $panelStmt->close();
                        }
                        ?>
                    </select>

                    <select id="panel2-dropdown" name="panel2">
                        <option value="">Panel 2</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel2'");
                            $panelStmt->bind_param("s", $selectedDepartment);
                            $panelStmt->execute();
                            $panelResult = $panelStmt->get_result();
                            while ($row = $panelResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                    <?= htmlspecialchars($row['fullname']) ?>
                                </option>
                            <?php endwhile;
                            $panelStmt->close();
                        }
                        ?>
                    </select> 
                    
                    <select id="panel3-dropdown" name="panel3">
                        <option value="">Panel 3</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel3'");
                            $panelStmt->bind_param("s", $selectedDepartment);
                            $panelStmt->execute();
                            $panelResult = $panelStmt->get_result();
                            while ($row = $panelResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                    <?= htmlspecialchars($row['fullname']) ?>
                                </option>
                            <?php endwhile;
                            $panelStmt->close();
                        }
                        ?>
                    </select>
                    
                    <select id="panel4-dropdown" name="panel4">
                        <option value="">Panel 4</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel4'");
                            $panelStmt->bind_param("s", $selectedDepartment);
                            $panelStmt->execute();
                            $panelResult = $panelStmt->get_result();
                            while ($row = $panelResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                    <?= htmlspecialchars($row['fullname']) ?>
                                </option>
                            <?php endwhile;
                            $panelStmt->close();
                        }
                        ?>
                    </select>
                </div>
            </div>
            <button id="external-submit-button">Submit</button>
            <div class="user-info">
                <div class="routeNo" style="margin-right: 20px;">Final - Route 2</div>
                <div class="vl"></div>
                <span class="role">Admin:</span>
                <span class="user-name">
                    <?php echo isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Guest'; ?>
                </span>
            </div>
        </div>
        
        <!-- Main Content -->
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

            <div class="content" id="content-area">
                <form id="submission-form" action="route2.php" method="POST">
                    <table>
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Control No.</th>
                                <th>Leader</th>
                                <th>Group No.</th>
                                <th>Title</th>
                                <th>Assigned</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="file-list">
                            <?php if (!empty($files)): ?>
                                <?php foreach ($files as $file): ?>
                                    <?php
                                        $filepath = htmlspecialchars($file['filepath'], ENT_QUOTES);
                                        $filename = htmlspecialchars(basename($file['filename']), ENT_QUOTES);
                                        $controlNo = htmlspecialchars($file['controlNo'] ?? '', ENT_QUOTES);
                                        $fullname = htmlspecialchars($file['fullname'] ?? '', ENT_QUOTES);
                                        $group_number = htmlspecialchars($file['group_number'] ?? '', ENT_QUOTES);
                                        $route2_id = htmlspecialchars($file['route2_id'] ?? '', ENT_QUOTES);
                                        $student_id = htmlspecialchars($file['student_id'] ?? '', ENT_QUOTES);
                                        $title = htmlspecialchars($file['title'] ?? '', ENT_QUOTES);
                                        // Check if file is assigned to a panelist and adviser
                                        $assigned = '';
                                        if ($file['panel1_id'] || $file['panel2_id'] || $file['panel3_id'] || $file['panel4_id']) {
                                            $assigned = 'Panelists Assigned';
                                        }
                                        if ($file['adviser_id']) {
                                            $assigned .= $assigned ? ' & Adviser Assigned' : 'Adviser Assigned';
                                        }
                                        $assigned = $assigned ?: 'Not Assigned';
                                    ?>
                                    <tr>
                                        <td style="text-align: center;">
                                            <input type="checkbox" name="selected_files[]" value="<?= $filepath ?>">
                                        </td>
                                        <td><?= $controlNo ?></td>
                                        <td><?= $fullname ?></td>
                                        <td><?= $group_number ?></td>
                                        <td><?= $title ?></td>
                                        <td style="text-align: center;"><?= $assigned ?></td>
                                        <td style="text-align: center;">
                                            <button type="button" class="view-button" onclick="viewFile('<?= $filepath ?>', '<?= $student_id ?>', '<?= $route2_id ?>')">View</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No files found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Hidden Inputs -->
                    <input type="hidden" name="panel1" id="hidden-panel1">
                    <input type="hidden" name="panel2" id="hidden-panel2">
                    <input type="hidden" name="panel3" id="hidden-panel3">
                    <input type="hidden" name="panel4" id="hidden-panel4">
                    <input type="hidden" name="selected_adviser" id="hidden-adviser">
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Viewing Files -->
    <div id="fileModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">×</span>
            <div class="modal-layout">
                <div id="fileModalContent" class="file-preview-section"></div>
                <div id="routingForm" class="routing-form-section"></div>
            </div>
        </div>
    </div>
</html>

<script>
    // Handles file selection toggle
    function toggleFileSelection(fileElement) {
        const checkbox = fileElement.querySelector("input[type='checkbox']");
        checkbox.checked = !checkbox.checked;
        fileElement.classList.toggle("selected");
    }

    // Handles form submission
    // Handles form submission
document.getElementById("external-submit-button").addEventListener("click", function () {
    const panel1 = document.getElementById("panel1-dropdown").value.trim();
    const panel2 = document.getElementById("panel2-dropdown").value.trim();
    const panel3 = document.getElementById("panel3-dropdown").value.trim();
    const panel4 = document.getElementById("panel4-dropdown").value.trim();
   

    // Set hidden fields
    document.getElementById("hidden-panel1").value = panel1;
    document.getElementById("hidden-panel2").value = panel2;
    document.getElementById("hidden-panel3").value = panel3;
    document.getElementById("hidden-panel4").value = panel4;
   

    // Validate file selection
    const selectedFiles = document.querySelectorAll("input[name='selected_files[]']:checked");
    if (selectedFiles.length === 0) {
        alert("Please select at least one file.");
        return;
    }



    // If at least one panelist is selected, proceed
    const panelSelected = panel1 || panel2 || panel3 || panel4;
    if (!panelSelected) {
        alert("Please select at least one panelist.");
        return;
    }

    // Submit the form
    document.getElementById("submission-form").submit();
});


function viewFile(filePath, student_id, route1_id, route2_id) {
            const modal = document.getElementById("fileModal");
            const contentArea = document.getElementById("fileModalContent");
            const routingFormArea = document.getElementById("routingForm");

            modal.style.display = "flex";
            contentArea.innerHTML = "Loading file...";
            routingFormArea.innerHTML = `
    <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
        <img src="../../../assets/logo.png" style="width: 40px; max-width: 100px;">
        <img src="../../../assets/smcc-reslogo.png" style="width: 50px; max-width: 100px;">
        <div style="text-align: center;">
            <h4 style="margin: 0;">SAINT MICHAEL COLLEGE OF CARAGA</h4>
            <h4 style="margin: 0;">RESEARCH & INSTRUCTIONAL INNOVATION DEPARTMENT</h4>
        </div>
        <img src="../../../assets/socotec.png" style="width: 60px; max-width: 100px;">
    </div>
    <hr style="border: 1px solid black; margin: 0.2rem 0;">
    <div style="margin-top: 1rem; margin-bottom: 30px; display: flex; justify-content: center; align-items: center;">
        <h4 style="margin: 0;">ROUTING MONITORING FORM</h4>
    </div>

    <!-- Header row for submitted forms -->
    <div class="form-grid-container" style="margin-top: 20px;">
        <div><strong>Date Submitted</strong></div>
        <div><strong>Chapter</strong></div>
        <div><strong>Feedback</strong></div>
        <div><strong>Paragraph No</strong></div>
        <div><strong>Page No</strong></div>
        <div><strong>Submitted By</strong></div>
        <div><strong>Date Released</strong></div>
        <div><strong>Status</strong></div>
    </div>

    <!-- Container for submitted form data -->
    <div id="submittedFormsContainer" class="form-grid-container"></div>
    <div id="noFormsMessage" style="margin-top: 10px; color: gray;"></div>
`;


            // Load form data dynamically
            // Load form data dynamically using route2_id
            fetch(`route2get_all_forms.php?student_id=${encodeURIComponent(student_id)}&route1_id=${encodeURIComponent(route1_id)}&route2_id=${encodeURIComponent(route2_id)}`)
                .then(res => res.json())
                .then(data => {
                    console.log("Fetched forms:", data);
                    const rowsContainer = document.getElementById("submittedFormsContainer");

                    if (!Array.isArray(data) || data.length === 0) {
                        rowsContainer.innerHTML = `<div style="grid-column: span 9; text-align: center;">No routing form data available.</div>`;
                        return;
                    }
                    data.forEach(row => {
    let submittedBy = "N/A";
    if (row.adviser_name) {
        submittedBy = `${row.adviser_name} - Adviser`;
    } else if (row.panel_name) {
        submittedBy = `${row.panel_name} - Panel`;
    }

    rowsContainer.innerHTML += `
        <div>${row.date_submitted}</div>
        <div>${row.chapter}</div>
        <div>${row.feedback}</div>
        <div>${row.paragraph_number}</div>
        <div>${row.page_number}</div>
        <div>${submittedBy}</div>
        <div>${row.date_released}</div>
        <div>${row.status}</div>
    `;
});


                })
                .catch(err => {
                    console.error("Error loading form data:", err);
                });



            // Load file
            const extension = filePath.split('.').pop().toLowerCase();
            if (extension === "pdf") {
                contentArea.innerHTML = `<iframe src="${filePath}" width="100%" height="100%" style="border: none;"></iframe>`;
            } else if (extension === "docx") {
                fetch(filePath)
                    .then((response) => response.arrayBuffer())
                    .then((arrayBuffer) => mammoth.convertToHtml({ arrayBuffer }))
                    .then((result) => {
                        contentArea.innerHTML = `<div class="file-content">${result.value}</div>`;
                    })
                    .catch((err) => {
                        console.error("Error viewing file:", err);
                        alert("Failed to display the file.");
                    });
            } else {
                contentArea.innerHTML = "Unsupported file type.";
            }
        }

function closeModal() {
    const modal = document.getElementById("fileModal");
    modal.style.display = "none";
    document.getElementById("fileModalContent").innerHTML = '';
    document.getElementById("routingForm").innerHTML = '';
}


</script>

