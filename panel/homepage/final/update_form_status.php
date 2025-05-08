<?php
session_start();
include '../../../connection.php';

header("Content-Type: application/json");

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if panel member is logged in
if (!isset($_SESSION['panel_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Access denied: not authenticated']);
    exit;
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
function sendApprovalNotificationEmail($student_email, $student_name, $panel_name, $form_details) {
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
        $mail->Subject = "Final Defense - Panel Form Approved - Thesis Routing System";
        
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
                <p>Your panel member <strong>{$panel_name}</strong> has <span style='color: green; font-weight: bold;'>APPROVED</span> your Final Defense submission.</p>
                <p><strong>Form Details:</strong></p>
                <ul>
                    <li><strong>Chapter:</strong> {$form_details['chapter']}</li>
                    <li><strong>Feedback:</strong> {$form_details['feedback']}</li>
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
        $mail->AltBody = "Dear {$student_name}, Your panel member {$panel_name} has APPROVED your Final Defense submission for Chapter: {$form_details['chapter']}, Feedback: {$form_details['feedback']}, Paragraph: {$form_details['paragraph_number']}, Page: {$form_details['page_number']}, Route: {$form_details['routeNumber']}. Please login at: {$login_url} to review.";

        // Add additional headers that may help with deliverability
        $mail->addCustomHeader('X-Mailer', 'Thesis Routing System');
        $mail->addCustomHeader('X-Priority', '3');

        $mail->send();
        error_log("Final Defense approval email sent successfully to student: $student_email using PHPMailer");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Final Defense approval email could not be sent to student: $student_email. ";
        
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

// Read JSON from request body
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id']) || !isset($data['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$form_id = intval($data['id']);
$new_status = $conn->real_escape_string($data['status']);
$panel_id = $_SESSION['panel_id'];

// Only proceed if status is valid
if (!in_array($new_status, ['Pending', 'Approved', 'For Revision'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

// Check if the panel is authorized for this form
$checkQuery = "SELECT panel_id FROM final_monitoring_form WHERE id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("i", $form_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Form not found']);
    exit;
}

$row = $result->fetch_assoc();
if ($row['panel_id'] != $panel_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You are not allowed to approve the status because you are not the assigned Panel']);
    exit;
}

// Update the form status in the database
$query = "UPDATE final_monitoring_form SET status = ? WHERE id = ? AND panel_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sii", $new_status, $form_id, $panel_id);

if (!$stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->close();

// If status was changed to "Approved", send an email notification to the student
$email_sent = false;
if ($new_status === 'Approved') {
    // Get form details and student information
    $getFormStmt = $conn->prepare("
        SELECT 
            p.student_id, 
            p.chapter, 
            p.feedback, 
            p.paragraph_number, 
            p.page_number, 
            p.panel_name,
            p.routeNumber,
            p.route1_id,
            p.route2_id,
            p.route3_id,
            p.finaldocu_id,
            s.fullname AS student_name,
            s.email AS student_email
        FROM 
            final_monitoring_form p
        JOIN 
            student s ON p.student_id = s.student_id
        WHERE 
            p.id = ?
    ");
    
    $getFormStmt->bind_param("i", $form_id);
    $getFormStmt->execute();
    $result = $getFormStmt->get_result();
    
    if ($result->num_rows > 0) {
        $formData = $result->fetch_assoc();
        $student_email = $formData['student_email'];
        $student_name = $formData['student_name'];
        $panel_name = $formData['panel_name'];
        
        $form_details = [
            'chapter' => $formData['chapter'],
            'feedback' => $formData['feedback'],
            'paragraph_number' => $formData['paragraph_number'],
            'page_number' => $formData['page_number'],
            'routeNumber' => $formData['routeNumber']
        ];
        
        // Send email notification
        $email_sent = sendApprovalNotificationEmail($student_email, $student_name, $panel_name, $form_details);
    }
    
    $getFormStmt->close();
}

// Return success response with email status
header('Content-Type: application/json');
echo json_encode([
    'success' => true, 
    'email_sent' => $email_sent,
    'message' => $email_sent ? 'Status updated and email notification sent' : 'Status updated but could not send email notification'
]);
?>
