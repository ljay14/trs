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
    
    // Create array of approved routes for status display
    $approvedRoutes = [];
    if ($route1Approved) $approvedRoutes[] = "Route 1";
    if ($route2Approved) $approvedRoutes[] = "Route 2";
    if ($route3Approved) $approvedRoutes[] = "Route 3";
    
    // Return results
    return [
        'route1_approved' => $route1Approved,
        'route2_approved' => $route2Approved,
        'route3_approved' => $route3Approved,
        'all_approved' => ($route1Approved && $route2Approved && $route3Approved),
        'approved_routes' => $approvedRoutes,
        'approved_count' => count($approvedRoutes)
    ];
}

// Fetch school years
$schoolYears = [];
$schoolYearQuery = "SELECT DISTINCT school_year FROM finaldocufinal_files ORDER BY school_year DESC";
$schoolYearResult = $conn->query($schoolYearQuery);
if ($schoolYearResult && $schoolYearResult->num_rows > 0) {
    while ($row = $schoolYearResult->fetch_assoc()) {
        $schoolYears[] = $row['school_year'];
    }
}

// Fetch semesters
$semesters = [];
$semesterQuery = "SELECT DISTINCT semester FROM student WHERE semester IS NOT NULL AND semester != ''";
$semesterResult = $conn->query($semesterQuery);
if ($semesterResult && $semesterResult->num_rows > 0) {
    while ($row = $semesterResult->fetch_assoc()) {
        // Store original value with original case and formatting
        $semesters[] = $row['semester'];
    }
}

// Make sure all standard semesters are included regardless of database values
$standardSemesters = ['First semester', 'Second semester', 'Summer'];

// Create a normalized array for comparison (lowercase and trimmed)
$normalizedSemesters = array_map(function($sem) {
    return strtolower(trim($sem));
}, $semesters);

// Add standard semesters only if they don't exist (case-insensitive check)
foreach ($standardSemesters as $stdSemester) {
    $normalizedStdSemester = strtolower(trim($stdSemester));
    if (!in_array($normalizedStdSemester, $normalizedSemesters)) {
        $semesters[] = $stdSemester;
        $normalizedSemesters[] = $normalizedStdSemester;
    }
}

// Remove duplicates (case-insensitive)
$uniqueSemesters = [];
$uniqueNormalizedSemesters = [];
foreach ($semesters as $semester) {
    $normalizedSemester = strtolower(trim($semester));
    if (!in_array($normalizedSemester, $uniqueNormalizedSemesters)) {
        $uniqueSemesters[] = $semester;
        $uniqueNormalizedSemesters[] = $normalizedSemester;
    }
}
$semesters = $uniqueSemesters;

// Sort them in logical order
usort($semesters, function($a, $b) {
    $order = ['first semester' => 1, 'second semester' => 2, 'summer' => 3];
    $aOrder = isset($order[strtolower(trim($a))]) ? $order[strtolower(trim($a))] : 999;
    $bOrder = isset($order[strtolower(trim($b))]) ? $order[strtolower(trim($b))] : 999;
    return $aOrder - $bOrder;
});

// Helper functions to get names from IDs
function getPanelName($conn, $panel_id) {
    $stmt = $conn->prepare("SELECT fullname FROM panel WHERE panel_id = ?");
    if ($stmt === false) {
        error_log("Error preparing panel name statement: " . $conn->error);
        return "";
    }
    $stmt->bind_param("i", $panel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['fullname'];
    }
    $stmt->close();
    return "";
}

