<?php
session_start();

if (!isset($_SESSION['panel_id'])) {
    header("Location: ../../../logout.php");
    exit;
}

include '../../../connection.php';

// Fetch departments
$departments = [];
$query = "SELECT DISTINCT department FROM student";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

$selectedDepartment = $_POST['department'] ?? '';
$panel_id = $_SESSION['panel_id'];
$panel_fullname = $_SESSION['fullname'] ?? 'Panelist';

$stmt = $conn->prepare("SELECT student_id, finaldocu_id FROM finaldocufinal_files 
                        WHERE panel1_id = ? OR panel2_id = ? OR panel3_id = ? OR panel4_id = ? OR panel5_id = ?");

if ($stmt === false) {
    die("Error preparing the query: " . $conn->error);
}

$stmt->bind_param("sssss", $panel_id, $panel_id, $panel_id, $panel_id, $panel_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_id = $row['student_id'];
    $finaldocu_id = $row['finaldocu_id'];
    
    // Check if adviser has reviewed the document first
    $adviserCheck = $conn->prepare("SELECT COUNT(*) as count FROM final_monitoring_form 
                                   WHERE finaldocu_id = ? AND adviser_id IS NOT NULL");
    $adviserCheck->bind_param("i", $finaldocu_id);
    $adviserCheck->execute();
    $adviserResult = $adviserCheck->get_result();
    $adviserData = $adviserResult->fetch_assoc();
    $finaldocu_advised = ($adviserData['count'] > 0);
    $adviserCheck->close();
    
    // If finaldocu is not advised, check if route3 was submitted by adviser for this student
    if (!$finaldocu_advised) {
        // Get route3_id for this student
        $route3Check = $conn->prepare("SELECT route3_id FROM route3final_files WHERE student_id = ?");
        $route3Check->bind_param("s", $student_id);
        $route3Check->execute();
        $route3Result = $route3Check->get_result();
        
        if ($route3Result && $route3Row = $route3Result->fetch_assoc()) {
            $route3_id = $route3Row['route3_id'];
            
            // Check if route3 was advised (has adviser submission)
            $route3ApprovalCheck = $conn->prepare("SELECT COUNT(*) as count FROM final_monitoring_form 
                                                  WHERE route3_id = ? AND adviser_id IS NOT NULL");
            $route3ApprovalCheck->bind_param("i", $route3_id);
            $route3ApprovalCheck->execute();
            $route3ApprovalResult = $route3ApprovalCheck->get_result();
            $route3_advised = ($route3ApprovalResult && $route3ApprovalResult->fetch_assoc()['count'] > 0);
            $route3ApprovalCheck->close();
            
            if ($route3_advised) {
                // If route3 was advised, no need to show alert
                $finaldocu_advised = true;
            }
        }
        $route3Check->close();
    }
    
    // Only show alert if no submission in either route
    if (!$finaldocu_advised) {
        // Display message that adviser needs to review first
        echo "<script>alert('The adviser must review this document first before panel members can access it.');</script>";
    }
} else {
    $student_id = null;
    $finaldocu_id = null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dateSubmitted'])) {
    $dateSubmittedArr = $_POST['dateSubmitted'];
    $chapterArr = $_POST['chapter'];
    $feedbackArr = $_POST['feedback'];
    $paragraphNumberArr = $_POST['paragraphNumber'];
    $pageNumberArr = $_POST['pageNumber'];
    $panelNameArr = $_POST['panelName'];
    $dateReleasedArr = $_POST['dateReleased'];
    $finaldocu = $_POST['finaldocu'];
    $finaldocu_id = $_POST['finaldocu_id'];
    $student_id = $_POST['student_id'];

    // Prepare the query
    $stmt = $conn->prepare("INSERT INTO final_monitoring_form (panel_id, panel_name, date_submitted, chapter, feedback, paragraph_number, page_number, date_released, finaldocu, finaldocu_id, student_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Loop over the arrays and insert data
    for ($i = 0; $i < count($chapterArr); $i++) {
        $dateSubmitted = $dateSubmittedArr[$i];
        $chapter = $chapterArr[$i];
        $feedback = $feedbackArr[$i];
        $paragraphNumber = $paragraphNumberArr[$i];
        $pageNumber = $pageNumberArr[$i];
        $panelName = $panelNameArr[$i];
        $dateReleased = $dateReleasedArr[$i];

        $stmt->bind_param(
            "ssssssisssi",
            $panel_id,
            $panelName,
            $dateSubmitted,
            $chapter,
            $feedback,
            $paragraphNumber,
            $pageNumber,
            $dateReleased,
            $finaldocu,
            $finaldocu_id,
            $student_id
        );

        if (!$stmt->execute()) {
            echo "<script>alert('Error on row $i: " . addslashes($stmt->error) . "');</script>";
            break;
        }
    }

    echo "<script>alert('Form submitted successfully.'); window.location.href=window.location.href;</script>";
    exit;
}

$currentPanelPosition = '';
if (isset($_SESSION['panel_id'])) {
    $stmt = $conn->prepare("SELECT position FROM panel WHERE panel_id = ?");
    $stmt->bind_param("i", $_SESSION['panel_id']);
    $stmt->execute();
    $stmt->bind_result($currentPanelPosition);
    $stmt->fetch();
    $stmt->close();
}

// Add a function to check if all routes are approved for a student
function checkAllRoutesApproved($conn, $student_id) {
    // Check Route 1 status
    $route1Approved = false;
    
    // First check if there are any Route 1 forms
    $totalStmt = $conn->prepare("
        SELECT COUNT(*) as total_count 
        FROM final_monitoring_form 
        WHERE student_id = ? 
        AND routeNumber = 'Route 1'
    ");
    $totalStmt->bind_param("s", $student_id);
    $totalStmt->execute();
    $result = $totalStmt->get_result();
    $totalRow = $result->fetch_assoc();
    $route1_total = (int)$totalRow['total_count'];
    $totalStmt->close();
    
    // Then check how many are approved
    $stmt = $conn->prepare("
        SELECT COUNT(*) as approved_count 
        FROM final_monitoring_form 
        WHERE student_id = ? 
        AND routeNumber = 'Route 1'
        AND (status = 'Approved' OR status = 'approved')
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $route1_approved_count = 0;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $route1_approved_count = (int)$row['approved_count'];
        // Only mark as approved if ALL forms are approved
        $route1Approved = ($route1_total > 0 && $route1_approved_count == $route1_total);
    }
    $stmt->close();

    // Check Route 2 status
    $route2Approved = false;
    
    // First check if there are any Route 2 forms
    $totalStmt = $conn->prepare("
        SELECT COUNT(*) as total_count 
        FROM final_monitoring_form 
        WHERE student_id = ? 
        AND routeNumber = 'Route 2'
    ");
    $totalStmt->bind_param("s", $student_id);
    $totalStmt->execute();
    $result = $totalStmt->get_result();
    $totalRow = $result->fetch_assoc();
    $route2_total = (int)$totalRow['total_count'];
    $totalStmt->close();
    
    // Then check how many are approved
    $stmt = $conn->prepare("
        SELECT COUNT(*) as approved_count 
        FROM final_monitoring_form 
        WHERE student_id = ? 
        AND routeNumber = 'Route 2'
        AND (status = 'Approved' OR status = 'approved')
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $route2_approved_count = 0;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $route2_approved_count = (int)$row['approved_count'];
        // Only mark as approved if ALL forms are approved
        $route2Approved = ($route2_total > 0 && $route2_approved_count == $route2_total);
    }
    $stmt->close();

    // Check Route 3 status
    $route3Approved = false;
    
    // First check if there are any Route 3 forms
    $totalStmt = $conn->prepare("
        SELECT COUNT(*) as total_count 
        FROM final_monitoring_form 
        WHERE student_id = ? 
        AND routeNumber = 'Route 3'
    ");
    $totalStmt->bind_param("s", $student_id);
    $totalStmt->execute();
    $result = $totalStmt->get_result();
    $totalRow = $result->fetch_assoc();
    $route3_total = (int)$totalRow['total_count'];
    $totalStmt->close();
    
    // Then check how many are approved
    $stmt = $conn->prepare("
        SELECT COUNT(*) as approved_count 
        FROM final_monitoring_form 
        WHERE student_id = ? 
        AND routeNumber = 'Route 3'
        AND (status = 'Approved' OR status = 'approved')
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $route3_approved_count = 0;
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $route3_approved_count = (int)$row['approved_count'];
        // Only mark as approved if ALL forms are approved
        $route3Approved = ($route3_total > 0 && $route3_approved_count == $route3_total);
    }
    $stmt->close();

    return [
        'route1' => $route1Approved,
        'route2' => $route2Approved,
        'route3' => $route3Approved,
        'all_approved' => ($route1Approved && $route2Approved && $route3Approved),
        'route1_counts' => [
            'total' => $route1_total,
            'approved' => $route1_approved_count
        ],
        'route2_counts' => [
            'total' => $route2_total,
            'approved' => $route2_approved_count
        ],
        'route3_counts' => [
            'total' => $route3_total,
            'approved' => $route3_approved_count
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Thesis Routing System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    
    <style>
:root {
--primary: #4366b3;
--primary-light: #0a3885;
--accent: #4a6fd1;
--light: #f5f7fd;
--dark: #333;
--success: #28a745;
--shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
--border: #e0e0e0;
--white: #ffffff;
--hover: #f5f7fd;
--active: #e5ebf8;
--text-light: #777777;
--radius: 8px;
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
.action-label {
    text-align: center;
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
    width: 100%;
    height: 100%;
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

.form-grid-container {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
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
            word-break: break-word;
            overflow-wrap: break-word;
            min-height: 40px;
        }
        
        /* Specific style for the feedback cell (3rd column) */
        .form-grid-container > div:nth-child(10n + 3) {
            text-align: left;
            justify-content: flex-start;
            overflow-y: visible;
            max-height: none; /* Remove height limit */
            height: auto; /* Allow height to adjust to content */
            white-space: pre-wrap; /* Preserve line breaks and spacing */
        }
        
        /* Style for feedback cell class used in JavaScript */
        .feedback-cell {
            text-align: left !important;
            justify-content: flex-start !important;
            overflow-y: visible !important;
            max-height: none !important;
            height: auto !important;
            white-space: pre-wrap !important;
            word-break: break-word !important;
            overflow-wrap: break-word !important;
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

/* Responsive styles */
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

/* Dropdown menu styling */
.nav-menu {
    display: flex;
    flex-direction: column;
    padding: 1rem 0;
    gap: 4px;
}

.menu-item {
    display: flex;
    flex-direction: column;
}

.menu-header {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s ease;
    gap: 12px;
}

.icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
}

.icon svg {
    width: 18px;
    height: 18px;
    stroke: var(--primary);
}

.menu-header span {
    flex: 1;
    font-size: 14px;
    color: var(--dark);
}

.dropdown-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;
}

.dropdown-icon svg {
    width: 16px;
    height: 16px;
    stroke: #777777;
}

.expanded .dropdown-icon {
    transform: rotate(180deg);
}

.menu-header:hover {
    background-color: var(--light);
}

.dropdown-content {
    display: none;
    flex-direction: column;
    padding-left: 2.5rem;
}

.dropdown-content.show {
    display: flex;
}

.submenu-item {
    padding: 0.6rem 1rem;
    font-size: 13px;
    color: #777777;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.submenu-item:hover {
    background-color: var(--light);
    color: var(--primary);
}

/* Additional utilities */
input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.selected {
    background-color: var(--light);
}

.submit-button a {
    margin-left: 50px;
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}
.navigation a{
    color: var(--accent);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}
.submit-button a{
    margin-left: 50px;
}

/* Search bar styling */
.search-container {
    margin: 15px 0;
    width: 100%;
    display: flex;
    justify-content: flex-start;
    align-items: center;
}

.search-box {
    padding: 8px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 300px;
    font-size: 14px;
    margin-bottom: 15px;
}
    </style>
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
                <a href="../homepage.php">Home Page</a>
            </div>
            <div class="dropdown-container">
    <?php
    // Check if the current user is a panel1 for any document
    $checkPanel1Query = "SELECT COUNT(*) as count FROM finaldocufinal_files WHERE panel1_id = ? OR panel2_id = ? OR panel3_id = ? OR panel4_id = ? OR panel5_id = ?";
    $checkStmt = $conn->prepare($checkPanel1Query);
    $checkStmt->bind_param("sssss", $panel_id, $panel_id, $panel_id, $panel_id, $panel_id);
    $checkStmt->execute();
    $isPanelResult = $checkStmt->get_result();
    $isPanel1 = ($isPanelResult->fetch_assoc()['count'] > 0);
    
    // Only show the department dropdown if user is panel1 for any document
    if ($isPanel1): ?>
        <form method="POST">
            <select name="department" onchange="this.form.submit()">
                <option value="">Select Department</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?= htmlspecialchars($department) ?>" <?= $selectedDepartment == $department ? 'selected' : '' ?>>
                        <?= htmlspecialchars($department) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>
            <div class="user-info">
                <div class="routeNo" style="margin-right: 20px;">Final - Final Document</div>
                <div class="vl"></div>
                <span class="role">Panelist:</span>
                <span class="user-name"><?= htmlspecialchars($panel_fullname) ?></span>
            </div>
        </div>

        <div class="main-content">
        <nav class="sidebar">
                <nav class="nav-menu">
                    <!-- Title Proposal Section -->
                    <div class="menu-item dropdown">
                        <div class="menu-header">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                            <span>Title Proposal</span>
                            <div class="dropdown-icon expanded">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
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
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                                </svg>
                            </div>
                            <span>Final</span>
                            <div class="dropdown-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
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
                </nav>
                <div class="logout">
                    <a href="../../../logout.php">Logout</a>
                </div>
            </nav>

            <div class="content" id="content-area">
            
            <!-- Search bar -->
            <div class="search-container">
                <input type="text" id="searchInput" class="search-box" placeholder="Search by leader name..." onkeyup="searchTable()">
            </div>
            
            <?php
                $query = "
                    SELECT 
                        f.finaldocu, 
                        f.department, 
                        f.student_id, 
                        f.finaldocu_id, 
                        f.controlNo, 
                        f.fullname, 
                        f.group_number,
                        f.title
                    FROM 
                        finaldocufinal_files f
                    WHERE 
                        (f.panel1_id = ? OR f.panel2_id = ? OR f.panel3_id = ? OR f.panel4_id = ? OR f.panel5_id = ?)
                    " . ($selectedDepartment ? " AND f.department = ?" : "");

                $stmt = $conn->prepare($query);
                
                if ($stmt === false) {
                    echo "Error in preparing statement: " . htmlspecialchars($conn->error) . "<br>";
                    echo "Query: " . htmlspecialchars($query);
                    exit;
                }

                if ($selectedDepartment) {
                    $stmt->bind_param("ssssss", $panel_id, $panel_id, $panel_id, $panel_id, $panel_id, $selectedDepartment);
                } else {
                    $stmt->bind_param("sssss", $panel_id, $panel_id, $panel_id, $panel_id, $panel_id);
                }

                $stmt->execute();
                $result = $stmt->get_result();
                $files_to_display = [];

                // For each file, check if adviser approved either route3 or finaldocu
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $student_id = $row['student_id'];
                        $finaldocu_id = $row['finaldocu_id'];
                        $can_display = false;
                        
                        // Check if finaldocu has any adviser submission
                        $check_finaldocu = $conn->prepare("SELECT COUNT(*) as count FROM final_monitoring_form 
                                                      WHERE finaldocu_id = ? AND adviser_id IS NOT NULL");
                        $check_finaldocu->bind_param("i", $finaldocu_id);
                        $check_finaldocu->execute();
                        $finaldocu_result = $check_finaldocu->get_result();
                        $finaldocu_has_adviser = ($finaldocu_result && $finaldocu_result->fetch_assoc()['count'] > 0);
                        $check_finaldocu->close();
                        
                        if ($finaldocu_has_adviser) {
                            $can_display = true;
                        } else {
                            // Get route3_id
                            $get_route3 = $conn->prepare("SELECT route3_id FROM route3final_files WHERE student_id = ?");
                            $get_route3->bind_param("s", $student_id);
                            $get_route3->execute();
                            $route3_result = $get_route3->get_result();
                            
                            if ($route3_result && $route3_row = $route3_result->fetch_assoc()) {
                                $route3_id = $route3_row['route3_id'];
                                
                                // Check if route3 has any adviser submission
                                $check_route3 = $conn->prepare("SELECT COUNT(*) as count FROM final_monitoring_form 
                                                              WHERE route3_id = ? AND adviser_id IS NOT NULL");
                                $check_route3->bind_param("i", $route3_id);
                                $check_route3->execute();
                                $route3_result = $check_route3->get_result();
                                $route3_has_adviser = ($route3_result && $route3_result->fetch_assoc()['count'] > 0);
                                $check_route3->close();
                                
                                if ($route3_has_adviser) {
                                    $can_display = true;
                                }
                            }
                            $get_route3->close();
                        }
                        
                        if ($can_display) {
                            $files_to_display[] = $row;
                        }
                    }
                }

                if (count($files_to_display) > 0) {
                    echo "
                    <table>
                        <thead>
                            <tr>
                                <th>Control No.</th>
                                <th>Leader</th>
                                <th>Group No.</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th class='action-label'>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                    ";

                    foreach ($files_to_display as $row) {
                        $filePath = htmlspecialchars($row['finaldocu'], ENT_QUOTES);
                        $finaldocu_id = htmlspecialchars($row['finaldocu_id'], ENT_QUOTES);
                        $student_id = htmlspecialchars($row['student_id'], ENT_QUOTES);
                        $fileName = basename($filePath);
                        $controlNo = htmlspecialchars($row['controlNo'], ENT_QUOTES);
                        $fullname = htmlspecialchars($row['fullname'], ENT_QUOTES);
                        $groupNo = htmlspecialchars($row['group_number'], ENT_QUOTES);
                        $title = htmlspecialchars($row['title'], ENT_QUOTES);
                        
                        // Check all routes status
                        $routeStatus = checkAllRoutesApproved($conn, $student_id);
                        
                        // Determine status label and color
                        $statusLabel = '';
                        $statusColor = '';
                        
                        if ($routeStatus['all_approved']) {
                            $statusLabel = 'Complete';
                            $statusColor = 'green';
                        } else {
                            // Show which routes are approved and the approval counts
                            $approvedRoutes = [];
                            if (isset($routeStatus['route1_counts'])) {
                                if ($routeStatus['route1']) {
                                    $approvedRoutes[] = "Route 1 ({$routeStatus['route1_counts']['approved']}/{$routeStatus['route1_counts']['total']})";
                                } else if ($routeStatus['route1_counts']['total'] > 0) {
                                    $approvedRoutes[] = "Route 1 ({$routeStatus['route1_counts']['approved']}/{$routeStatus['route1_counts']['total']})";
                                }
                            }
                            
                            if (isset($routeStatus['route2_counts'])) {
                                if ($routeStatus['route2']) {
                                    $approvedRoutes[] = "Route 2 ({$routeStatus['route2_counts']['approved']}/{$routeStatus['route2_counts']['total']})";
                                } else if ($routeStatus['route2_counts']['total'] > 0) {
                                    $approvedRoutes[] = "Route 2 ({$routeStatus['route2_counts']['approved']}/{$routeStatus['route2_counts']['total']})";
                                }
                            }
                            
                            if (isset($routeStatus['route3_counts'])) {
                                if ($routeStatus['route3']) {
                                    $approvedRoutes[] = "Route 3 ({$routeStatus['route3_counts']['approved']}/{$routeStatus['route3_counts']['total']})";
                                } else if ($routeStatus['route3_counts']['total'] > 0) {
                                    $approvedRoutes[] = "Route 3 ({$routeStatus['route3_counts']['approved']}/{$routeStatus['route3_counts']['total']})";
                                }
                            }
                            
                            if (count($approvedRoutes) > 0) {
                                $statusLabel = 'In Progress: ' . implode(', ', $approvedRoutes);
                                $statusColor = 'orange';
                            } else {
                                $statusLabel = 'Pending';
                                $statusColor = 'red';
                            }
                        }

                        echo "
                            <tr>
                                <td>$controlNo</td>
                                <td>$fullname</td>
                                <td>$groupNo</td>
                                <td>$title</td>
                                <td><span style='color: $statusColor; font-weight: bold;'>$statusLabel</span></td>
                                <td style='text-align: center;'>
                                    <button class='view-button' onclick=\"viewFile('$filePath', '$finaldocu_id', '$student_id')\">View</button>
                                </td>
                            </tr>
                        ";
                    }

                    echo "
                        </tbody>
                    </table>
                    ";
                } else {
                    echo "<p>No files uploaded yet.</p>";
                }

                $stmt->close();
                ?>
            </div>
        </div>
    </div>

    <!-- Modal Viewer -->
    <div id="fileModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">×</span>
            <div class="modal-layout">
                <div id="fileModalContent" class="file-preview-section"></div>
                <div id="routingForm" class="routing-form-section"></div>
            </div>
        </div>
    </div>

    <script>
        const panelName = <?= json_encode($panel_fullname) ?>;
        const currentPanelPosition = "<?php echo $currentPanelPosition; ?>";

        function viewFile(filePath, finaldocu_id, student_id) {
            // First check if the adviser has approved route3 final
            fetch(`check_adviser_finaldocu.php?finaldocu_id=${finaldocu_id}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.hasReviewed) {
                        alert("This document cannot be viewed yet. " + data.message);
                        return;
                    }
                    
                    // Continue with normal file view process
                    const modal = document.getElementById("fileModal");
                    const contentArea = document.getElementById("fileModalContent");
                    const routingForm = document.getElementById("routingForm");
                    const extension = filePath.split('.').pop().toLowerCase();

                    modal.style.display = "flex";
                    contentArea.innerHTML = "<div style='display: flex; justify-content: center; align-items: center; height: 100%;'><div style='text-align: center;'><div class='spinner'></div><p style='margin-top: 10px;'>Loading file...</p></div></div>";
                    routingForm.innerHTML = "";

                    if (extension === "pdf") {
                        contentArea.innerHTML = `<iframe src="${filePath}" width="100%" height="100%" style="border:none;"></iframe>`;
                    } else if (extension === "docx") {
                        fetch(filePath)
                            .then(res => res.arrayBuffer())
                            .then(buffer => mammoth.convertToHtml({ arrayBuffer: buffer }))
                            .then(result => contentArea.innerHTML = `<div class="file-content" style="padding: 2rem;">${result.value}</div>`)
                            .catch(() => contentArea.innerHTML = "<div style='text-align: center; padding: 2rem;'><p style='color: #dc3545;'>Error loading file.</p></div>");
                    } else {
                        contentArea.innerHTML = "<div style='text-align: center; padding: 2rem;'><p style='color: #dc3545;'>Unsupported file type.</p></div>";
                    }

                    const today = new Date().toISOString().split('T')[0];

                    routingForm.innerHTML = `
                        <form method="POST">
                            <input type="hidden" name="finaldocu" value="${filePath}">
                            <input type="hidden" name="student_id" value="${student_id}">
                            <input type="hidden" name="finaldocu_id" value="${finaldocu_id}">

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
                            <div style="margin-top: 1rem; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                                <h4 style="margin: 0;">ROUTING MONITORING FORM</h4>
                                <div>
                                    <button type="button" id="toggleFormsButton" onclick="toggleForms('${student_id}')">Show less</button>
                                </div>
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
                                <div><strong>Route Number</strong></div>
                                <div><strong>Status</strong></div>
                                <div><strong>Action</strong></div>
                            </div>

                            <!-- Container for submitted form data -->
                            <div id="submittedFormsContainer" class="form-grid-container"></div>
                            <div id="noFormsMessage" style="margin-top: 10px; color: gray;"></div>
                        </form>
                    `;
                    
                    // Load forms by default when file is opened
                    loadAllForms(student_id);
                })
                .catch(error => {
                    console.error('Error checking adviser approval:', error);
                    alert('Unable to verify adviser approval status. Please try again later or contact support.');
                });
        }

        function closeModal() {
            document.getElementById("fileModal").style.display = "none";
        }


        let formsVisible = true;

        function toggleForms(student_id) {
            const formDataContainer = document.getElementById("submittedFormsContainer");
            const noFormsMessage = document.getElementById("noFormsMessage");
            const toggleButton = document.getElementById("toggleFormsButton");

            if (formsVisible) {
                // Hide forms
                formDataContainer.innerHTML = "";
                noFormsMessage.innerText = "Forms are hidden. Click 'Show all' to view them.";
                toggleButton.textContent = "Show all";
                formsVisible = false;
            } else {
                // Show forms
                loadAllForms(student_id);
            }
        }

        function loadAllForms(student_id) {
            const formDataContainer = document.getElementById("submittedFormsContainer");
            const noFormsMessage = document.getElementById("noFormsMessage");
            const toggleButton = document.getElementById("toggleFormsButton");

            // Show loading spinner
            formDataContainer.innerHTML = "<div style='grid-column: span 9; display: flex; justify-content: center; padding: 1rem;'><div class='spinner'></div></div>";

            // Fetch data
            fetch('route3get_all_forms.php?student_id=' + student_id)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    noFormsMessage.innerText = "No routing forms submitted yet.";
                    formDataContainer.innerHTML = "";
                    return;
                }

                noFormsMessage.innerText = ""; // Clear message
                formDataContainer.innerHTML = ""; // Clear container

                data.forEach(form => {
                    const formId = form.id;
                    const statusValue = (form.status || 'Pending').trim();

                    let submittedBy = 'N/A';
                    if (form.adviser_name) {
                        submittedBy = `${form.adviser_name} - Adviser`;
                    } else if (form.panel_name) {
                        submittedBy = `${form.panel_name} - Panel`;
                    }

                    formDataContainer.innerHTML += `
                        <div>${form.date_submitted}</div>
                        <div>${form.chapter}</div>
                        <div class="feedback-cell">${form.feedback}</div>
                        <div>${form.paragraph_number}</div>
                        <div>${form.page_number}</div>
                        <div>${submittedBy}</div>
                        <div>${form.date_released}</div>
                        <div>${form.routeNumber}</div>
                        <div>
                            <select id="statusSelect_${formId}" onchange="enableSaveButton(${formId})">
                                <option value="Pending" ${statusValue === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Approved" ${statusValue === 'Approved' ? 'selected' : ''}>Approved</option>
                                <option value="For Revision" ${statusValue === 'For Revision' ? 'selected' : ''}>For Revision</option>
                            </select>
                        </div>
                        <div>
                            <button id="saveButton_${formId}" onclick="saveStatus(${formId}, event)" disabled>Save</button>
                        </div>
                    `;
                });

                toggleButton.textContent = "Show less";
                formsVisible = true;
            })
            .catch(error => {
                console.error('Error fetching forms:', error);
                noFormsMessage.innerText = "Error loading forms. Please try again.";
                formDataContainer.innerHTML = "";
            });
        }

        function autoGrow(textarea) {
            textarea.style.height = 'auto'; // Reset height
            textarea.style.height = textarea.scrollHeight + 'px'; // Set to scrollHeight
        }

        function saveStatus(formId, event) {
            event.preventDefault();  // Prevent any form submission

            const statusSelect = document.getElementById(`statusSelect_${formId}`);
            const newStatus = statusSelect.value;

            if (!newStatus) {
                alert("Please select a status.");
                return;
            }

            fetch('update_form_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: formId,
                    status: newStatus  // Update to status (changed from adviser_status)
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log("Response from update_form_status.php:", data);
                if (data.success) {
                    const saveButton = document.getElementById(`saveButton_${formId}`);
                    saveButton.disabled = true;
                    saveButton.textContent = "Saved ✔";
                    saveButton.style.backgroundColor = "green";
                    saveButton.style.color = "white";
                } else {
                    alert("Failed to save status: " + data.message);
                }
            })
            .catch(error => {
                alert("Error saving status.");
                console.error(error);
            });
        }

        function enableSaveButton(formId) {
            const saveButton = document.getElementById(`saveButton_${formId}`);
            saveButton.disabled = false;
        }

        // Search function for the table
        function searchTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.querySelector("table tbody");
            if (!table) return;
            
            const rows = table.getElementsByTagName("tr");
            
            for (let i = 0; i < rows.length; i++) {
                const leaderCell = rows[i].getElementsByTagName("td")[1]; // Index 1 is the Leader column
                if (leaderCell) {
                    const leaderName = leaderCell.textContent || leaderCell.innerText;
                    if (leaderName.toUpperCase().indexOf(filter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuHeaders = document.querySelectorAll('.menu-header');
    const path = window.location.pathname;

    menuHeaders.forEach(header => {
        const dropdownContent = header.nextElementSibling;
        const label = header.querySelector('span').textContent.trim().toLowerCase();

        // Default: close all
        header.querySelector('.dropdown-icon').classList.remove('expanded');
        dropdownContent.classList.remove('show');

        // Expand the right one based on URL
        if (path.includes('/titleproposal/') && label.includes('title proposal')) {
            header.querySelector('.dropdown-icon').classList.add('expanded');
            dropdownContent.classList.add('show');
        } else if (path.includes('/final/') && label === 'final') {
            header.querySelector('.dropdown-icon').classList.add('expanded');
            dropdownContent.classList.add('show');
        }

        // Accordion behavior
        header.addEventListener('click', function() {
            menuHeaders.forEach(h => {
                const icon = h.querySelector('.dropdown-icon');
                const content = h.nextElementSibling;
                
                if (h !== this) {
                    icon.classList.remove('expanded');
                    content.classList.remove('show');
                }
            });

            // Toggle the clicked one
            this.querySelector('.dropdown-icon').classList.toggle('expanded');
            dropdownContent.classList.toggle('show');
        });
    });
});
</script>