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

// Import PHPMailer classes at the top of the file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Fetch departments
$departments = [];
$deptQuery = "SELECT DISTINCT department FROM student";
$deptResult = $conn->query($deptQuery);
if ($deptResult->num_rows > 0) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row['department'];
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

// Fetch school years
$schoolYears = [];
$schoolYearQuery = "SELECT DISTINCT school_year FROM route1proposal_files ORDER BY school_year DESC";
$schoolYearResult = $conn->query($schoolYearQuery);
if ($schoolYearResult && $schoolYearResult->num_rows > 0) {
    while ($row = $schoolYearResult->fetch_assoc()) {
        $schoolYears[] = $row['school_year'];
    }
}

// Helper functions to get names from IDs
function getPanelName($conn, $panel_id) {
    $stmt = $conn->prepare("SELECT fullname, email FROM panel WHERE panel_id = ?");
    $stmt->bind_param("i", $panel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

function getAdviserName($conn, $adviser_id) {
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

// Function to validate email address
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Custom error logging function for email issues
function logEmailError($message) {
    $logFile = __DIR__ . '/../../../email_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    // Also log to PHP error log
    error_log($message);
    
    // Write to custom log file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Function to send email notification to panel
function sendPanelNotificationEmail($panelEmail, $panelName, $position, $studentName, $title) {
    try {
        // Validate email address first
        if (!isValidEmail($panelEmail)) {
            logEmailError("Invalid email address format: $panelEmail");
            return false;
        }
        
        // Check for Composer autoloader
        $autoloader_path = __DIR__ . '/../../../vendor/autoload.php';
        
        if (!file_exists($autoloader_path)) {
            logEmailError("PHPMailer autoloader not found at: $autoloader_path. Please install PHPMailer via Composer.");
            return false;
        }
        
        // Include the autoloader
        require_once $autoloader_path;
        
        // Create instance of PHPMailer
        $mail = new PHPMailer(true);

        // Server settings
        $mail->SMTPDebug  = 2;  // Enable verbose debug output (0 for no output, 2 for verbose)
        $mail->Debugoutput = function($str, $level) { logEmailError("PHPMailer [$level]: $str"); };
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lokolomi14@gmail.com'; // Your Gmail
        $mail->Password   = 'appf rexr omgy ngjw';   // App password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8'; // Ensure proper character encoding
        
        // Recommended Gmail-specific settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Set Timeout values
        $mail->Timeout    = 60; // Increased HTTP timeout in seconds
        $mail->SMTPKeepAlive = true; // SMTP keep alive

        // Sender and recipient settings
        $mail->setFrom('lokolomi14@gmail.com', 'Thesis Routing System', false);
        $mail->addReplyTo('lokolomi14@gmail.com', 'Thesis Routing System');
        $mail->addAddress($panelEmail, $panelName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Panel Assignment Notification - Thesis Routing System";
        
        // Get server URL dynamically
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        $server_port = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '';
        $http_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $base_url = $http_protocol . '://' . $server_name . $server_port;
        
        $login_url = $base_url . '/TRS/panel/';
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                <h2 style='color: #4366b3; text-align: center;'>Thesis Routing System Notification</h2>
                <p>Dear <strong>{$panelName}</strong>,</p>
                <p>You have been assigned as <strong>{$position}</strong> for the following thesis:</p>
                <p><strong>Student:</strong> {$studentName}</p>
                <p><strong>Title:</strong> {$title}</p>
                <p>Please log in to the Thesis Routing System to review the proposal.</p>
                <div style='margin-top: 30px; text-align: center;'>
                    <a href='{$login_url}' style='background-color: #4366b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login to Review</a>
                </div>
                <p style='margin-top: 10px; text-align: center;'>If the button above doesn't work, copy and paste this URL into your browser: <br><a href='{$login_url}'>{$login_url}</a></p>
                <p style='margin-top: 30px; font-size: 12px; color: #777; text-align: center;'>This is an automated message from the Thesis Routing System. Please do not reply to this email.</p>
            </div>
        ";
        $mail->AltBody = "Dear {$panelName}, You have been assigned as {$position} for the thesis '{$title}' by {$studentName}. Please login at: {$login_url} to review the proposal.";

        // Add additional headers that may help with deliverability
        $mail->addCustomHeader('X-Mailer', 'Thesis Routing System');
        $mail->addCustomHeader('X-Priority', '3');

        $mail->send();
        error_log("Email sent successfully to panel: $panelEmail using PHPMailer");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Email could not be sent to panel: $panelEmail. ";
        
        if (isset($mail)) {
            $errorMsg .= "PHPMailer Error: " . $mail->ErrorInfo;
            
            // Log SMTP debug info for connection issues
            if (strpos($mail->ErrorInfo, 'SMTP connect() failed') !== false) {
                $errorMsg .= ". Possible connection issue with SMTP server.";
            } else if (strpos($mail->ErrorInfo, 'authentication failed') !== false) {
                $errorMsg .= ". Authentication issue - check username and password.";
            } else if (strpos($mail->ErrorInfo, 'Invalid address') !== false) {
                $errorMsg .= ". Invalid email address format.";
            } else if (strpos($mail->ErrorInfo, 'Could not authenticate') !== false) {
                $errorMsg .= ". Gmail may be blocking this attempt. Check Gmail settings and app password.";
            } else if (strpos($mail->ErrorInfo, 'Recipient') !== false) {
                $errorMsg .= ". There's an issue with the recipient address. Check if the address is valid.";
            }
        } else {
            $errorMsg .= "Exception: " . $e->getMessage();
        }
        
        logEmailError($errorMsg);
        return false;
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

    // Create proper query with placeholders
    $sqlQuery = "SELECT 
            docuRoute1 AS filepath, 
            docuRoute1 AS filename, 
            date_submitted,
            controlNo,
            group_number,
            fullname,
            title,
            student_id, panel1_id, panel2_id, panel3_id, panel4_id, panel5_id, adviser_id,
            minutes,
            route1_id,
            department
         FROM route1proposal_files";
    
    // Create array of parameters (must be variables, not direct values)
    $types = "";
    $params = [];
    
    // Only filter by department if one is selected
    if (!empty($selectedDepartment)) {
        $sqlQuery .= " WHERE department = ?";
        $types .= "s";
        $params[] = $selectedDepartment;
        
        // Add school year filter if selected
        if (!empty($selectedSchoolYear)) {
            $sqlQuery .= " AND school_year = ?";
            $types .= "s";
            $params[] = $selectedSchoolYear;
        }
        
        // Add semester filter if selected
        if (!empty($selectedSemester)) {
            $sqlQuery .= " AND student_id IN (SELECT student_id FROM student WHERE semester = ?)";
            $types .= "s";
            $params[] = $selectedSemester;
        }
    } else {
        // If no department selected, just add other filters
        $whereAdded = false;
        
        // Add school year filter if selected
        if (!empty($selectedSchoolYear)) {
            $sqlQuery .= " WHERE school_year = ?";
            $whereAdded = true;
            $types .= "s";
            $params[] = $selectedSchoolYear;
        }
        
        // Add semester filter if selected
        if (!empty($selectedSemester)) {
            if ($whereAdded) {
                $sqlQuery .= " AND student_id IN (SELECT student_id FROM student WHERE semester = ?)";
            } else {
                $sqlQuery .= " WHERE student_id IN (SELECT student_id FROM student WHERE semester = ?)";
                $whereAdded = true;
            }
            $types .= "s";
            $params[] = $selectedSemester;
        }
    }
    
    $fileStmt = $conn->prepare($sqlQuery);
    
    // Correctly bind parameters by reference if there are any
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
            'minutes' => $row['minutes'],
            'route1_id' => $row['route1_id'],
            'department' => $row['department']
        ];
    }
    
    $fileStmt->close();
} else {
    // No department selected, fetch all files
    $sqlQuery = "SELECT 
            docuRoute1 AS filepath, 
            docuRoute1 AS filename, 
            date_submitted,
            controlNo,
            group_number,
            fullname,
            title,
            student_id, panel1_id, panel2_id, panel3_id, panel4_id, panel5_id, adviser_id,
            minutes,
            route1_id,
            department
         FROM route1proposal_files";
    
    $fileStmt = $conn->prepare($sqlQuery);
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
            'minutes' => $row['minutes'],
            'route1_id' => $row['route1_id'],
            'department' => $row['department']
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
    } elseif (empty($panel1) || empty($panel2) || empty($panel3) || empty($panel4) || empty($panel5)) {
        echo "<script>alert('All 5 panel positions must be filled. Please select a panel member for each position.');</script>";
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

                // Get student information for email notifications
                $studentInfoStmt = $conn->prepare("SELECT fullname, title FROM route1proposal_files WHERE docuRoute1 = ?");
                $studentInfoStmt->bind_param("s", $fileName);
                $studentInfoStmt->execute();
                $studentInfo = $studentInfoStmt->get_result()->fetch_assoc();
                $studentName = $studentInfo['fullname'] ?? 'Unknown Student';
                $thesisTitle = $studentInfo['title'] ?? 'Unknown Title';
                $studentInfoStmt->close();

                // Send emails to newly assigned panels
                $panelPositions = [
                    1 => $panel1,
                    2 => $panel2,
                    3 => $panel3,
                    4 => $panel4,
                    5 => $panel5
                ];

                foreach ($panelPositions as $position => $panelId) {
                    if ($panelId) {
                        $panelInfo = getPanelName($conn, $panelId);
                        if ($panelInfo && !empty($panelInfo['email'])) {
                            sendPanelNotificationEmail(
                                $panelInfo['email'],
                                $panelInfo['fullname'],
                                "Panel {$position}",
                                $studentName,
                                $thesisTitle
                            );
                        }
                    }
                }

                echo "<script>alert('Successfully Submitted. Email notifications sent to panel members.');</script>";
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
    $adviser_id = $_POST['adviser_id'] ?? null;

    // Validate all panel positions are filled
    if (empty($panel1) || empty($panel2) || empty($panel3) || empty($panel4) || empty($panel5)) {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
            echo "<script>alert('All 5 panel positions must be filled. Please select a panel member for each position.');</script>";
        } else {
            if (!headers_sent()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'All 5 panel positions must be filled. Please select a panel member for each position.']);
                exit;
            }
        }
        return;
    }

    // Get student information
    $studentInfoStmt = $conn->prepare("SELECT fullname, title FROM route1proposal_files WHERE docuRoute1 = ?");
    $studentInfoStmt->bind_param("s", $filepath);
    $studentInfoStmt->execute();
    $studentInfo = $studentInfoStmt->get_result()->fetch_assoc();
    $studentName = $studentInfo['fullname'] ?? 'Unknown Student';
    $thesisTitle = $studentInfo['title'] ?? 'Unknown Title';
    $studentInfoStmt->close();

    // Update panel IDs in the database
    $updateStmt = $conn->prepare("UPDATE route1proposal_files 
        SET panel1_id = ?, panel2_id = ?, panel3_id = ?, panel4_id = ?, panel5_id = ?
        WHERE docuRoute1 = ?");
    $updateStmt->bind_param("iiiiis", $panel1, $panel2, $panel3, $panel4, $panel5, $filepath);
    
    if ($updateStmt->execute()) {
        // Send emails to newly assigned panels
        $panelPositions = [
            1 => $panel1,
            2 => $panel2,
            3 => $panel3,
            4 => $panel4,
            5 => $panel5
        ];

        foreach ($panelPositions as $position => $panelId) {
            if ($panelId) {
                $panelInfo = getPanelName($conn, $panelId);
                if ($panelInfo && !empty($panelInfo['email'])) {
                    sendPanelNotificationEmail(
                        $panelInfo['email'],
                        $panelInfo['fullname'],
                        "Panel {$position}",
                        $studentName,
                        $thesisTitle
                    );
                }
            }
        }

        // Only show alert if not an AJAX request
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
            echo "<script>alert('Assignments updated successfully and notifications sent.');</script>";
        } else {
            // For AJAX requests, just return a success message that will be handled by JavaScript
            if (!headers_sent()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Assignments updated successfully and notifications sent.']);
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
    <title>Route 1 - Thesis Routing System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <link rel="stylesheet" href="proposalstyle.css">
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
    
    /* Styles for disabled rows and checkboxes */
    .already-assigned {
        background-color: #f5f5f5;
        color: #777;
    }
    
    .already-assigned:hover {
        cursor: default;
    }
    
    .selectable-row:hover {
        background-color: #f0f8ff;
        cursor: pointer;
    }
    
    input[type="checkbox"]:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
    
    /* Specific style for the feedback cell (3rd column) - supports different grid sizes */
    .form-grid-container > div:nth-child(10n + 3),
    .form-grid-container > div:nth-child(9n + 3),
    .form-grid-container > div:nth-child(8n + 3) {
        text-align: left;
        justify-content: flex-start;
        overflow-y: visible;
        max-height: none;
        height: auto;
        white-space: pre-wrap;
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
        min-height: 40px;
    }

    /* CSS Animation for spinner */
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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
            position: sticky;
            top: 52px; /* Updated to account for the top bar height */
            height: calc(100vh - 52px); /* Adjusted to account for the top bar */
            overflow-y: auto;
            z-index: 90; /* Add z-index to ensure proper stacking */
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
        <div class="top-bar" style="position: sticky; top: 0; z-index: 100;">
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
                <!-- Panel Selection and Submit Button Area -->
<div class="panel-container">
    <select id="panel1-dropdown">
        <option value="">Panel 1</option>
        <?php
        if (isset($selectedDepartment)) {
            // Modified query to include all Panel 1 members regardless of department
            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
            // Modified query to include all Panel 2 members regardless of department
            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
    
    <select id="panel3-dropdown">
        <option value="">Panel 3</option>
        <?php
        if (isset($selectedDepartment)) {
            // Modified query to include all Panel 3 members regardless of department
            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
    
    <select id="panel4-dropdown">
        <option value="">Panel 4</option>
        <?php
        if (isset($selectedDepartment)) {
            // Modified query to include all Panel 4 members regardless of department
            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
    
    <select id="panel5-dropdown">
        <option value="">Panel 5</option>
        <?php
        if (isset($selectedDepartment)) {
            // Modified query to include all Panel 5 members regardless of department
            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
                <th>Minutes</th>
                <th>Assigned</th>
                
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
                $fullname = htmlspecialchars($file['fullname'] ?? '', ENT_QUOTES);
                $group_number = htmlspecialchars($file['group_number'] ?? '', ENT_QUOTES);
                $student_id = htmlspecialchars($file['student_id'] ?? '', ENT_QUOTES);
                $title = htmlspecialchars($file['title'] ?? '', ENT_QUOTES);
                
                // Panel and adviser information
                $assigned_panels = [];
                for ($i = 1; $i <= 5; $i++) {
                    $panel_id_key = "panel{$i}_id";
                    if (!empty($file[$panel_id_key])) {
                        $panel_info = getPanelName($conn, $file[$panel_id_key]);
                        if ($panel_info && is_array($panel_info)) {
                            $panel_name = $panel_info['fullname'] ?? 'Unknown';
                            if (!empty($panel_name)) {
                                $assigned_panels[] = ["name" => $panel_name, "position" => "Panel $i"];
                            }
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
                
                // Check if file is already assigned to determine if checkbox should be disabled
                // Only disable if a panel is assigned (don't consider adviser)
                $is_assigned = $has_panels;

                // Prepare file data for JS
                $fileData = [
                    'filepath' => $filepath,
                    'panel1_id' => $file['panel1_id'] ?? '',
                    'panel2_id' => $file['panel2_id'] ?? '',
                    'panel3_id' => $file['panel3_id'] ?? '',
                    'panel4_id' => $file['panel4_id'] ?? '',
                    'panel5_id' => $file['panel5_id'] ?? '',
                    'adviser_id' => $adviser_id,
                    'is_assigned' => $is_assigned,
                    'has_panels' => $has_panels,
                    'has_adviser' => $has_adviser
                ];
                
                // Encode assignment data for the modal
                $assignmentData = [
                    'panels' => $assigned_panels,
                    'adviser' => $adviser_name
                ];
            ?>
            <tr class="<?= $is_assigned ? 'already-assigned' : 'selectable-row' ?>">
                <td style="text-align: center;">
                    <input type="checkbox" name="selected_files[]" value="<?= $filepath ?>" <?= $is_assigned ? 'disabled' : '' ?> data-is-assigned="<?= $is_assigned ? 'true' : 'false' ?>">
                </td>
                <td><?= $controlNo ?></td>
                <td><?= $fullname ?></td>
                <td><?= $group_number ?></td>
                <td><?= $title ?></td>
                <td>
                    <?php 
                    $minutesStatus = $file['minutes'] ? '<span style="color: green;">Available</span>' : '<span style="color: red;">Not Available</span>';
                    echo $minutesStatus;
                    ?>
                </td>
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
                    <button type="button" class="view-button" onclick="viewFile('<?= $filepath ?>', '<?= $student_id ?>', '<?= $file['route1_id'] ?? '' ?>')">View</button>
                    <?php if ($file['minutes']): ?>
                    <button type="button" class="view-button" onclick="viewMinutes('<?= htmlspecialchars($file['minutes'], ENT_QUOTES) ?>')">View Minutes</button>
                    <?php endif; ?>
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

    <!-- Minutes Modal Viewer -->
    <div id="minutesModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeMinutesModal()">×</span>
            <div class="modal-layout">
                <div id="minutesModalContent" class="file-preview-section" style="flex: 1;"></div>
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
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
                    <label for="edit-panel3">Panel 3:</label>
                    <select id="edit-panel3" name="panel3" class="form-control">
                        <option value="">None</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
                    <label for="edit-panel4">Panel 4:</label>
                    <select id="edit-panel4" name="panel4" class="form-control">
                        <option value="">None</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
                    <label for="edit-panel5">Panel 5:</label>
                    <select id="edit-panel5" name="panel5" class="form-control">
                        <option value="">None</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname, department FROM panel");
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
    // Function to toggle file selection with assignment check
    function toggleFileSelection(fileElement) {
        const checkbox = fileElement.querySelector("input[type='checkbox']");
        
        // Check if the file is already assigned to a panel (not just adviser)
        if (checkbox.dataset.isAssigned === 'true') {
            // File has panel assignments, don't toggle
            alert("This file already has panel assignments and cannot be selected.");
            return;
        }
        
        // Otherwise toggle the checkbox
        checkbox.checked = !checkbox.checked;
        fileElement.classList.toggle("selected");
    }

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

    // Handles form submission
    document.getElementById("external-submit-button").addEventListener("click", function () {
        const panel1 = document.getElementById("panel1-dropdown").value.trim();
        const panel2 = document.getElementById("panel2-dropdown").value.trim();
        const panel3 = document.getElementById("panel3-dropdown").value.trim();
        const panel4 = document.getElementById("panel4-dropdown").value.trim();
        const panel5 = document.getElementById("panel5-dropdown").value.trim();

        // Set hidden fields
        document.getElementById("hidden-panel1").value = panel1;
        document.getElementById("hidden-panel2").value = panel2;
        document.getElementById("hidden-panel3").value = panel3;
        document.getElementById("hidden-panel4").value = panel4;
        document.getElementById("hidden-panel5").value = panel5;

        // Validate file selection
        const selectedFiles = document.querySelectorAll("input[name='selected_files[]']:checked:not(:disabled)");
        if (selectedFiles.length === 0) {
            alert("Please select at least one eligible file.");
            return;
        }

        // Check if all 5 panel positions are filled
        if (!panel1 || !panel2 || !panel3 || !panel4 || !panel5) {
            alert("All 5 panel positions must be filled. Please select a panel member for each position.");
            return;
        }

        // Submit the form
        document.getElementById("submission-form").submit();
    });

    // Add click event for rows to toggle file selection
    document.addEventListener('DOMContentLoaded', function() {
        const selectableRows = document.querySelectorAll(".selectable-row");
        selectableRows.forEach(row => {
            row.addEventListener('click', function(e) {
                // Only toggle if the click wasn't on a button or a link
                if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A' && e.target.tagName !== 'INPUT') {
                    const checkbox = this.querySelector("input[type='checkbox']");
                    if (checkbox && !checkbox.disabled) {
                        checkbox.checked = !checkbox.checked;
                    }
                }
            });
        });

        // Setup panel dropdown filtering
        setupPanelSelectionLogic();
        setupEditPanelSelectionLogic();
    });

    // Global variable to store current file data for edit mode
    let currentEditFile = null;

    // Function to setup panel selection logic for main dropdowns
    function setupPanelSelectionLogic() {
        // Get all panel dropdowns
        const panelDropdowns = [
            document.getElementById('panel1-dropdown'),
            document.getElementById('panel2-dropdown'),
            document.getElementById('panel3-dropdown'),
            document.getElementById('panel4-dropdown'),
            document.getElementById('panel5-dropdown')
        ];
        
        // Store original options for each dropdown
        const originalOptions = panelDropdowns.map(dropdown => {
            if (!dropdown) return [];
            return Array.from(dropdown.options).filter(option => option.value !== '').map(option => {
                return {value: option.value, text: option.text};
            });
        });
        
        // Function to update available options in each dropdown
        function updateAvailableOptions() {
            // Get currently selected values
            const selectedValues = panelDropdowns.map(dropdown => 
                dropdown ? dropdown.value : null
            ).filter(value => value); // Remove empty values
            
            // Reset and update each dropdown
            panelDropdowns.forEach((dropdown, index) => {
                if (!dropdown) return;
                
                const currentValue = dropdown.value;
                
                // Store the first option (the label)
                const firstOption = dropdown.options[0];
                
                // Clear everything except the first option
                while (dropdown.options.length > 1) {
                    dropdown.remove(1);
                }
                
                // Add all options except those selected in other dropdowns
                originalOptions[index].forEach(option => {
                    if (option.value === currentValue || !selectedValues.includes(option.value)) {
                        const optionElement = document.createElement('option');
                        optionElement.value = option.value;
                        optionElement.textContent = option.text;
                        dropdown.appendChild(optionElement);
                        
                        // Re-select current value if it exists
                        if (option.value === currentValue) {
                            optionElement.selected = true;
                        }
                    }
                });
            });
        }
        
        // Add change event listeners to each dropdown
        panelDropdowns.forEach(dropdown => {
            if (dropdown) {
                dropdown.addEventListener('change', updateAvailableOptions);
            }
        });
        
        // Initialize dropdowns
        updateAvailableOptions();
    }
    
    // Function to setup panel selection logic for edit form dropdowns
    function setupEditPanelSelectionLogic() {
        // Get all edit panel dropdowns
        const editPanelDropdowns = [
            document.getElementById('edit-panel1'),
            document.getElementById('edit-panel2'),
            document.getElementById('edit-panel3'),
            document.getElementById('edit-panel4'),
            document.getElementById('edit-panel5')
        ];
        
        // Store original options for each dropdown
        const originalEditOptions = editPanelDropdowns.map(dropdown => {
            if (!dropdown) return [];
            return Array.from(dropdown.options).filter(option => option.value !== '').map(option => {
                return {value: option.value, text: option.text};
            });
        });
        
        // Function to update available options in each dropdown
        function updateEditAvailableOptions() {
            // Get currently selected values
            const selectedValues = editPanelDropdowns.map(dropdown => 
                dropdown ? dropdown.value : null
            ).filter(value => value); // Remove empty values
            
            // Reset and update each dropdown
            editPanelDropdowns.forEach((dropdown, index) => {
                if (!dropdown) return;
                
                const currentValue = dropdown.value;
                
                // Store the first option (the label)
                const firstOption = dropdown.options[0];
                
                // Clear everything except the first option
                while (dropdown.options.length > 1) {
                    dropdown.remove(1);
                }
                
                // Add all options except those selected in other dropdowns
                originalEditOptions[index].forEach(option => {
                    if (option.value === currentValue || !selectedValues.includes(option.value)) {
                        const optionElement = document.createElement('option');
                        optionElement.value = option.value;
                        optionElement.textContent = option.text;
                        dropdown.appendChild(optionElement);
                        
                        // Re-select current value if it exists
                        if (option.value === currentValue) {
                            optionElement.selected = true;
                        }
                    }
                });
            });
        }
        
        // Add change event listeners to each dropdown
        editPanelDropdowns.forEach(dropdown => {
            if (dropdown) {
                dropdown.addEventListener('change', updateEditAvailableOptions);
            }
        });
        
        // Initialize dropdowns when the edit modal is shown
        document.getElementById('edit-assignments-btn').addEventListener('click', function() {
            setTimeout(updateEditAvailableOptions, 100);
        });
    }

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

    function viewFile(filePath, student_id, route1_id) {
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
        // Load form data dynamically using route2_id
        fetch(`get_all_forms.php?student_id=${encodeURIComponent(student_id)}&route1_id=${encodeURIComponent(route1_id)}`)
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

    function closeMinutesModal() {
        const modal = document.getElementById("minutesModal");
        modal.style.display = "none";
        document.getElementById("minutesModalContent").innerHTML = '';
    }

    function viewMinutes(minutesPath) {
        const modal = document.getElementById("minutesModal");
        const contentArea = document.getElementById("minutesModalContent");

        modal.style.display = "flex";
        contentArea.innerHTML = "<div style='display: flex; justify-content: center; align-items: center; height: 100%;'><div style='text-align: center;'><div class='spinner' style='border: 4px solid rgba(0, 0, 0, 0.1); width: 40px; height: 40px; border-radius: 50%; border-left-color: var(--accent); animation: spin 1s linear infinite; margin: 0 auto;'></div><p style='margin-top: 10px;'>Loading minutes file...</p></div></div>";
        
        const extension = minutesPath.split('.').pop().toLowerCase();
        if (extension === "pdf") {
            contentArea.innerHTML = `<iframe src="${minutesPath}" width="100%" height="100%" style="border: none;"></iframe>`;
        } else {
            contentArea.innerHTML = "<div style='text-align: center; padding: 2rem;'><p style='color: #dc3545;'>Unsupported file type. Only PDF files are supported.</p></div>";
        }
    }

    // Modify the update form to use AJAX for submission
    document.addEventListener('DOMContentLoaded', function() {
        const updateForm = document.getElementById('update-assignments-form');
        if (updateForm) {
            updateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Check if all 5 panel positions are filled
                const panel1 = document.getElementById('edit-panel1').value;
                const panel2 = document.getElementById('edit-panel2').value;
                const panel3 = document.getElementById('edit-panel3').value;
                const panel4 = document.getElementById('edit-panel4').value;
                const panel5 = document.getElementById('edit-panel5').value;
                
                if (!panel1 || !panel2 || !panel3 || !panel4 || !panel5) {
                    alert("All 5 panel positions must be filled. Please select a panel member for each position.");
                    return;
                }
                
                // Create form data object
                const formData = new FormData(this);
                
                // Remove adviser_id from the form data since it's disabled
                formData.delete('adviser_id');
                
                // Send AJAX request
                fetch('route1.php', {
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
                            let isAssigned = false;
                            
                            if (panels.length > 0 && adviserId) {
                                newStatus = 'View Assignments';
                                button.classList.remove('not-assigned');
                                // Only disable if panels are assigned (not just adviser)
                                isAssigned = true;
                            } else if (panels.length > 0) {
                                newStatus = 'View Panelists';
                                button.classList.remove('not-assigned');
                                isAssigned = true;
                            } else if (adviserId) {
                                newStatus = 'View Adviser';
                                button.classList.remove('not-assigned');
                                // Don't disable if only adviser is assigned
                                isAssigned = false;
                            } else {
                                button.classList.add('not-assigned');
                                isAssigned = false;
                            }
                            
                            button.textContent = newStatus;
                            
                            // Update checkbox status based on panel assignment status only
                            if (isAssigned && panels.length > 0) { // Only disable if has panels
                                checkbox.disabled = true;
                                checkbox.dataset.isAssigned = 'true';
                                row.classList.remove('selectable-row');
                                row.classList.add('already-assigned');
                            } else {
                                checkbox.disabled = false;
                                checkbox.dataset.isAssigned = 'false';
                                row.classList.add('selectable-row');
                                row.classList.remove('already-assigned');
                            }
                            
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

    function autoGrow(textarea) {
        textarea.style.height = 'auto'; // Reset height
        textarea.style.height = (textarea.scrollHeight) + 'px'; // Set to scrollHeight
        
        // Ensure minimum height
        if (textarea.scrollHeight < 40) {
            textarea.style.height = '40px';
        }
    }
</script>
<script src="../sidebar.js"></script>


