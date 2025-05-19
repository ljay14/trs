<?php
include '../../../connection.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json");

// Make sure session is started
session_start();

// Debug log
error_log("update_form_status.php called - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION: " . print_r($_SESSION, true));

// Get data from either POST form data or JSON input
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the request has a JSON content type
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Read JSON input
        $input = file_get_contents("php://input");
        error_log("Raw JSON input: " . $input);
        $data = json_decode($input, true);
        error_log("Decoded JSON data: " . print_r($data, true));
    } else {
        // Use regular POST data
        error_log("Using regular POST data");
        $data = $_POST;
    }
}

// Function to validate email address
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Function to log email errors
function logEmailError($message) {
    $logFile = __DIR__ . '/../../../email_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    // Also log to PHP error log
    error_log($message);
    
    // Write to custom log file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Function to send approval notification email to student
function sendApprovalNotificationEmail($student_email, $student_name, $adviser_name, $form_details) {
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
        $mail->Subject = "Form Approved - Thesis Routing System";
        
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
                <p>Your adviser <strong>{$adviser_name}</strong> has <span style='color: green; font-weight: bold;'>APPROVED</span> your submission.</p>
                <p><strong>Form Details:</strong></p>
                <ul>
                    <li><strong>Chapter:</strong> {$form_details['chapter']}</li>
                    <li><strong>Paragraph:</strong> {$form_details['paragraph_number']}</li>
                    <li><strong>Page:</strong> {$form_details['page_number']}</li>
                    <li><strong>Route:</strong> {$form_details['routeNumber']}</li>
                </ul>
                <p>Please log in to the Thesis Routing System to review the approval and proceed with your next steps.</p>
                <div style='margin-top: 30px; text-align: center;'>
                    <a href='{$login_url}' style='background-color: #4366b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login to System</a>
                </div>
                <p style='margin-top: 10px; text-align: center;'>If the button above doesn't work, copy and paste this URL into your browser: <br><a href='{$login_url}'>{$login_url}</a></p>
                <p style='margin-top: 30px; font-size: 12px; color: #777; text-align: center;'>This is an automated message from the Thesis Routing System. Please do not reply to this email.</p>
            </div>
        ";
        $mail->AltBody = "Dear {$student_name}, Your adviser {$adviser_name} has APPROVED your submission for Chapter: {$form_details['chapter']}, Paragraph: {$form_details['paragraph_number']}, Page: {$form_details['page_number']}, Route: {$form_details['routeNumber']}. Please login at: {$login_url} to review.";

        // Add additional headers that may help with deliverability
        $mail->addCustomHeader('X-Mailer', 'Thesis Routing System');
        $mail->addCustomHeader('X-Priority', '3');

        $mail->send();
        error_log("Approval email sent successfully to student: $student_email using PHPMailer");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Approval email could not be sent to student: $student_email. ";
        
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

// Function to send comprehensive approval notification email to student
function sendAllApprovedNotification($student_email, $student_name, $adviser_name, $feedback_summary, $route_number) {
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
        $mail->Subject = "All Feedback Approved - Thesis Routing System";
        
        // Get server URL dynamically
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        $server_port = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '';
        $http_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $base_url = $http_protocol . '://' . $server_name . $server_port;
        
        $login_url = $base_url . '/TRS/student/';
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                <h2 style='color: #4366b3; text-align: center;'>Thesis Routing System - All Feedback Approved</h2>
                <p>Dear <strong>{$student_name}</strong>,</p>
                <p>Congratulations! Your adviser <strong>{$adviser_name}</strong> has <span style='color: green; font-weight: bold;'>APPROVED ALL</span> feedback for your thesis proposal!</p>
                <p><strong>Summary:</strong> {$feedback_summary}</p>
                <p><strong>Route:</strong> {$route_number}</p>
                <p>You may now proceed to the next step in your thesis workflow. Please log in to the Thesis Routing System to view all approved feedback and continue with your thesis progress.</p>
                <div style='margin-top: 30px; text-align: center;'>
                    <a href='{$login_url}' style='background-color: #4366b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login to System</a>
                </div>
                <p style='margin-top: 10px; text-align: center;'>If the button above doesn't work, copy and paste this URL into your browser: <br><a href='{$login_url}'>{$login_url}</a></p>
                <p style='margin-top: 30px; font-size: 12px; color: #777; text-align: center;'>This is an automated message from the Thesis Routing System. Please do not reply to this email.</p>
            </div>
        ";
        $mail->AltBody = "Dear {$student_name}, Congratulations! Your adviser {$adviser_name} has APPROVED ALL feedback for your thesis proposal! Summary: {$feedback_summary}, Route: {$route_number}. Please login at: {$login_url} to continue with your thesis progress.";

        // Add additional headers that may help with deliverability
        $mail->addCustomHeader('X-Mailer', 'Thesis Routing System');
        $mail->addCustomHeader('X-Priority', '3');

        $mail->send();
        error_log("All approved notification email sent successfully to student: $student_email using PHPMailer");
        return true;
    } catch (Exception $e) {
        $errorMsg = "All approved notification email could not be sent to student: $student_email. ";
        
        if (isset($mail)) {
            $errorMsg .= "PHPMailer Error: " . $mail->ErrorInfo;
        } else {
            $errorMsg .= "Exception: " . $e->getMessage();
        }
        
        logEmailError($errorMsg);
        return false;
    }
}

// Check if we have the required data
if (!isset($data['id']) || !isset($data['status'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$form_id = $data['id'];
$new_status = $data['status'];

// Get adviser ID from session
$adviser_id = isset($_SESSION['adviser_id']) ? $_SESSION['adviser_id'] : null;
$adviser_name = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Adviser';

if (!$adviser_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Adviser not logged in'
    ]);
    exit;
}

// Update the form status in the database
$stmt = $conn->prepare("UPDATE proposal_monitoring_form SET status = ? WHERE id = ? AND adviser_id = ?");
$stmt->bind_param("sis", $new_status, $form_id, $adviser_id);
$result = $stmt->execute();

if ($result) {
    // Get the form details to use in email notification
    $formQuery = $conn->prepare("
        SELECT 
            student_id, 
            chapter, 
            paragraph_number, 
            page_number, 
            routeNumber,
            route2_id
        FROM 
            proposal_monitoring_form 
        WHERE 
            id = ?
    ");
    $formQuery->bind_param("i", $form_id);
    $formQuery->execute();
    $formResult = $formQuery->get_result();
    
    if ($formResult->num_rows > 0) {
        $formData = $formResult->fetch_assoc();
        $student_id = $formData['student_id'];
        $route2_id = $formData['route2_id'];
        
        // Get student email for notification
        $studentQuery = $conn->prepare("SELECT email, fullname FROM student WHERE student_id = ?");
        $studentQuery->bind_param("s", $student_id);
        $studentQuery->execute();
        $studentResult = $studentQuery->get_result();
        
        if ($studentResult->num_rows > 0) {
            $studentData = $studentResult->fetch_assoc();
            $student_email = $studentData['email'];
            $student_name = $studentData['fullname'];
            
            // Send email notification if status is Approved
            if ($new_status === 'Approved') {
                // Send individual approval notification
                sendApprovalNotificationEmail($student_email, $student_name, $adviser_name, $formData);
                
                // Check if all forms for this student and adviser are approved
                $allFormsQuery = $conn->prepare("
                    SELECT 
                        COUNT(*) as total, 
                        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved 
                    FROM 
                        proposal_monitoring_form 
                    WHERE 
                        student_id = ? AND 
                        adviser_id = ? AND
                        route2_id = ?
                ");
                $allFormsQuery->bind_param("ssi", $student_id, $adviser_id, $route2_id);
                $allFormsQuery->execute();
                $allFormsResult = $allFormsQuery->get_result();
                $allFormsData = $allFormsResult->fetch_assoc();
                
                $all_approved = ($allFormsData['total'] > 0 && $allFormsData['total'] == $allFormsData['approved']);
                
                if ($all_approved) {
                    // Get a summary of the feedback
                    $feedbackQuery = $conn->prepare("
                        SELECT GROUP_CONCAT(chapter SEPARATOR ', ') as chapters
                        FROM proposal_monitoring_form 
                        WHERE student_id = ? AND adviser_id = ? AND route2_id = ?
                    ");
                    $feedbackQuery->bind_param("ssi", $student_id, $adviser_id, $route2_id);
                    $feedbackQuery->execute();
                    $feedbackResult = $feedbackQuery->get_result();
                    $feedbackData = $feedbackResult->fetch_assoc();
                    
                    $feedback_summary = "All feedback for chapters " . $feedbackData['chapters'] . " has been approved";
                    
                    // Send comprehensive approval notification
                    sendAllApprovedNotification($student_email, $student_name, $adviser_name, $feedback_summary, $formData['routeNumber']);
                    
                    echo json_encode([
                        'success' => true,
                        'all_approved' => true,
                        'message' => 'Status updated successfully. All forms are now approved.'
                    ]);
                    exit;
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'all_approved' => false,
        'message' => 'Status updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update status: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
