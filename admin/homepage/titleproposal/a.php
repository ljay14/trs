<?php
session_start(); // Start the session

// Check if the user is not logged in (session variable is not set)
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../../../logout.php");
    exit;
}

// Database connection settings
include '../../../connection.php';

// Fetch departments
$departments = [];
$deptQuery = "SELECT DISTINCT department FROM student";
$deptResult = $conn->query($deptQuery);
if ($deptResult->num_rows > 0) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Fetch school years
$schoolYears = [];
$schoolYearQuery = "SELECT DISTINCT school_year FROM route1proposal_files ORDER BY school_year DESC";
$schoolYearResult = $conn->query($schoolYearQuery);
if ($schoolYearResult && $schoolYearResult->num_rows > 0) {
    while ($row = $schoolYearResult->fetch_assoc()) {
        $schoolYears[] = $row['school_year'];
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
    $selectedDepartment = $_POST['department'] ?? $_SESSION['selected_department'] ?? '';
    $selectedSchoolYear = $_POST['school_year'] ?? $_SESSION['selected_school_year'] ?? '';
    
    $_SESSION['selected_department'] = $selectedDepartment;
    $_SESSION['selected_school_year'] = $selectedSchoolYear;

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
    $fileStmt = $conn->prepare(
        "SELECT 
            docuRoute1 AS filepath, 
            docuRoute1 AS filename, 
            date_submitted,
            controlNo,
            group_number,
            fullname,
            title,
            student_id, panel1_id, panel2_id, panel3_id, panel4_id, panel5_id, adviser_id
         FROM route1proposal_files 
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
            'panel5_id' => $row['panel5_id'],
            'adviser_id' => $row['adviser_id'],
            'title' => $row['title']
        ];
    }
    
    $fileStmt->close();
}

// Handle file submission for panelists and adviser
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selected_files'])) {
    $selectedFiles = $_POST['selected_files'];
    $panel1 = $_POST['panel1'] ?? null;
    $panel2 = $_POST['panel2'] ?? null;
    $panel3 = $_POST['panel3'] ?? null;
    $panel4 = $_POST['panel4'] ?? null;
    $panel5 = $_POST['panel5'] ?? null;

    // Validation
    if (empty($selectedFiles)) {
        echo "<script>alert('Please select at least one file.');</script>";
    } elseif (empty($panel1) && empty($panel2) && empty($panel3) && empty($panel4) && empty($panel5)) {
        echo "<script>alert('Please select at least one panel.');</script>";
    } else {
        foreach ($selectedFiles as $filePath) {
            $fileName = $filePath; // Use the full path stored in the DB

            // Check if the file exists in DB
            $checkStmt = $conn->prepare("SELECT * FROM route1proposal_files WHERE docuRoute1 = ?");
            $checkStmt->bind_param("s", $fileName);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $fileExists = $result->num_rows > 0;
            $checkStmt->close();

            if ($fileExists) {
                // Update panel IDs and set date_submitted
                $dateNow = date('Y-m-d H:i:s'); // Get the current date and time
                $updatePanelStmt = $conn->prepare("UPDATE route1proposal_files 
                    SET panel1_id = ?, panel2_id = ?, panel3_id = ?, panel4_id = ?, panel5_id = ?, date_submitted = ? 
                    WHERE docuRoute1 = ?");
                $updatePanelStmt->bind_param("iiiiiss", $panel1, $panel2, $panel3, $panel4, $panel5, $dateNow, $fileName);
                $updatePanelStmt->execute();
                $updatePanelStmt->close();

                echo "<script>alert('Successfully Submitted.');</script>";
            } else {
                echo "<script>alert('File $fileName not found in the database.');</script>";
            }
        }
    }
}

// Function to get panel name by ID
function getPanelName($conn, $panel_id) {
    if (empty($panel_id)) return "";
    
    $stmt = $conn->prepare("SELECT fullname FROM panel WHERE panel_id = ?");
    $stmt->bind_param("i", $panel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['fullname'];
    }
    
    return "";
}

