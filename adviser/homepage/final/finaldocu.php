<?php
session_start();

if (!isset($_SESSION['adviser_id'])) {
    header("Location: ../../../logout.php");
    exit;
}

include '../../../connection.php';

$adviser_id = $_SESSION['adviser_id'];
$fullname = $_SESSION['fullname'] ?? 'Adviser';

// Query to get student id and finaldocu_id based on adviser_id
$stmt = $conn->prepare("SELECT student_id, finaldocu_id FROM finaldocufinal_files WHERE adviser_id = ?");
if ($stmt === false) {
    die("Error preparing the query: " . $conn->error); // This will show the MySQL error
}

$stmt->bind_param("s", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the student id and finaldocu_id
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_id = $row['student_id'];
    $finaldocu_id = $row['finaldocu_id']; // Now you have the finaldocu_id
} else {
    // Handle case if no student is found (optional)
    $student_id = null;
    $finaldocu_id = null;
}

// Handle form submission
$showModal = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dateSubmitted'])) {
    $dateSubmittedArr = $_POST['dateSubmitted'];
    $chapterArr = $_POST['chapter'];
    $feedbackArr = $_POST['feedback'];
    $paragraphNumberArr = $_POST['paragraphNumber'];
    $pageNumberArr = $_POST['pageNumber'];
    $adviserNameArr = $_POST['adviserName'];
    $dateReleasedArr = $_POST['dateReleased'];
    $finaldocu = $_POST['docuRoute3'];
    $finaldocu_id = $_POST['finaldocu_id'];
    $student_id = $_POST['student_id'];
    $status = $_POST['status'];
    $routeNumberArr = $_POST['routeNumber'];

    // Prepare SQL for inserting form data
    $stmt = $conn->prepare("INSERT INTO final_monitoring_form 
    (adviser_id, adviser_name, student_id, date_submitted, chapter, feedback, paragraph_number, page_number, date_released, docuRoute3, finaldocu_id, status, routeNumber) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing the insert query: " . $conn->error);
    }

    // Loop through the form data and insert each row
    for ($i = 0; $i < count($chapterArr); $i++) {
        $dateSubmitted = $dateSubmittedArr[$i];
        $chapter = $chapterArr[$i];
        $feedback = $feedbackArr[$i];
        $paragraphNumber = $paragraphNumberArr[$i];
        $pageNumber = $pageNumberArr[$i];
        $adviserName = $adviserNameArr[$i];
        $dateReleased = $dateReleasedArr[$i];
        $routeNumber = $routeNumberArr[$i];

        // Bind parameters including the finaldocu_id
        $stmt->bind_param(
            "ssssssissssss",  // 13 specifiers
            $adviser_id, 
            $adviserName, 
            $student_id, 
            $dateSubmitted, 
            $chapter, 
            $feedback, 
            $paragraphNumber, 
            $pageNumber, 
            $dateReleased, 
            $finaldocu,
            $finaldocu_id,
            $status,
            $routeNumber
        );
        
        // Execute the statement
        if (!$stmt->execute()) {
            echo "<script>alert('Error on row $i: " . addslashes($stmt->error) . "');</script>";
            break;
        }
    }

    echo "<script>alert('Form submitted successfully.'); window.location.href=window.location.href;</script>";
    exit;
}