function getAdviserName($conn, $adviser_id) {
    $stmt = $conn->prepare("SELECT fullname FROM adviser WHERE adviser_id = ?");
    if ($stmt === false) {
        error_log("Error preparing adviser name statement: " . $conn->error);
        return "";
    }
    $stmt->bind_param("i", $adviser_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['fullname'];
    }
    $stmt->close();
    return "";
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['school_year'])) {
    $selectedSchoolYear = $_POST['school_year'];
    $_SESSION['selected_school_year'] = $selectedSchoolYear;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['semester'])) {
    $selectedSemester = $_POST['semester'];
    $_SESSION['selected_semester'] = $selectedSemester;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Load data for selected department
if (isset($_SESSION['selected_department'])) {
    $selectedDepartment = $_POST['department'] ?? $_SESSION['selected_department'] ?? '';
    $selectedSchoolYear = $_POST['school_year'] ?? $_SESSION['selected_school_year'] ?? '';
    $selectedSemester = $_POST['semester'] ?? $_SESSION['selected_semester'] ?? '';
    
    $_SESSION['selected_department'] = $selectedDepartment;
    $_SESSION['selected_school_year'] = $selectedSchoolYear;
    $_SESSION['selected_semester'] = $selectedSemester;

    // Fetch panel data
    $panelStmt = $conn->prepare("SELECT * FROM panel WHERE department = ?");
    if ($panelStmt === false) {
        echo "Error preparing panel statement: " . $conn->error;
    } else {
        $panelStmt->bind_param("s", $selectedDepartment);
        $panelStmt->execute();
        $panelResult = $panelStmt->get_result();
        while ($row = $panelResult->fetch_assoc()) {
            $panel[$row['position']][] = $row['fullname'];
        }
        $panelStmt->close();
    }

    // Fetch adviser data
    $adviserStmt = $conn->prepare("SELECT * FROM adviser WHERE department = ?");
    if ($adviserStmt === false) {
        echo "Error preparing adviser statement: " . $conn->error;
    } else {
        $adviserStmt->bind_param("s", $selectedDepartment);
        $adviserStmt->execute();
        $adviserResult = $adviserStmt->get_result();
        while ($row = $adviserResult->fetch_assoc()) {
            $adviser[] = $row['fullname'];
        }
        $adviserStmt->close();
    }

    // Fetch files
    $sqlQuery = "SELECT 
            fd.finaldocu AS filepath, 
            fd.finaldocu AS filename, 
            fd.date_submitted,
            fd.controlNo,
            fd.group_number,
            fd.fullname,
            fd.title,
            fd.student_id, 
            fd.panel1_id, 
            fd.panel2_id, 
            fd.panel3_id, 
            fd.panel4_id, 
            fd.panel5_id, 
            fd.adviser_id,
            fd.finaldocu_id
         FROM finaldocufinal_files fd
         LEFT JOIN route1final_files r1 ON fd.student_id = r1.student_id
         WHERE fd.department = ?";

    // Create array of parameters (must be variables, not direct values)
    $types = "s";
    $params = [$selectedDepartment];

    // Add school year filter if selected
    if (!empty($selectedSchoolYear)) {
        $sqlQuery .= " AND fd.school_year = ?";
        $types .= "s";
        $params[] = $selectedSchoolYear;
    }

    // Add semester filter if selected
    if (!empty($selectedSemester)) {
        $sqlQuery .= " AND fd.student_id IN (SELECT student_id FROM student WHERE semester = ?)";
        $types .= "s";
        $params[] = $selectedSemester;
    }
    
    $fileStmt = $conn->prepare($sqlQuery);
    
    if ($fileStmt === false) {
        // Handle prepare error
        echo "Error preparing statement: " . $conn->error;
    } else {
        // Correctly bind parameters by reference
        if (!empty($params)) {
            $bind_params = array();
            $bind_params[] = &$types; // First parameter is always the types string
            
            // Add references to each parameter
            for ($i = 0; $i < count($params); $i++) {
                $bind_params[] = &$params[$i];
            }
            
            // Call bind_param with references
            call_user_func_array([$fileStmt, 'bind_param'], $bind_params);
        }
        
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
                'title' => $row['title'],
                'finaldocu_id' => $row['finaldocu_id']
                
            ];
        }
        
        $fileStmt->close();
    }
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
            $checkStmt = $conn->prepare("SELECT * FROM finaldocufinal_files WHERE finaldocu = ?");
            if ($checkStmt === false) {
                echo "<script>alert('Error preparing check statement: " . $conn->error . "');</script>";
                continue;
            }
            $checkStmt->bind_param("s", $fileName);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $fileExists = $result->num_rows > 0;
            $checkStmt->close();

            if ($fileExists) {
                // Update panel IDs and set date_submitted
                $dateNow = date('Y-m-d H:i:s'); // Get the current date and time
                $updatePanelStmt = $conn->prepare("UPDATE finaldocufinal_files 
                    SET panel1_id = ?, panel2_id = ?, panel3_id = ?, panel4_id = ?, panel5_id = ?, date_submitted = ? 
                    WHERE finaldocu = ?");
                if ($updatePanelStmt === false) {
                    echo "<script>alert('Error preparing update statement: " . $conn->error . "');</script>";
                    continue;
                }
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

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_assignments'])) {
    $filepath = $_POST['filepath'];
    $panel1 = $_POST['panel1'] ?? null;
    $panel2 = $_POST['panel2'] ?? null;
    $panel3 = $_POST['panel3'] ?? null;
    $panel4 = $_POST['panel4'] ?? null;
    $panel5 = $_POST['panel5'] ?? null;
    
    // Reopen the database connection if closed
    if (!isset($conn) || $conn->connect_error) {
        include '../../../connection.php';
    }
    
    // Update panel IDs in the database
    $updateStmt = $conn->prepare("UPDATE finaldocufinal_files 
        SET panel1_id = ?, panel2_id = ?, panel3_id = ?, panel4_id = ?, panel5_id = ?
        WHERE finaldocu = ?");
    $updateStmt->bind_param("iiiiis", $panel1, $panel2, $panel3, $panel4, $panel5, $filepath);
    
    if ($updateStmt->execute()) {
        // Only show alert if not an AJAX request
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
            echo "<script>alert('Assignments updated successfully.');</script>";
        } else {
            // For AJAX requests, just return a success message that will be handled by JavaScript
            if (!headers_sent()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Assignments updated successfully.']);
                exit;
            }
        }
    } else {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
            echo "<script>alert('Failed to update assignments: " . $conn->error . "');</script>";
        } else {
            if (!headers_sent()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update assignments: ' . $conn->error]);
                exit;
            }
        }
    }
    $updateStmt->close();
}