// Function to get adviser name by ID
function getAdviserName($conn, $adviser_id) {
    if (empty($adviser_id)) return "";
    
    $stmt = $conn->prepare("SELECT fullname FROM adviser WHERE adviser_id = ?");
    $stmt->bind_param("i", $adviser_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['fullname'];
    }
    
    return "";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route 1 - Thesis Routing System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <link rel="stylesheet" href="proposalstyles.css">
    <style>
        /* Add styles for the search bar */
        .search-container {
            margin: 15px 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-box {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 300px;
            font-size: 14px;
        }

        .panel-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .panel-container select {
            flex: 1;
            min-width: 120px;
            max-width: 200px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        #external-submit-button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        #external-submit-button:hover {
            background-color: #45a049;
        }
        
        /* Styles for the assignment button and modal */
        .assignment-button {
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .assignment-button:hover {
            background-color: #0056b3;
        }
        
        /* Styles for the assignment modal */
        .assignment-modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .assignment-modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            max-width: 500px;
            border-radius: 8px;
        }
        
        .assignment-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .assignment-modal-close:hover,
        .assignment-modal-close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        
        .assignment-list {
            margin-top: 15px;
        }
        
        .assignment-list h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .assignment-list ul {
            padding-left: 20px;
            margin-bottom: 15px;
        }
        
        .assignment-list li {
            margin-bottom: 5px;
        }
        
        .not-assigned {
            color: #999;
            font-style: italic;
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
                    <form method="POST" style="display: inline; margin-left: 10px;">
                        <select name="school_year" onchange="this.form.submit()">
                            <option value="">All School Years</option>
                            <?php foreach ($schoolYears as $year): ?>
                                <option value="<?= htmlspecialchars($year) ?>"
                                    <?= isset($selectedSchoolYear) && $selectedSchoolYear == $year ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($year) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            <div class="user-info">
                <div class="routeNo" style="margin-right: 20px;">Proposal - Route 1</div>
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
                <nav class="nav-menu">
                    <!-- Title Proposal Section -->
                    <div class="menu-item dropdown">
                        <div class="menu-header">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                            <span>Research Proposal</span>
                            <div class="dropdown-icon expanded">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="dropdown-content show">
                            <a href="../titleproposal/route1.php" class="submenu-item">Route 1</a>
                            <a href="../titleproposal/route2.php" class="submenu-item">Route 2</a>
                            <a href="../titleproposal/route3.php" class="submenu-item">Route 3</a>
                            <a href="../titleproposal/finaldocu.php" class="submenu-item">Final Document</a>
                        </div>
                    </div>

                    <!-- Final Defense Section -->
                    <div class="menu-item dropdown">
                        <div class="menu-header">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                </svg>
                            </div>
                            <span>Final Defense</span>
                            <div class="dropdown-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="dropdown-content">
                            <a href="../final/route1.php" class="submenu-item">Route 1</a>
                            <a href="../final/route2.php" class="submenu-item">Route 2</a>
                            <a href="../final/route3.php" class="submenu-item">Route 3</a>
                            <a href="../final/finaldocu.php" class="submenu-item">Final Document</a>
                        </div>
                    </div>

                    <!-- Department Course Section -->
                    <div class="menu-item dropdown">
                        <div class="menu-header">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                                </svg>
                            </div>
                            <span>Department Course</span>
                            <div class="dropdown-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="dropdown-content">
                            <a href="../departmentcourse/departmentcourse.php" class="submenu-item">Department Course</a>
                        </div>
                    </div>

                    <!-- Register Account Section -->
                    <div class="menu-item dropdown">
                        <div class="menu-header">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="8.5" cy="7" r="4"></circle>
                                    <line x1="20" y1="8" x2="20" y2="14"></line>
                                    <line x1="23" y1="11" x2="17" y2="11"></line>
                                </svg>
                            </div>
                            <span>Register Account</span>
                            <div class="dropdown-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="dropdown-content">
                            <a href="../registeraccount/panel.php" class="submenu-item">Panel</a>
                            <a href="../registeraccount/adviser.php" class="submenu-item">Adviser</a>
                        </div>
                    </div>

                    <!-- Registered Account Section -->
                    <div class="menu-item dropdown">
                        <div class="menu-header">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <span>Registered Account</span>
                            <div class="dropdown-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div class="dropdown-content">
                            <a href="../registeredaccount/panel_register.php" class="submenu-item">Panel</a>
                            <a href="../registeredaccount/adviser_register.php" class="submenu-item">Adviser</a>
                            <a href="../registeredaccount/student_register.php" class="submenu-item">Student</a>
                        </div>
                    </div>
                </nav>
                <div class="logout">
                    <a href="../../../logout.php">Logout</a>
                </div>
            </nav>

            
            <div class="content" id="content-area">
                
                <!-- Panel Selection and Submit Button Area -->
                <!-- Panel Selection and Submit Button Area -->
<div class="panel-container">
    <select id="panel1-dropdown">
        <option value="">Panel 1</option>
        <?php
        if (isset($selectedDepartment)) {
            // Modified query to include all Panel 1 members regardless of department
            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel WHERE position = 'panel1'");
            $panelStmt->execute();
            $panelResult = $panelStmt->get_result();
            while ($row = $panelResult->fetch_assoc()):
                // You can optionally show the department in the dropdown option
            ?>
                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                    <?= htmlspecialchars($row['fullname']) ?> <?= ($row['department'] != $selectedDepartment) ? '(' . htmlspecialchars($row['department']) . ')' : '' ?>
                </option>
            <?php endwhile;
            $panelStmt->close();
        }
        ?>
    </select>
    <select id="panel2-dropdown">
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
    
    <select id="panel3-dropdown">
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
    
    <select id="panel4-dropdown">
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
    
    <select id="panel5-dropdown">
        <option value="">Panel 5</option>
        <?php
        if (isset($selectedDepartment)) {
            $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel5'");
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
    <button id="external-submit-button" type="button">Submit</button>
</div>

<!-- Search and Submit Button -->
<div class="search-container">
    <input type="text" id="searchInput" class="search-box" placeholder="Search by leader name..." onkeyup="searchTable()">
</div>

<form id="submission-form" action="route1.php" method="POST">
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
                        $route1_id = htmlspecialchars($file['route1_id'] ?? '', ENT_QUOTES);
                        $student_id = htmlspecialchars($file['student_id'] ?? '', ENT_QUOTES);
                        $title = htmlspecialchars($file['title'] ?? '', ENT_QUOTES);
                        
                        // Get assigned panel names
                        $assigned_panels = [];
                        for ($i = 1; $i <= 5; $i++) {
                            $panel_id_key = "panel{$i}_id";
                            if (!empty($file[$panel_id_key])) {
                                $panel_name = getPanelName($conn, $file[$panel_id_key]);
                                if (!empty($panel_name)) {
                                    $assigned_panels[] = ["name" => $panel_name, "position" => "Panel $i"];
                                }
                            }
                        }
                        
                        // Get assigned adviser name
                        $adviser_name = "";
                        if (!empty($file['adviser_id'])) {
                            $adviser_name = getAdviserName($conn, $file['adviser_id']);
                        }
                        
                        // Create assignment status for button text
                        $assignment_status = "";
                        $has_panels = !empty($assigned_panels);
                        $has_adviser = !empty($adviser_name);
                        
                        if ($has_panels && $has_adviser) {
                            $assignment_status = "Panelists & Adviser";
                        } elseif ($has_panels) {
                            $assignment_status = "Panelists";
                        } elseif ($has_adviser) {
                            $assignment_status = "Adviser";
                        } else {
                            $assignment_status = "Not Assigned";
                        }

                    ?>
                    <tr>
                        <td><input type="checkbox" name="selected_files[]" value="<?= $filepath ?>"></td>
                        <td><?= $controlNo ?></td>
                        <td><?= $fullname ?></td>
                        <td><?= $group_number ?></td>
                        <td><?= $title ?></td>
                        <td>
                            <button type="button" class="assignment-button" onclick="showAssignmentDetails(<?= htmlspecialchars(json_encode([
                                'panels' => $assigned_panels,
                                'adviser' => $adviser_name
                            ]), ENT_QUOTES) ?>)">
                                <?php if ($has_panels || $has_adviser): ?>
                                    View Assignments
                                <?php else: ?>
                                    Not Assigned
                                <?php endif; ?>
                            </button>
                        </td>
                        <td><a href="<?= $filepath ?>" target="_blank">View</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No files found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <input type="hidden" name="panel1" id="panel1-input">
    <input type="hidden" name="panel2" id="panel2-input">
    <input type="hidden" name="panel3" id="panel3-input">
    <input type="hidden" name="panel4" id="panel4-input">
    <input type="hidden" name="panel5" id="panel5-input">