// Add a function to check if all routes are approved for a student
function checkAllRoutesApproved($conn, $student_id) {
    // Check Route 1 status
    $route1Approved = false;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as approved_count 
        FROM final_monitoring_form 
        WHERE student_id = ? 
        AND route1_id IS NOT NULL
        AND (status = 'Approved' OR status = 'approved')
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $route1Approved = ((int)$row['approved_count'] > 0);
    }
    $stmt->close();

    // Check Route 2 status
    $route2Approved = false;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as approved_count 
        FROM final_monitoring_form 
        WHERE student_id = ? 
        AND route2_id IS NOT NULL
        AND (status = 'Approved' OR status = 'approved')
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $route2Approved = ((int)$row['approved_count'] > 0);
    }
    $stmt->close();

    // Check Route 3 status
    $route3Approved = false;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as approved_count 
        FROM final_monitoring_form 
        WHERE student_id = ? 
        AND route3_id IS NOT NULL
        AND (status = 'Approved' OR status = 'approved')
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $route3Approved = ((int)$row['approved_count'] > 0);
    }
    $stmt->close();

    return [
        'route1' => $route1Approved,
        'route2' => $route2Approved,
        'route3' => $route3Approved,
        'all_approved' => ($route1Approved && $route2Approved && $route3Approved)
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
.action-label {
    text-align: center;
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
            <div class="user-info">
                <div class="routeNo" style="margin-right: 20px;">Final - Final Document</div>
                <div class="vl"></div>
                <span class="role">Adviser:</span>
                <span class="user-name"><?= htmlspecialchars($fullname) ?></span>
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
                        finaldocu, 
                        student_id, 
                        finaldocu_id, 
                        department, 
                        group_number, 
                        controlNo, 
                        fullname, 
                        title 
                    FROM finaldocufinal_files 
                    WHERE adviser_id = ?";

                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $adviser_id);

                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
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

                    while ($row = $result->fetch_assoc()) {
                        $filePath = htmlspecialchars($row['finaldocu'], ENT_QUOTES);
                        $student_id = htmlspecialchars($row['student_id'], ENT_QUOTES);
                        $finaldocu_id = htmlspecialchars($row['finaldocu_id'], ENT_QUOTES);
                        $groupNo = htmlspecialchars($row['group_number'], ENT_QUOTES);
                        $controlNo = htmlspecialchars($row['controlNo'], ENT_QUOTES);
                        $fullName = htmlspecialchars($row['fullname'], ENT_QUOTES);
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
                            // Show which routes are approved
                            $approvedRoutes = [];
                            if ($routeStatus['route1']) $approvedRoutes[] = 'Route 1';
                            if ($routeStatus['route2']) $approvedRoutes[] = 'Route 2';
                            if ($routeStatus['route3']) $approvedRoutes[] = 'Route 3';
                            
                            if (count($approvedRoutes) > 0) {
                                $statusLabel = 'Approved: ' . implode(', ', $approvedRoutes);
                                $statusColor = 'orange';
                            } else {
                                $statusLabel = 'Pending';
                                $statusColor = 'red';
                            }
                        }

                        echo "
                            <tr>
                                <td>$controlNo</td>
                                <td>$fullName</td>
                                <td>$groupNo</td>
                                <td>$title</td>
                                <td><span style='color: $statusColor; font-weight: bold;'>$statusLabel</span></td>
                                <td style='text-align: center;'>
                                    <button class='view-button' onclick=\"viewFile('$filePath', '$student_id', '$finaldocu_id')\">View</button>
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

</html>
<script>
function viewFile(filePath, student_id, finaldocu_id) {
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
            .then(result => contentArea.innerHTML = `<div class="file-content">${result.value}</div>`)
            .catch(() => contentArea.innerHTML = "<div style='text-align: center; padding: 2rem;'><p style='color: #dc3545;'>Error loading file.</p></div>");
    } else {
        contentArea.innerHTML = "<div style='text-align: center; padding: 2rem;'><p style='color: #dc3545;'>Unsupported file type.</p></div>";
    }

    const adviserName = <?= json_encode($fullname) ?>;

    routingForm.innerHTML = `
        <form method="POST">
            <input type="hidden" name="finaldocu" value="${filePath}">
            <input type="hidden" name="student_id" value="${student_id}">
            <input type="hidden" name="finaldocu_id" value="${finaldocu_id}">
            <input type="hidden" name="status" value="Approved">

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
                    <button type="button" onclick="doneEditing()" style="background-color: var(--success); color: white;">Done</button>
                    <button type="button" id="toggleFormsBtn" onclick="toggleForms('${student_id}')">Hide Forms</button>
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
    
    // Load all forms by default
    loadAllForms(student_id);
}

function closeModal() {
    document.getElementById("fileModal").style.display = "none";
}

function doneEditing() {
    // Get the form elements
    const form = document.querySelector('#routingForm form');
    const student_id = form.querySelector('input[name="student_id"]').value;
    const finaldocu_id = form.querySelector('input[name="finaldocu_id"]').value;
    const finaldocu = form.querySelector('input[name="finaldocu"]').value;
    const adviserName = <?= json_encode($fullname) ?>;
    
    // Confirm the action
    if (confirm("Are you sure you want to mark this as done without additional comments? This indicates you've reviewed the document and have no further feedback.")) {
        // Create a form data object
        const formData = new FormData();
        
        // Add a single entry with "No comments" as feedback
        formData.append('dateSubmitted[]', new Date().toISOString().split('T')[0]);
        formData.append('chapter[]', 'All');
        formData.append('feedback[]', 'No additional comments. Document reviewed.');
        formData.append('paragraphNumber[]', '0');
        formData.append('pageNumber[]', '0');
        formData.append('adviserName[]', adviserName);
        formData.append('dateReleased[]', new Date().toISOString().split('T')[0]);
        formData.append('routeNumber[]', 'Final');
        formData.append('finaldocu', finaldocu);
        formData.append('finaldocu_id', finaldocu_id);
        formData.append('student_id', student_id);
        formData.append('status', 'Approved');
        
        // Submit the form data using fetch
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                alert('Document marked as reviewed with no additional comments.');
                window.location.reload();
            } else {
                throw new Error('Network response was not ok');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to mark document as done. Please try again.');
        });
    }
}

let formsVisible = true;

function loadAllForms(student_id) {
    const formDataContainer = document.getElementById("submittedFormsContainer");
    const noFormsMessage = document.getElementById("noFormsMessage");
    
    // Show loading spinner
    formDataContainer.innerHTML = "<div style='grid-column: span 9; display: flex; justify-content: center; padding: 1rem;'><div class='spinner'></div></div>";

    // Fetch data
    fetch('finaldocu_get_all_forms.php?student_id=' + student_id)
        .then(response => response.json())
        .then(data => {
            formDataContainer.innerHTML = ""; // Clear spinner
            
            if (data.length === 0) {
                noFormsMessage.innerText = "No routing forms submitted yet.";
                return;
            }

            noFormsMessage.innerText = ""; // Clear message

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

            formsVisible = true;
        })
        .catch(error => {
            console.error('Error fetching forms:', error);
            noFormsMessage.innerText = "Error loading forms.";
        });
}

function toggleForms(student_id) {
    const formDataContainer = document.getElementById("submittedFormsContainer");
    const noFormsMessage = document.getElementById("noFormsMessage");
    const toggleButton = document.getElementById("toggleFormsBtn");

    if (formsVisible) {
        // Hide forms
        formDataContainer.innerHTML = "";
        noFormsMessage.innerText = "";
        toggleButton.textContent = "Show Forms";
        formsVisible = false;
    } else {
        // Show forms
        toggleButton.textContent = "Hide Forms";
        loadAllForms(student_id);
    }
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
    saveButton.disabled = false;  // Enable the save button
}

<?php if ($showModal): ?>
    window.addEventListener('load', () => {
        viewFile("<?= addslashes($lastFilePath) ?>", "<?= addslashes($student_id) ?>", "<?= addslashes($finaldocu_id) ?>");
    });
<?php endif; ?>
    </script>


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

<script>
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