// Fetch advisers for dropdown
function getAdvisers($conn, $department) {
    $advisers = [];
    $stmt = $conn->prepare("SELECT adviser_id, fullname FROM adviser WHERE department = ?");
    if ($stmt === false) {
        error_log("Error preparing advisers statement: " . $conn->error);
        return $advisers;
    }
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $advisers[] = $row;
    }
    
    $stmt->close();
    return $advisers;
}

// If department is selected, fetch advisers
$advisers = [];
if (isset($selectedDepartment)) {
    $advisers = getAdvisers($conn, $selectedDepartment);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Document - Thesis Routing System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <link rel="stylesheet" href="finalstyle.css">
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
        .action-label {
    text-align: center;
}
        #external-submit-button:hover {
            background-color: #45a049;
        }
        .assignment-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
        text-decoration: none;
    }

    .assignment-modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        border-radius: 5px;
        width: 50%;
        max-width: 500px;
        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
        text-decoration: none;
    }

    .assignment-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        text-decoration: none;
    }

    .assignment-modal-close:hover,
    .assignment-modal-close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
        text-decoration: none;
    }

    .assignment-list {
        margin-top: 20px;
        text-decoration: none;
    }

    .not-assigned {
        color: #888;
        font-style: italic;
        text-decoration: none;
    }

    .assignment-list h3 {
        margin-bottom: 5px;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
        text-decoration: none;
    }

    .assignment-list ul {
        padding-left: 20px;
        margin-top: 5px;
        text-decoration: none;
    }

    .edit-button {
    background-color: #FF9800;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 15px;
    font-size: 14px;
}

.edit-button:hover {
    background-color: #F57C00;
}

.save-button {
    background-color: #4CAF50;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    margin-right: 10px;
}

.save-button:hover {
    background-color: #45a049;
}

.cancel-button {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.cancel-button:hover {
    background-color: #d32f2f;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-actions {
    margin-top: 20px;
    display: flex;
}

#view-assignments-section {
    margin-bottom: 20px;
}

/* Style to fix the panel buttons */
.assignment-button {
    white-space: nowrap;
}

.form-input-row textarea {
    resize: vertical;
    min-height: 40px;
}