</form>

<!-- Assignment Modal -->
<div id="assignmentModal" class="assignment-modal">
    <div class="assignment-modal-content">
        <span class="assignment-modal-close" onclick="closeAssignmentModal()">&times;</span>
        <h2>Assignment Details</h2>
        <div class="assignment-list">
            <h3>Panelists</h3>
            <ul id="panelists-list">
                <!-- Will be populated dynamically -->
            </ul>
            
            <h3>Adviser</h3>
            <div id="adviser-name">
                <!-- Will be populated dynamically -->
            </div>
        </div>
    </div>
</div>

<script>
    // Function to handle search functionality
    function searchTable() {
        var input, filter, table, tr, td, i, txtValue;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.querySelector("table");
        tr = table.getElementsByTagName("tr");
        
        for (i = 1; i < tr.length; i++) { // Start from 1 to skip the header row
            td = tr[i].getElementsByTagName("td")[2]; // The third column has the leader's name
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }
    
    // Toggle dropdown content visibility
    document.querySelectorAll('.menu-header').forEach(function(header) {
        header.addEventListener('click', function() {
            const dropdownContent = this.nextElementSibling;
            const dropdownIcon = this.querySelector('.dropdown-icon');
            
            dropdownContent.classList.toggle('show');
            dropdownIcon.classList.toggle('expanded');
        });
    });
    
    // External submit button functionality
    document.getElementById('external-submit-button').addEventListener('click', function() {
        // Get selected panel values
        const panel1Value = document.getElementById('panel1-dropdown').value;
        const panel2Value = document.getElementById('panel2-dropdown').value;
        const panel3Value = document.getElementById('panel3-dropdown').value;
        const panel4Value = document.getElementById('panel4-dropdown').value;
        const panel5Value = document.getElementById('panel5-dropdown').value;
        
        // Set hidden input values
        document.getElementById('panel1-input').value = panel1Value;
        document.getElementById('panel2-input').value = panel2Value;
        document.getElementById('panel3-input').value = panel3Value;
        document.getElementById('panel4-input').value = panel4Value;
        document.getElementById('panel5-input').value = panel5Value;
        
        // Check if at least one file is selected
        const selectedFiles = document.querySelectorAll('input[name="selected_files[]"]:checked');
        if (selectedFiles.length === 0) {
            alert('Please select at least one file.');
            return;
        }
        
        // Check if at least one panel is selected
        if (!panel1Value && !panel2Value && !panel3Value && !panel4Value && !panel5Value) {
            alert('Please select at least one panel.');
            return;
        }
        
        // Submit the form
        document.getElementById('submission-form').submit();
    });
    
    // Function to show assignment details in modal
    function showAssignmentDetails(assignments) {
        const modal = document.getElementById('assignmentModal');
        const panelistsList = document.getElementById('panelists-list');
        const adviserName = document.getElementById('adviser-name');
        
        // Clear previous content
        panelistsList.innerHTML = '';
        adviserName.innerHTML = '';
        
        // Add panelists
        if (assignments.panels && assignments.panels.length > 0) {
            assignments.panels.forEach(panel => {
                const li = document.createElement('li');
                li.textContent = `${panel.position}: ${panel.name}`;
                panelistsList.appendChild(li);
            });
        } else {
            const li = document.createElement('li');
            li.textContent = 'No panelists assigned';
            li.className = 'not-assigned';
            panelistsList.appendChild(li);
        }
        
        // Add adviser
        if (assignments.adviser && assignments.adviser.trim() !== '') {
            adviserName.textContent = assignments.adviser;
        } else {
            adviserName.textContent = 'No adviser assigned';
            adviserName.className = 'not-assigned';
        }
        
        // Show modal
        modal.style.display = 'block';
    }
    
    // Function to close assignment modal
    function closeAssignmentModal() {
        document.getElementById('assignmentModal').style.display = 'none';
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('assignmentModal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
</script>
</div>
</div>
</div>
</body>
</html>