<?php
session_start();

// Import PHPMailer classes at the top of the file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['adviser_id'])) {
    header("Location: ../../../logout.php");
    exit;
}

include '../../../connection.php';

$adviser_id = $_SESSION['adviser_id'];
$fullname = $_SESSION['fullname'] ?? 'Adviser';

// Query to get student id and route2_id based on adviser_id
$stmt = $conn->prepare("SELECT student_id, route2_id FROM route2proposal_files WHERE adviser_id = ?");
if ($stmt === false) {
    die("Error preparing the query: " . $conn->error); // This will show the MySQL error
}

$stmt->bind_param("s", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the student id and route2_id
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_id = $row['student_id'];
    $route2_id = $row['route2_id']; // Now you have the route2_id
    
    // Check if adviser has already reviewed this document
    $adviserReviewCheck = $conn->prepare("SELECT COUNT(*) as count FROM proposal_monitoring_form WHERE route2_id = ? AND adviser_id = ?");
    $adviserReviewCheck->bind_param("is", $route2_id, $adviser_id);
    $adviserReviewCheck->execute();
    $adviserReviewResult = $adviserReviewCheck->get_result();
    $adviserReviewData = $adviserReviewResult->fetch_assoc();
    $hasReviewed = $adviserReviewData['count'] > 0;
    
    if (!$hasReviewed) {
        $advisorNotification = "You must review this document first. Panel members will not be able to access it until you've completed your review.";
    }
} else {
    // Handle case if no student is found (optional)
    $student_id = null;
    $route2_id = null;
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
    $docuRoute2 = $_POST['docuRoute2'];
    $route2_id = $_POST['route2_id'];
    $student_id = $_POST['student_id'];
    $status = $_POST['status'];
    $routeNumberArr = $_POST['routeNumber'];

    // Prepare SQL for inserting form data
    $stmt = $conn->prepare("INSERT INTO proposal_monitoring_form 
    (adviser_id, adviser_name, student_id, date_submitted, chapter, feedback, paragraph_number, page_number, date_released, docuRoute2, route2_id, status, routeNumber) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing the insert query: " . $conn->error);
    }

    // Get student email for notification
    $studentEmailStmt = $conn->prepare("SELECT email, fullname FROM student WHERE student_id = ?");
    $studentEmailStmt->bind_param("s", $student_id);
    $studentEmailStmt->execute();
    $studentEmailResult = $studentEmailStmt->get_result();
    $studentEmailData = $studentEmailResult->fetch_assoc();
    $studentEmail = $studentEmailData['email'] ?? '';
    $studentName = $studentEmailData['fullname'] ?? 'Student';
    $studentEmailStmt->close();

    // Summary of feedback for email notification
    $feedbackSummary = "New feedback for chapters: " . implode(", ", array_slice($chapterArr, 0, 3));
    if (count($chapterArr) > 3) {
        $feedbackSummary .= " and " . (count($chapterArr) - 3) . " more";
    }

    // Loop through the form data and insert each row
    $insertSuccess = true;
    for ($i = 0; $i < count($chapterArr); $i++) {
        $dateSubmitted = $dateSubmittedArr[$i];
        $chapter = $chapterArr[$i];
        $feedback = $feedbackArr[$i];
        $paragraphNumber = $paragraphNumberArr[$i];
        $pageNumber = $pageNumberArr[$i];
        $adviserName = $adviserNameArr[$i];
        $dateReleased = $dateReleasedArr[$i];
        $routeNumber = $routeNumberArr[$i];

        // Bind parameters including the route2_id
        $stmt->bind_param(
            "ssssssissssss",  // 11 specifiers
            $adviser_id, 
            $adviserName, 
            $student_id, 
            $dateSubmitted, 
            $chapter, 
            $feedback, 
            $paragraphNumber, 
            $pageNumber, 
            $dateReleased, 
            $docuRoute2,
            $route2_id,
            $status,
            $routeNumber
        );
        
        // Execute the statement
        if (!$stmt->execute()) {
            echo "<script>alert('Error on row $i: " . addslashes($stmt->error) . "');</script>";
            $insertSuccess = false;
            break;
        }
    }

    // Send email notification to student if data was inserted successfully
    if ($insertSuccess && !empty($studentEmail)) {
        // Check if all adviser feedback items for this route are approved
        $adviserStatusQuery = $conn->prepare("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved 
            FROM proposal_monitoring_form 
            WHERE route2_id = ? AND adviser_id = ?
        ");
        $adviserStatusQuery->bind_param("ss", $route2_id, $adviser_id);
        $adviserStatusQuery->execute();
        $adviserResult = $adviserStatusQuery->get_result();
        $adviserStatus = $adviserResult->fetch_assoc();
        $allAdviserApproved = ($adviserStatus['total'] > 0 && $adviserStatus['total'] == $adviserStatus['approved']);
        $adviserStatusQuery->close();
        
        if (isValidEmail($studentEmail) && $allAdviserApproved) {
            $emailSent = sendStudentNotificationEmail($studentEmail, $studentName, $fullname, $feedbackSummary);
            if ($emailSent) {
                echo "<script>alert('Form submitted successfully and notification email sent to student.'); window.location.href=window.location.href;</script>";
                exit;
            } else {
                echo "<script>alert('Form submitted successfully but failed to send notification email to student.'); window.location.href=window.location.href;</script>";
                exit;
            }
        } else {
            $message = 'Form submitted successfully.';
            if (!isValidEmail($studentEmail)) {
                $message .= ' Student email address is invalid.';
            } else if (!$allAdviserApproved) {
                $message .= ' Email will be sent when all your feedback is marked as Approved.';
            }
            echo "<script>alert('$message'); window.location.href=window.location.href;</script>";
            exit;
        }
    } else if ($insertSuccess) {
        echo "<script>alert('Form submitted successfully. No student email available for notification.'); window.location.href=window.location.href;</script>";
        exit;
    }
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

// Function to send email notification to student
function sendStudentNotificationEmail($student_email, $student_name, $adviser_name, $feedback_summary) {
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
        $mail->Subject = "New Feedback Submitted - Thesis Routing System";
        
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
                <p>Your adviser <strong>{$adviser_name}</strong> has submitted feedback on your thesis proposal.</p>
                <p><strong>Feedback Summary:</strong> {$feedback_summary}</p>
                <p>Please log in to the Thesis Routing System to review the detailed feedback.</p>
                <div style='margin-top: 30px; text-align: center;'>
                    <a href='{$login_url}' style='background-color: #4366b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login to Review</a>
                </div>
                <p style='margin-top: 10px; text-align: center;'>If the button above doesn't work, copy and paste this URL into your browser: <br><a href='{$login_url}'>{$login_url}</a></p>
                <p style='margin-top: 30px; font-size: 12px; color: #777; text-align: center;'>This is an automated message from the Thesis Routing System. Please do not reply to this email.</p>
            </div>
        ";
        $mail->AltBody = "Dear {$student_name}, Your adviser {$adviser_name} has submitted feedback on your thesis proposal. Feedback Summary: {$feedback_summary}. Please login at: {$login_url} to review the detailed feedback.";

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
.action-label {
    text-align: center;
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

.file-preview-section {
    flex: 0 0 45%;
    padding: 1rem;
    overflow-y: auto;
    border-right: 1px solid var(--border);
}

.routing-form-section {
    flex: 0 0 55%;
    padding: 1rem;
    overflow-y: auto;
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
        
        /* Specific style for the feedback cell (3rd column) in 10-column grid */
        .form-grid-container > div:nth-child(10n + 3) {
            text-align: left;
            justify-content: flex-start;
            overflow-y: visible;
            max-height: none;
            height: auto;
            white-space: pre-wrap;
        }
        
        .form-grid-container1 {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .form-grid-container1 > div {
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
        
        /* Specific style for the feedback cell (3rd column) in 8-column grid */
        .form-grid-container1 > div:nth-child(8n + 3) {
            text-align: left;
            justify-content: flex-start;
            overflow-y: visible;
            max-height: none;
            height: auto;
            white-space: pre-wrap;
        }

        .form-grid-container1 input,
        .form-grid-container1 textarea {
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
        flex: 0 0 300px;
        border-right: none;
        border-bottom: 1px solid var(--border);
    }
    
    .routing-form-section {
        flex: 1 1 auto;
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

/* Change modal layout proportions */
.modal-layout {
    display: flex;
    height: 100%;
    width: 100%;
}

.file-preview-section {
    flex: 0 0 45%;
    padding: 1rem;
    overflow-y: auto;
    border-right: 1px solid var(--border);
}

.routing-form-section {
    flex: 0 0 55%;
    padding: 1rem;
    overflow-y: auto;
    background-color: #f9f9f9;
    font-size: 0.85rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 5px;
    margin-bottom: 10px;
}

/* CSS Animation for spinner */
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
                <div class="routeNo" style="margin-right: 20px;">Proposal - Route 2</div>
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
                <?php if (isset($advisorNotification)): ?>
                <div style="background-color: #ffe6e6; border-left: 4px solid #ff3333; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                    <h4 style="margin-top: 0; color: #d32f2f;">Important Workflow Requirement</h4>
                    <p><?php echo $advisorNotification; ?></p>
                    <p><strong>Note:</strong> Panel members will only be able to see and review the document after you have submitted your feedback.</p>
                </div>
                <?php endif; ?>
                
                <!-- Search bar -->
                <div class="search-container">
                    <input type="text" id="searchInput" class="search-box" placeholder="Search by leader name..." onkeyup="searchTable()">
                </div>
                
                <?php
                $query = "
                    SELECT 
                        docuRoute2, 
                        student_id, 
                        route2_id, 
                        department, 
                        group_number, 
                        controlNo, 
                        fullname, 
                        title,
                        minutes
                    FROM route2proposal_files 
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
                                <th>Minutes</th>
                                <th class='action-label'>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                    ";

                    while ($row = $result->fetch_assoc()) {
                        $filePath = htmlspecialchars($row['docuRoute2'], ENT_QUOTES);
                        $student_id = htmlspecialchars($row['student_id'], ENT_QUOTES);
                        $route2_id = htmlspecialchars($row['route2_id'], ENT_QUOTES);
                        $groupNo = htmlspecialchars($row['group_number'], ENT_QUOTES);
                        $controlNo = htmlspecialchars($row['controlNo'], ENT_QUOTES);
                        $fullName = htmlspecialchars($row['fullname'], ENT_QUOTES);
                        $title = htmlspecialchars($row['title'], ENT_QUOTES);
                        
                        // Get minutes file from route1proposal_files
                        $minutesQuery = $conn->prepare("SELECT minutes FROM route1proposal_files WHERE student_id = ?");
                        $minutesQuery->bind_param("s", $student_id);
                        $minutesQuery->execute();
                        $minutesResult = $minutesQuery->get_result();
                        $minutes = '';
                        
                        if ($minutesResult->num_rows > 0) {
                            $minutesRow = $minutesResult->fetch_assoc();
                            $minutes = $minutesRow['minutes'] ? htmlspecialchars($minutesRow['minutes'], ENT_QUOTES) : '';
                        }
                        $minutesQuery->close();
                        
                        $minutesStatus = $minutes ? '<span style="color: green;">Available</span>' : '<span style="color: red;">Not Available</span>';
                        
                        echo "
                            <tr>
                                <td>$controlNo</td>
                                <td>$fullName</td>
                                <td>$groupNo</td>
                                <td>$title</td>
                                <td>$minutesStatus</td>
                                <td style='text-align: center;'>
                                    <button class='view-button' onclick=\"viewFile('$filePath', '$student_id', '$route2_id')\">View</button>";
                        
                        if ($minutes) {
                            echo "<button class='view-button' onclick=\"viewMinutes('$minutes')\">View Minutes</button>";
                        }
                        
                        echo "</td>
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
        const adviserName = <?= json_encode($fullname) ?>;

        function viewFile(filePath, student_id, route2_id) {
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
            <input type="hidden" name="docuRoute2" value="${filePath}">
            <input type="hidden" name="student_id" value="${student_id}">
            <input type="hidden" name="route2_id" value="${route2_id}">
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
                    <button type="button" onclick="doneEditing()" style="background-color: var(--success); color: white;">Done</button>
                    <button type="submit">Submit Routing Form</button>
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

            <div id="routingRowsContainer">
                <div class="form-grid-container1">
                    <div><input type="text" placeholder="Date Submitted" name="dateSubmitted[]" value="${today}" readonly></div>
                    <div><input type="text" placeholder="Chapter" name="chapter[]" required></div>
                    <div><textarea placeholder="Feedback" name="feedback[]" required oninput="autoGrow(this)"></textarea></div>
                    <div><input type="number" placeholder="Paragraph No" name="paragraphNumber[]" required></div>
                    <div><input type="number" placeholder="Page No" name="pageNumber[]" required></div>
                    <div><input type="text" placeholder="Submitted By" name="adviserName[]" value="${adviserName}" readonly></div>
                    <div><input type="date" placeholder="Date Released" name="dateReleased[]" value="${today}" required></div>
                    <div><input type="text" placeholder="Route Number" name="routeNumber[]" value="Route 2" required></div>
                    
                </div>
            </div>
        </form>
    `;
    
    // Load all forms by default
    loadAllForms(student_id);
}

function closeModal() {
    document.getElementById("fileModal").style.display = "none";
}

function addFormRow() {
    const today = new Date().toISOString().split('T')[0];
    const row = `
        <div class="form-grid-container1">
            <div><input type="text" name="dateSubmitted[]" value="${today}" readonly></div>
            <div><input type="text" name="chapter[]" required></div>
            <div><textarea name="feedback[]" required oninput="autoGrow(this)"></textarea></div>
            <div><input type="number" name="paragraphNumber[]" required></div>
            <div><input type="number" name="pageNumber[]" required></div>
            <div><input type="text" name="adviserName[]" value="${adviserName}" readonly></div>
            <div><input type="text" name="dateReleased[]" value="${today}" required></div>
             <div><input type="text" placeholder="Route Number" name="routeNumber[]" value="Route 2" required></div>
        </div>
    `;
    document.getElementById("routingRowsContainer").insertAdjacentHTML("beforeend", row);
}

let formsVisible = true;

function loadAllForms(student_id) {
    const formDataContainer = document.getElementById("submittedFormsContainer");
    const noFormsMessage = document.getElementById("noFormsMessage");
    
    // Show loading spinner
    formDataContainer.innerHTML = "<div style='grid-column: span 9; display: flex; justify-content: center; padding: 1rem;'><div class='spinner'></div></div>";

    // Fetch data with correct path
    fetch('route2get_all_forms.php?student_id=' + student_id)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
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
            noFormsMessage.innerText = "Error loading forms: " + error.message;
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
    textarea.style.height = (textarea.scrollHeight) + 'px'; // Set to scrollHeight
    
    // Ensure minimum height
    if (textarea.scrollHeight < 40) {
        textarea.style.height = '40px';
    }
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
    .then(response => {
        // Check if response is ok (status code 200-299)
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Disable the save button once status is saved
            document.getElementById(`saveButton_${formId}`).disabled = true;
            
            // Show a success message
            const statusCell = statusSelect.parentElement;
            statusCell.style.backgroundColor = '#e8f5e9';
            
            // If all forms are approved, show a special message
            if (data.all_approved) {
                alert("All your feedback forms for this student have been approved! An email notification has been sent to the student.");
            }
        } else {
            alert(data.message || 'Failed to update status.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the status: ' + error.message);
    });
}

function enableSaveButton(formId) {
    const saveButton = document.getElementById(`saveButton_${formId}`);
    saveButton.disabled = false;  // Enable the save button
}

function doneEditing() {
    // Get the form elements
    const form = document.querySelector('#routingForm form');
    const student_id = form.querySelector('input[name="student_id"]').value;
    const route2_id = form.querySelector('input[name="route2_id"]').value;
    const docuRoute2 = form.querySelector('input[name="docuRoute2"]').value;
    const adviserName = form.querySelector('input[name="adviserName[]"]').value;
    
    // Confirm the action
    if (confirm("Are you sure you want to mark this as done? This will send an approval notification to the student.")) {
        // Create a form data object
        const formData = new FormData();
        
        // Add a single entry with "No comments" as feedback
        formData.append('dateSubmitted[]', new Date().toISOString().split('T')[0]);
        formData.append('chapter[]', 'All');
        formData.append('feedback[]', 'Document approved. No additional comments.');
        formData.append('paragraphNumber[]', '0');
        formData.append('pageNumber[]', '0');
        formData.append('adviserName[]', adviserName);
        formData.append('dateReleased[]', new Date().toISOString().split('T')[0]);
        formData.append('routeNumber[]', 'Route 2');
        formData.append('docuRoute2', docuRoute2);
        formData.append('route2_id', route2_id);
        formData.append('student_id', student_id);
        formData.append('status', 'Approved');
        
        // Submit the form data using fetch
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                alert('Document marked as approved. The student has been notified by email.');
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

<?php if ($showModal): ?>
    window.addEventListener('load', () => {
        viewFile("<?= addslashes($lastFilePath) ?>", "<?= addslashes($student_id) ?>", "<?= addslashes($route2_id) ?>");
    });
<?php endif; ?>

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
function closeMinutesModal() {
    const modal = document.getElementById("minutesModal");
    modal.style.display = "none";
    document.getElementById("minutesModalContent").innerHTML = '';
}

function viewMinutes(minutesPath) {
    const modal = document.getElementById("minutesModal");
    const contentArea = document.getElementById("minutesModalContent");

    if (!minutesPath) {
        alert("No minutes available for this document.");
        return;
    }

    modal.style.display = "flex";
    contentArea.innerHTML = "<div style='display: flex; justify-content: center; align-items: center; height: 100%;'><div style='text-align: center;'><div class='spinner'></div><p style='margin-top: 10px;'>Loading minutes file...</p></div></div>";
    
    const extension = minutesPath.split('.').pop().toLowerCase();
    if (extension === "pdf") {
        contentArea.innerHTML = `<iframe src="${minutesPath}" width="100%" height="100%" style="border: none;"></iframe>`;
    } else {
        contentArea.innerHTML = "<div style='text-align: center; padding: 2rem;'><p style='color: #dc3545;'>Unsupported file type. Only PDF files are supported.</p></div>";
    }
}
</script>