/* CSS Animation for spinner */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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
                    <form method="POST" style="display: inline; margin-left: 10px;">
                        <select name="semester" onchange="this.form.submit()">
                            <option value="">All Semesters</option>
                            <?php foreach ($semesters as $semester): ?>
                                <option value="<?= htmlspecialchars($semester) ?>"
                                    <?= isset($selectedSemester) && $selectedSemester == $semester ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($semester) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            <div class="user-info">
                <div class="routeNo" style="margin-right: 20px;">Final - Final Document</div>
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
                            <span>Accounts</span>
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


                <!-- Search and Submit Button -->
                <div class="search-container">
                    <input type="text" id="searchInput" class="search-box" placeholder="Search by leader name..." onkeyup="searchTable()">
                </div>

                <form id="submission-form" action="finaldocu.php" method="POST">
                    <table>
                        <thead>
                            <tr>
                                <th>Control No.</th>
                                <th>Leader</th>
                                <th>Group No.</th>
                                <th>Title</th>
                                <th>Assigned</th>
                                <th>Status</th>
                                <th class='action-label'>Action</th>
                            </tr>
                        </thead>
                        <!-- Replace your existing table rows with this updated version -->
    <tbody id="file-list">
        <?php if (!empty($files)): ?>
            <?php foreach ($files as $file): ?>
                <?php
                    $filepath = htmlspecialchars($file['filepath'], ENT_QUOTES);
                    $filename = htmlspecialchars(basename($file['filename']), ENT_QUOTES);
                    $controlNo = htmlspecialchars($file['controlNo'] ?? '', ENT_QUOTES);
                    $group_number = htmlspecialchars($file['group_number'] ?? '', ENT_QUOTES);
                    $fullname = htmlspecialchars($file['fullname'] ?? '', ENT_QUOTES);
                    $student_id = htmlspecialchars($file['student_id'] ?? '', ENT_QUOTES);
                    $title = htmlspecialchars($file['title'] ?? '', ENT_QUOTES);
                    
                    // Check the approval status of all routes
                    // $routeStatus = checkAllRoutesApproved($conn, $student_id);
                    
                    // Determine status label and color
                    $statusLabel = '';
                    $statusColor = '';
                    
                    // Check if any routing monitoring form for this finaldocu is approved
                    $finaldocu_id = $file['finaldocu_id'] ?? 0; // Ensure we have a valid ID and not null
                    
                    $approvedFormQuery = $conn->prepare("SELECT COUNT(*) as count FROM final_monitoring_form 
                                                       WHERE finaldocu_id = ? 
                                                       AND (status = 'Approved' OR status = 'approved')");
                    $approvedFormQuery->bind_param("i", $finaldocu_id);
                    $approvedFormQuery->execute();
                    $approvedFormResult = $approvedFormQuery->get_result();
                    $countResult = $approvedFormResult->fetch_assoc();
                    $hasApprovedForm = ($countResult && $countResult['count'] > 0);
                    $approvedFormQuery->close();
                    
                    // Determine status label and color - simple Complete/Incomplete
                    $statusLabel = $hasApprovedForm ? 'Complete' : 'Incomplete';
                    $statusColor = $hasApprovedForm ? 'green' : 'red';
                    
                    // Panel and adviser information
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
                    $adviser_id = !empty($file['adviser_id']) ? $file['adviser_id'] : "";
                    if (!empty($adviser_id)) {
                        $adviser_name = getAdviserName($conn, $adviser_id);
                    }
                    
                    // Create assignment status text
                    $has_panels = !empty($assigned_panels);
                    $has_adviser = !empty($adviser_name);
                    if ($has_panels && $has_adviser) {
                        $assignment_status = "View Assignments";
                    } elseif ($has_panels) {
                        $assignment_status = "View Panelists";
                    } elseif ($has_adviser) {
                        $assignment_status = "View Adviser";
                    } else {
                        $assignment_status = "Not Assigned";
                    }

                    // Prepare file data for JS
                    $fileData = [
                        'filepath' => $filepath,
                        'panel1_id' => $file['panel1_id'] ?? '',
                        'panel2_id' => $file['panel2_id'] ?? '',
                        'panel3_id' => $file['panel3_id'] ?? '',
                        'panel4_id' => $file['panel4_id'] ?? '',
                        'panel5_id' => $file['panel5_id'] ?? '',
                        'adviser_id' => $adviser_id
                    ];
                    
                    // Encode assignment data for the modal
                    $assignmentData = [
                        'panels' => $assigned_panels,
                        'adviser' => $adviser_name
                    ];
                ?>
                <tr>
                    <td><?= $controlNo ?></td>
                    <td><?= $fullname ?></td>
                    <td><?= $group_number ?></td>
                    <td><?= $title ?></td>
                    <td>
                        <button type="button" class="assignment-button <?= ($has_panels || $has_adviser) ? '' : 'not-assigned' ?>" 
                                onclick="showAssignmentDetails(
                                    <?= htmlspecialchars(json_encode($assignmentData), ENT_QUOTES) ?>, 
                                    <?= htmlspecialchars(json_encode($fileData), ENT_QUOTES) ?>
                                )">
                            <?= $assignment_status ?>
                        </button>
                    </td>
                    <td>
                        <span style="color: <?= $statusColor ?>; font-weight: bold;">
                            <?= $statusLabel ?>
                        </span>
                    </td>
                    <td>
                        <button type="button" class="view-button" onclick="viewFile('<?= $filepath ?>', '<?= $student_id ?>', '<?= $file['finaldocu_id'] ?? '' ?>')">View</button>
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
                    <input type="hidden" name="panel5" id="hidden-panel5">
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

    <div id="assignmentModal" class="assignment-modal">
    <div class="assignment-modal-content">
        <span class="assignment-modal-close" onclick="closeAssignmentModal()">&times;</span>
        <h2>Assignment Details</h2>
        
        <div id="view-assignments-section">
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
            <button id="edit-assignments-btn" class="edit-button" onclick="showEditAssignments()">Edit Assignments</button>
        </div>
        
        <div id="edit-assignments-section" style="display: none;">
            <form id="update-assignments-form" method="POST">
                <input type="hidden" id="edit-filepath" name="filepath">
                <input type="hidden" name="update_assignments" value="1">
                
                <div class="form-group">
                    <label for="edit-panel1">Panel 1:</label>
                    <select id="edit-panel1" name="panel1" class="form-control">
                        <option value="">None</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel WHERE position = 'panel1'");
                            $panelStmt->execute();
                            $panelResult = $panelStmt->get_result();
                            while ($row = $panelResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                    <?= htmlspecialchars($row['fullname']) ?> <?= ($row['department'] != $selectedDepartment) ? '(' . htmlspecialchars($row['department']) . ')' : '' ?>
                                </option>
                            <?php endwhile;
                            $panelStmt->close();
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit-panel2">Panel 2:</label>
                    <select id="edit-panel2" name="panel2" class="form-control">
                        <option value="">None</option>
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
                </div>
                
                <div class="form-group">
                    <label for="edit-panel3">Panel 3:</label>
                    <select id="edit-panel3" name="panel3" class="form-control">
                        <option value="">None</option>
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
                </div>
                
                <div class="form-group">
                    <label for="edit-panel4">Panel 4:</label>
                    <select id="edit-panel4" name="panel4" class="form-control">
                        <option value="">None</option>
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
                
                <div class="form-group">
                    <label for="edit-panel5">Panel 5:</label>
                    <select id="edit-panel5" name="panel5" class="form-control">
                        <option value="">None</option>
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
                </div>
                
                <div class="form-group">
                    <label for="edit-adviser">Adviser:</label>
                    <select id="edit-adviser" name="adviser_id" class="form-control" disabled>
                        <option value="">None</option>
                        <?php foreach ($advisers as $adv): ?>
                            <option value="<?= htmlspecialchars($adv['adviser_id']) ?>">
                                <?= htmlspecialchars($adv['fullname']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="save-button">Save Changes</button>
                    <button type="button" class="cancel-button" onclick="cancelEdit()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
</html>
<script>

    // Search function for the table
    function searchTable() {
        const input = document.getElementById("searchInput");
        const filter = input.value.toUpperCase();
        const table = document.getElementById("file-list");
        const rows = table.getElementsByTagName("tr");
        
        for (let i = 0; i < rows.length; i++) {
            const leaderCell = rows[i].getElementsByTagName("td")[2]; // Index 2 is the Leader column
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

    // Global variable to store current file data for edit mode
    let currentEditFile = null;

    function showAssignmentDetails(assignments, fileData = null) {
        const modal = document.getElementById('assignmentModal');
        const panelistsList = document.getElementById('panelists-list');
        const adviserName = document.getElementById('adviser-name');
        
        // Store file data for edit mode
        currentEditFile = fileData;
        
        // Clear previous content
        panelistsList.innerHTML = '';
        adviserName.innerHTML = '';
        adviserName.className = ''; // Reset any classes
        
        // Reset view to show assignments
        document.getElementById('view-assignments-section').style.display = 'block';
        document.getElementById('edit-assignments-section').style.display = 'none';
        
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
            adviserName.className = ''; // Remove any previous class
        } else {
            adviserName.textContent = 'No adviser assigned';
            adviserName.className = 'not-assigned';
        }
        
        // Show modal
        modal.style.display = 'block';
    }

    function showEditAssignments() {
        if (!currentEditFile) {
            alert("File data not available. Please try again.");
            return;
        }
        
        // Switch to edit view
        document.getElementById('view-assignments-section').style.display = 'none';
        document.getElementById('edit-assignments-section').style.display = 'block';
        
        // Set file path for the form
        document.getElementById('edit-filepath').value = currentEditFile.filepath;
        
        // Set current values in dropdowns
        if (currentEditFile.panel1_id) {
            document.getElementById('edit-panel1').value = currentEditFile.panel1_id;
        }
        if (currentEditFile.panel2_id) {
            document.getElementById('edit-panel2').value = currentEditFile.panel2_id;
        }
        if (currentEditFile.panel3_id) {
            document.getElementById('edit-panel3').value = currentEditFile.panel3_id;
        }
        if (currentEditFile.panel4_id) {
            document.getElementById('edit-panel4').value = currentEditFile.panel4_id;
        }
        if (currentEditFile.panel5_id) {
            document.getElementById('edit-panel5').value = currentEditFile.panel5_id;
        }
        if (currentEditFile.adviser_id) {
            document.getElementById('edit-adviser').value = currentEditFile.adviser_id;
        }
    }

    function cancelEdit() {
        // Switch back to view mode
        document.getElementById('view-assignments-section').style.display = 'block';
        document.getElementById('edit-assignments-section').style.display = 'none';
    }

    function closeAssignmentModal() {
        document.getElementById('assignmentModal').style.display = 'none';
        currentEditFile = null; // Clear current file data
    }

    // Modify the existing showAssignmentDetails call in the HTML table generation
    // You need to pass the full file data as the second parameter
    // Update your function call in the PHP section to include file data:
    /* Replace the existing onclick function with:
    onclick="showAssignmentDetails(
        <?= htmlspecialchars(json_encode([
            'panels' => $assigned_panels,
            'adviser' => $adviser_name
        ]), ENT_QUOTES) ?>, 
        <?= htmlspecialchars(json_encode([
            'filepath' => $filepath,
            'panel1_id' => $file['panel1_id'],
            'panel2_id' => $file['panel2_id'],
            'panel3_id' => $file['panel3_id'],
            'panel4_id' => $file['panel4_id'],
            'panel5_id' => $file['panel5_id'],
            'adviser_id' => $file['adviser_id']
        ]), ENT_QUOTES) ?>
    )"
    */
    function viewFile(filePath, student_id, finaldocu) {
        const modal = document.getElementById("fileModal");
        const contentArea = document.getElementById("fileModalContent");
        const routingFormArea = document.getElementById("routingForm");

        modal.style.display = "flex";
        contentArea.innerHTML = "Loading file...";
        routingFormArea.innerHTML = `
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
        <img src="../../../assets/logo.png" style="width: 40px; max-width: 100px;">
        <img src="../../../assets/smcc-reslogo.png" style="width: 50px; max-width: 100px;">
        <div style="text-align: center;">
            <h4 style="margin: 0;">SAINT MICHAEL COLLEGE OF CARAGA</h4>
            <h4 style="margin: 0;">RESEARCH & INSTRUCTIONAL INNOVATION DEPARTMENT</h4>
        </div>
        <img src="../../../assets/socotec.png" style="width: 60px; max-width: 100px;">
    </div>
    <button id="printButton" style="background-color: var(--primary); color: white; border: none; border-radius: 4px; padding: 0.5rem 1rem; cursor: pointer; display: flex; align-items: center; gap: 5px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
            <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
        </svg>
        Print Form
    </button>
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
<div><strong>Route Number</strong></div>
</div>

<!-- Container for submitted form data -->
<div id="submittedFormsContainer" class="form-grid-container"></div>
<div id="noFormsMessage" style="margin-top: 10px; color: gray;"></div>
`;

        // Load form data dynamically
        fetch(`route3get_all_forms.php?student_id=${encodeURIComponent(student_id)}`)
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
                        <div>${row.routeNumber}</div>
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
        
        // New event listener for print button
        setTimeout(() => {
            document.getElementById('printButton').addEventListener('click', function() {
                // Create a hidden iframe element
                const iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
                
                // Get content to print
                const headerContent = document.querySelector('.routing-form-section > div:first-child').cloneNode(true);
                const titleContent = document.querySelector('.routing-form-section > div:nth-child(3)').cloneNode(true);
                const tableHeaders = document.querySelector('.form-grid-container').cloneNode(true);
                const tableData = document.getElementById('submittedFormsContainer').cloneNode(true);
                
                // Remove print button from cloned header
                headerContent.querySelector('#printButton').remove();
                
                // Create print HTML
                const printContent = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Routing Monitoring Form</title>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .header { display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 1rem; }
                            .title { text-align: center; margin: 1.5rem 0; }
                            .grid-container {
                                display: grid;
                                grid-template-columns: repeat(9, 1fr);
                                border: 1px solid #e0e0e0;
                                border-radius: 6px;
                                overflow: hidden;
                                margin-bottom: 1rem;
                            }
                            .grid-container > div {
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                padding: 0.5rem;
                                font-size: 0.9rem;
                                border: 1px solid #e0e0e0;
                                background-color: white;
                                text-align: center;
                            }
                            @media print {
                                @page { size: landscape; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">${headerContent.innerHTML}</div>
                        <hr style="border: 1px solid black; margin: 0.2rem 0;">
                        <div class="title">${titleContent.innerHTML}</div>
                        <div class="grid-container">${tableHeaders.innerHTML}</div>
                        <div class="grid-container">${tableData.innerHTML}</div>
                    </body>
                    </html>
                `;
                
                // Write content to iframe and print
                iframe.contentWindow.document.open();
                iframe.contentWindow.document.write(printContent);
                iframe.contentWindow.document.close();
                
                // Add onload event to ensure content is fully loaded before printing
                iframe.onload = function() {
                    setTimeout(function() {
                        iframe.contentWindow.print();
                        // Clean up after printing
                        setTimeout(function() {
                            document.body.removeChild(iframe);
                        }, 500);
                    }, 300);
                };
            });
        }, 100);
    }

    function closeModal() {
        const modal = document.getElementById("fileModal");
        modal.style.display = "none";
        document.getElementById("fileModalContent").innerHTML = '';
        document.getElementById("routingForm").innerHTML = '';
    }

    // Modify the update form to use AJAX for submission
    document.addEventListener('DOMContentLoaded', function() {
        // Check if external-submit-button doesn't exist, and handle that case
        const submitButton = document.getElementById('external-submit-button');
        if (!submitButton) {
            console.log("Submit button not found. Panel assignment feature will be limited.");
            // Hide elements that require the panel dropdowns if they don't exist
            const panelContainer = document.querySelector('.panel-container');
            if (panelContainer) {
                panelContainer.style.display = 'none';
            }
        }
        
        // Update form for assignments
        const updateForm = document.getElementById('update-assignments-form');
        if (updateForm) {
            updateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Create form data object
                const formData = new FormData(this);
                
                // Remove adviser_id from the form data since it's disabled
                formData.delete('adviser_id');
                
                // Send AJAX request
                fetch('finaldocu.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Unknown error occurred');
                    }
                    
                    // Show success message
                    alert(data.message);
                    
                    // Get updated panel information
                    const panel1Id = document.getElementById('edit-panel1').value;
                    const panel2Id = document.getElementById('edit-panel2').value;
                    const panel3Id = document.getElementById('edit-panel3').value;
                    const panel4Id = document.getElementById('edit-panel4').value;
                    const panel5Id = document.getElementById('edit-panel5').value;
                    
                    // Keep the existing adviser_id from currentEditFile
                    const adviserId = currentEditFile.adviser_id;
                    
                    // Update currentEditFile with new panel values only
                    currentEditFile.panel1_id = panel1Id;
                    currentEditFile.panel2_id = panel2Id;
                    currentEditFile.panel3_id = panel3Id;
                    currentEditFile.panel4_id = panel4Id;
                    currentEditFile.panel5_id = panel5Id;
                    // Note: adviser_id is not changed
                    
                    // Get selected panel and adviser names
                    const panels = [];
                    if (panel1Id) {
                        const panel1Select = document.getElementById('edit-panel1');
                        const panel1Name = panel1Select.options[panel1Select.selectedIndex].text;
                        panels.push({position: 'Panel 1', name: panel1Name});
                    }
                    if (panel2Id) {
                        const panel2Select = document.getElementById('edit-panel2');
                        const panel2Name = panel2Select.options[panel2Select.selectedIndex].text;
                        panels.push({position: 'Panel 2', name: panel2Name});
                    }
                    if (panel3Id) {
                        const panel3Select = document.getElementById('edit-panel3');
                        const panel3Name = panel3Select.options[panel3Select.selectedIndex].text;
                        panels.push({position: 'Panel 3', name: panel3Name});
                    }
                    if (panel4Id) {
                        const panel4Select = document.getElementById('edit-panel4');
                        const panel4Name = panel4Select.options[panel4Select.selectedIndex].text;
                        panels.push({position: 'Panel 4', name: panel4Name});
                    }
                    if (panel5Id) {
                        const panel5Select = document.getElementById('edit-panel5');
                        const panel5Name = panel5Select.options[panel5Select.selectedIndex].text;
                        panels.push({position: 'Panel 5', name: panel5Name});
                    }
                    
                    // Get adviser name from existing select element (even though it's disabled)
                    let adviserName = '';
                    if (adviserId) {
                        const adviserSelect = document.getElementById('edit-adviser');
                        const selectedOption = Array.from(adviserSelect.options).find(option => option.value === adviserId);
                        adviserName = selectedOption ? selectedOption.text : '';
                    }
                    
                    // Update assigned data
                    const updatedAssignmentData = {
                        panels: panels,
                        adviser: adviserName
                    };
                    
                    // Show updated assignments in the modal
                    const panelistsList = document.getElementById('panelists-list');
                    const adviserNameElement = document.getElementById('adviser-name');
                    
                    // Clear previous content
                    panelistsList.innerHTML = '';
                    adviserNameElement.innerHTML = '';
                    adviserNameElement.className = ''; // Reset any classes
                    
                    // Add panelists
                    if (panels.length > 0) {
                        panels.forEach(panel => {
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
                    if (adviserName && adviserName.trim() !== '') {
                        adviserNameElement.textContent = adviserName;
                        adviserNameElement.className = ''; // Remove any previous class
                    } else {
                        adviserNameElement.textContent = 'No adviser assigned';
                        adviserNameElement.className = 'not-assigned';
                    }
                    
                    // Update the assignment status button in the table
                    const filepath = document.getElementById('edit-filepath').value;
                    const assignmentButtons = document.querySelectorAll('.assignment-button');
                    
                    assignmentButtons.forEach(button => {
                        // Find the button for this file
                        const row = button.closest('tr');
                        const checkbox = row.querySelector('input[type="checkbox"]');
                        if (checkbox && checkbox.value === filepath) {
                            let newStatus = 'Not Assigned';
                            
                            if (panels.length > 0 && adviserId) {
                                newStatus = 'View Assignments';
                                button.classList.remove('not-assigned');
                            } else if (panels.length > 0) {
                                newStatus = 'View Panelists';
                                button.classList.remove('not-assigned');
                            } else if (adviserId) {
                                newStatus = 'View Adviser';
                                button.classList.remove('not-assigned');
                            } else {
                                button.classList.add('not-assigned');
                            }
                            
                            button.textContent = newStatus;
                            
                            // Update onclick function with new data
                            button.onclick = function() {
                                showAssignmentDetails(updatedAssignmentData, currentEditFile);
                            };
                        }
                    });
                    
                    // Switch back to view mode
                    document.getElementById('view-assignments-section').style.display = 'block';
                    document.getElementById('edit-assignments-section').style.display = 'none';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to update assignments: ' + error.message);
                });
            });
        }
    });
</script>
<script src="../sidebar.js"></script>
