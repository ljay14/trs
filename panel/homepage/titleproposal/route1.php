<?php
session_start();

// Import PHPMailer classes at the top of the file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['panel_id'])) {
    header("Location: ../../../logout.php");
    exit;
}

include '../../../connection.php';

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

// Function to send email notification to student
function sendStudentNotificationEmail($student_email, $student_name, $panel_name, $feedback_summary) {
    try {
        // Validate email address first
        if (!isValidEmail($student_email)) {
            logEmailError("Invalid email address format: $student_email");
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
        $mail->SMTPDebug  = 0;  // Enable verbose debug output (0 for no output, 2 for verbose)
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
        $mail->addAddress($student_email, $student_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "New Panel Feedback Submitted - Thesis Routing System";
        
        // Get server URL dynamically
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        $server_port = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '';
        $http_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $base_url = $http_protocol . '://' . $server_name . $server_port;
        
        $login_url = $base_url . '/TRS/student/';
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                <h2 style='color: #4366b3; text-align: center;'>Thesis Routing System Notification</h2>
                <p>Dear <strong>{$student_name}</strong>,</p>
                <p>Your panel member <strong>{$panel_name}</strong> has submitted feedback on your thesis proposal.</p>
                <p><strong>Feedback Summary:</strong> {$feedback_summary}</p>
                <p>Please log in to the Thesis Routing System to review the detailed feedback.</p>
                <div style='margin-top: 30px; text-align: center;'>
                    <a href='{$login_url}' style='background-color: #4366b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login to Review</a>
                </div>
                <p style='margin-top: 10px; text-align: center;'>If the button above doesn't work, copy and paste this URL into your browser: <br><a href='{$login_url}'>{$login_url}</a></p>
                <p style='margin-top: 30px; font-size: 12px; color: #777; text-align: center;'>This is an automated message from the Thesis Routing System. Please do not reply to this email.</p>
            </div>
        ";
        $mail->AltBody = "Dear {$student_name}, Your panel member {$panel_name} has submitted feedback on your thesis proposal. Feedback Summary: {$feedback_summary}. Please login at: {$login_url} to review the detailed feedback.";

        // Add additional headers that may help with deliverability
        $mail->addCustomHeader('X-Mailer', 'Thesis Routing System');
        $mail->addCustomHeader('X-Priority', '3');

        $mail->send();
        error_log("Email sent successfully to student: $student_email using PHPMailer");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Email could not be sent to student: $student_email. ";
        
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
$fullname = $_SESSION['fullname'] ?? 'Panelist';

$stmt = $conn->prepare("SELECT student_id, route1_id FROM route1proposal_files 
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
    $route1_id = $row['route1_id'];
} else {
    $student_id = null;
    $route1_id = null;
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
    $docuRoute1 = $_POST['docuRoute1'];
    $route1_id = $_POST['route1_id'];
    $student_id = $_POST['student_id'];
    $status = $_POST['status'];
    $routeNumberArr = $_POST['routeNumber'];

    // Prepare the query
    $stmt = $conn->prepare("INSERT INTO proposal_monitoring_form (panel_id, panel_name, date_submitted, chapter, feedback, paragraph_number, page_number, date_released, docuRoute1, route1_id, student_id, status, routeNumber) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Loop over the arrays and insert data
    $insertSuccess = true;
    for ($i = 0; $i < count($chapterArr); $i++) {
        $dateSubmitted = $dateSubmittedArr[$i];
        $chapter = $chapterArr[$i];
        $feedback = $feedbackArr[$i];
        $paragraphNumber = $paragraphNumberArr[$i];
        $pageNumber = $pageNumberArr[$i];
        $panelName = $panelNameArr[$i];
        $dateReleased = $dateReleasedArr[$i];
        $routeNumber = $routeNumberArr[$i];

        $stmt->bind_param(
            "ssssssisssiss", 
            $panel_id, 
            $panelName, 
            $dateSubmitted, 
            $chapter, 
            $feedback, 
            $paragraphNumber, 
            $pageNumber, 
            $dateReleased, 
            $docuRoute1, 
            $route1_id, 
            $student_id,
            $status,
            $routeNumber
        );

        if (!$stmt->execute()) {
            echo "<script>alert('Error on row $i: " . addslashes($stmt->error) . "');</script>";
            $insertSuccess = false;
            break;
        }
    }

    // Get student email for notification
    if ($insertSuccess) {
        $studentEmailStmt = $conn->prepare("SELECT email, fullname FROM student WHERE student_id = ?");
        $studentEmailStmt->bind_param("s", $student_id);
        $studentEmailStmt->execute();
        $studentEmailResult = $studentEmailStmt->get_result();
        $studentEmailData = $studentEmailResult->fetch_assoc();
        $studentEmail = $studentEmailData['email'] ?? '';
        $studentName = $studentEmailData['fullname'] ?? 'Student';
        $studentEmailStmt->close();

        // Check if all adviser statuses are approved before sending email
        $adviserStatusQuery = $conn->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved 
            FROM proposal_monitoring_form 
            WHERE route1_id = ? AND adviser_id IS NOT NULL
        ");
        $adviserStatusQuery->bind_param("s", $route1_id);
        $adviserStatusQuery->execute();
        $adviserResult = $adviserStatusQuery->get_result();
        $adviserStatus = $adviserResult->fetch_assoc();
        $allAdviserApproved = ($adviserStatus['total'] > 0 && $adviserStatus['total'] == $adviserStatus['approved']);
        $adviserStatusQuery->close();

        // Summary of feedback for email notification
        $feedbackSummary = "New feedback for chapters: " . implode(", ", array_slice($chapterArr, 0, 3));
        if (count($chapterArr) > 3) {
            $feedbackSummary .= " and " . (count($chapterArr) - 3) . " more";
        }

        // Send email notification to student if email is available AND all adviser statuses are approved
        if (!empty($studentEmail) && $allAdviserApproved) {
            if (isValidEmail($studentEmail)) {
                $emailSent = sendStudentNotificationEmail($studentEmail, $studentName, $fullname, $feedbackSummary);
                if ($emailSent) {
                    echo "<script>alert('Form submitted successfully and notification email sent to student.'); window.location.href=window.location.href;</script>";
                    exit;
                } else {
                    echo "<script>alert('Form submitted successfully but failed to send notification email to student.'); window.location.href=window.location.href;</script>";
                    exit;
                }
            } else {
                echo "<script>alert('Form submitted successfully but student email address is invalid.'); window.location.href=window.location.href;</script>";
                exit;
            }
        } else {
            $message = 'Form submitted successfully.';
            if (!empty($studentEmail) && !$allAdviserApproved) {
                $message .= ' Email will be sent when all adviser feedback is approved.';
            } elseif (empty($studentEmail)) {
                $message .= ' No student email available for notification.';
            }
            echo "<script>alert('$message'); window.location.href=window.location.href;</script>";
            exit;
        }
    } else {
        echo "<script>alert('Error submitting the form.'); window.location.href=window.location.href;</script>";
        exit;
    }
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

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
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

.delete-button {
    background-color: var(--accent);
    color: white;
    margin-right: 0.5rem;
}

.delete-button:hover {
    background-color: var(--primary);
}

/* Action column styling */
.action-buttons {
    display: flex;
    justify-content: center;
    gap: 8px;
}

.action-label {
    text-align: center;
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
            grid-template-columns: repeat(8, 1fr);
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

        /* Specific style for the feedback cell (3rd column) - supports different grid sizes */
        .form-grid-container > div:nth-child(8n + 3) {
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
            min-height: 40px;
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
    </style>
    
    <script>
    const panelName = <?= json_encode($fullname) ?>;

    function viewFile(filePath, route1_id, student_id) {
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
            <input type="hidden" name="docuRoute1" value="${filePath}">
            <input type="hidden" name="student_id" value="${student_id}">
            <input type="hidden" name="route1_id" value="${route1_id}">
            <input type="hidden" name="status" value="Pending">

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
                    <button type="button" onclick="addFormRow()">Add Row</button>
                    <button type="submit">Submit Routing Form</button>
                    <button type="button" id="toggleFormsBtn" onclick="toggleForms('${route1_id}')">Show less</button>
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
                <div><strong>Route Number</strong></div>
                <div><strong>Status</strong></div>
            </div>

            <!-- Container for submitted form data -->
            <div id="submittedFormsContainer" class="form-grid-container"></div>
            <div id="noFormsMessage" style="margin-top: 10px; color: gray;"></div>

            <div id="routingRowsContainer">
                <div class="form-grid-container">
                    <div><input type="text" name="dateSubmitted[]" value="${today}" readonly></div>
                    <div><input type="text" name="chapter[]" required></div>
                    <div><textarea name="feedback[]" required oninput="autoGrow(this)"></textarea></div>
                    <div><input type="number" name="paragraphNumber[]" required></div>
                    <div><input type="number" name="pageNumber[]" required></div>
                    <div><input type="text" name="panelName[]" value="${panelName}" readonly></div>
                    <div><input type="date" name="dateReleased[]" value="${today}" required></div>
                    <div><input type="text" name="routeNumber[]" value="Route 1" required></div>
                </div>
            </div>
        </form>
    `;
    
    // Load forms by default when opening the modal
    loadAllForms(route1_id);
}

let formsVisible = true; // Set to true by default, since we're showing forms initially

function toggleForms(route1_id) {
    const formDataContainer = document.getElementById("submittedFormsContainer");
    const noFormsMessage = document.getElementById("noFormsMessage");
    const toggleButton = document.getElementById("toggleFormsBtn");

    if (formsVisible) {
        // Hide forms
        formDataContainer.innerHTML = "";
        noFormsMessage.innerText = "";
        toggleButton.textContent = "Show all Forms";
        formsVisible = false;
    } else {
        // Show forms
        loadAllForms(route1_id);
    }
}

function loadAllForms(route1_id) {
    const formDataContainer = document.getElementById("submittedFormsContainer");
    const noFormsMessage = document.getElementById("noFormsMessage");
    const toggleButton = document.getElementById("toggleFormsBtn");

    // Show loading spinner
    formDataContainer.innerHTML = "<div style='grid-column: span 7; display: flex; justify-content: center; padding: 1rem;'><div class='spinner'></div></div>";

    // Fetch data
    fetch('get_all_forms.php?route1_id=' + route1_id)
        .then(response => response.json())
        .then(data => {
            formDataContainer.innerHTML = ""; // Clear old content first

            if (!data || data.length === 0) {
                noFormsMessage.innerText = "No routing forms submitted yet.";
                return;
            }

            noFormsMessage.innerText = ""; // Clear message if forms exist

            data.forEach(row => {
                let submittedBy = "N/A";
                if (row.adviser_name) {
                    submittedBy = `${row.adviser_name} - Adviser`;
                } else if (row.panel_name) {
                    submittedBy = `${row.panel_name} - Panel`;
                }
                
                const formId = row.id;
                const statusValue = (row.status || 'Pending').trim();

                formDataContainer.innerHTML += `
                    <div>${row.date_submitted}</div>
                    <div>${row.chapter}</div>
                    <div class="feedback-cell">${row.feedback}</div>
                    <div>${row.paragraph_number}</div>
                    <div>${row.page_number}</div>
                    <div>${submittedBy}</div>
                    <div>${row.date_released}</div>
                    <div>${row.routeNumber}</div>
                `;
            });

            toggleButton.textContent = "Show less";
            formsVisible = true;
        })
        .catch(error => {
            console.error('Error fetching forms:', error);
            noFormsMessage.innerText = "Failed to load forms.";
            formDataContainer.innerHTML = "";
        });
}

    function autoGrow(textarea) {
        textarea.style.height = 'auto'; // Reset height
        textarea.style.height = (textarea.scrollHeight) + 'px'; // Set to scrollHeight
        
        // Ensure minimum height
        if (textarea.scrollHeight < 40) {
            textarea.style.height = '40px';
        }
    }

    // For modal animation
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
            closeMinutesModal();
        }
    });
    function closeModal() {
    document.getElementById("fileModal").style.display = "none";
}

    function closeMinutesModal() {
        document.getElementById("minutesModal").style.display = "none";
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

    function enableSaveButton(formId) {
        const saveButton = document.getElementById(`saveButton_${formId}`);
        saveButton.disabled = false; // Enable the save button
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
                status: newStatus
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
                <a href="../homepage.php">Home Page</a>
            </div>
            <div class="dropdown-container">
    <?php
    // Check if the current user is a panel1 for any document
    $checkPanel1Query = "SELECT COUNT(*) as count FROM route1proposal_files WHERE panel1_id = ? OR panel2_id = ? OR panel3_id = ? OR panel4_id = ? OR panel5_id = ?";
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
                <div class="routeNo" style="margin-right: 20px;">Proposal - Route 1</div>
                <div class="vl"></div>
                <span class="role">Panelist:</span>
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
                    docuRoute1, 
                    department, 
                    student_id, 
                    route1_id, 
                    controlNo, 
                    fullname, 
                    group_number,
                    title,
                    minutes
                FROM route1proposal_files 
                WHERE (panel1_id = ? OR panel2_id = ? OR panel3_id = ? OR panel4_id = ? OR panel5_id = ?)
                " . ($selectedDepartment ? " AND department = ?" : "");

            $stmt = $conn->prepare($query);

            if ($selectedDepartment) {
                $stmt->bind_param("ssssss", $panel_id, $panel_id, $panel_id, $panel_id, $panel_id, $selectedDepartment);
            } else {
                $stmt->bind_param("sssss", $panel_id, $panel_id, $panel_id, $panel_id, $panel_id);
            }

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
                            <th>Minutes</th>
                            <th class='action-label'>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                ";

                while ($row = $result->fetch_assoc()) {
                    $filePath = htmlspecialchars($row['docuRoute1'], ENT_QUOTES);
                    $route1_id = htmlspecialchars($row['route1_id'], ENT_QUOTES);
                    $student_id = htmlspecialchars($row['student_id'], ENT_QUOTES);
                    $fileName = basename($filePath);
                    $controlNo = htmlspecialchars($row['controlNo'], ENT_QUOTES);
                    $fullname = htmlspecialchars($row['fullname'], ENT_QUOTES);
                    $groupNo = htmlspecialchars($row['group_number'], ENT_QUOTES);
                    $title = htmlspecialchars($row['title'], ENT_QUOTES);
                    $minutes = $row['minutes'] ? htmlspecialchars($row['minutes'], ENT_QUOTES) : '';
                    
                    $minutesStatus = $minutes ? '<span style="color: green;">Available</span>' : '<span style="color: red;">Not Available</span>';

                    echo "
                        <tr>
                            <td>$controlNo</td>
                            <td>$fullname</td>
                            <td>$groupNo</td>
                            <td>$title</td>
                            <td>$minutesStatus</td>
                            <td style='text-align: center;'>
                                <button class='view-button' onclick=\"viewFile('$filePath', '$route1_id', '$student_id')\">View</button>";
                                
                    if ($minutes) {
                        echo "<button class='view-button' onclick=\"viewMinutes('$minutes')\">View Minutes</button>";
                    }
                    
                    echo "    </td>
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

    <!-- Minutes Modal Viewer -->
    <div id="minutesModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeMinutesModal()">×</span>
            <div class="modal-layout">
                <div id="minutesModalContent" class="file-preview-section" style="flex: 1;"></div>
            </div>
        </div>
    </div>


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

        document.getElementById("submit-file-button")?.addEventListener("click", function(e) {
            e.preventDefault();
            document.querySelector("#docuRoute1").click();
        });
        
        document.querySelector("#docuRoute1")?.addEventListener("change", function() {
            document.querySelector("#file-upload-form").submit();
        });

        // Search function for the table
        function searchTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.querySelector("table tbody");
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