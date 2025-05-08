<?php
// Import PHPMailer classes at the top of the file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

include 'connection.php';

// Function to validate email address
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Custom error logging function for email issues
function logEmailError($message) {
    $logFile = __DIR__ . '/email_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    
    // Also log to PHP error log
    error_log($message);
    
    // Write to custom log file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Function to send email notification to student
function sendStudentNotificationEmail($student_email, $student_name, $sender_name, $feedback_summary, $is_adviser = false) {
    try {
        // Validate email address first
        if (!isValidEmail($student_email)) {
            logEmailError("Invalid email address format: $student_email");
            return false;
        }
        
        // Check for Composer autoloader
        $autoloader_path = __DIR__ . '/vendor/autoload.php';
        
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
        $sender_role = $is_adviser ? "adviser" : "panel member";
        $mail->Subject = "Feedback Approved - Thesis Routing System";
        
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
                <p>Your {$sender_role} <strong>{$sender_name}</strong> has <span style='color: green; font-weight: bold;'>APPROVED</span> all feedback on your thesis proposal.</p>
                <p><strong>Feedback Summary:</strong> {$feedback_summary}</p>
                <p>Please log in to the Thesis Routing System to review the approved feedback and proceed with your thesis.</p>
                <div style='margin-top: 30px; text-align: center;'>
                    <a href='{$login_url}' style='background-color: #4366b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login to Review</a>
                </div>
                <p style='margin-top: 10px; text-align: center;'>If the button above doesn't work, copy and paste this URL into your browser: <br><a href='{$login_url}'>{$login_url}</a></p>
                <p style='margin-top: 30px; font-size: 12px; color: #777; text-align: center;'>This is an automated message from the Thesis Routing System. Please do not reply to this email.</p>
            </div>
        ";
        $mail->AltBody = "Dear {$student_name}, Your {$sender_role} {$sender_name} has APPROVED all feedback on your thesis proposal. Feedback Summary: {$feedback_summary}. Please login at: {$login_url} to review the detailed feedback.";

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

// Check for route2 proposals where adviser has approved all feedback
$route2Query = "
    SELECT 
        r.student_id,
        r.adviser_id,
        r.route2_id,
        s.fullname AS student_name,
        s.email AS student_email,
        a.fullname AS adviser_name,
        COUNT(p.id) AS total_forms,
        SUM(CASE WHEN p.status = 'Approved' THEN 1 ELSE 0 END) AS approved_forms,
        GROUP_CONCAT(p.chapter ORDER BY p.date_submitted DESC SEPARATOR ', ') AS chapters
    FROM 
        route2proposal_files r
    JOIN 
        student s ON r.student_id = s.student_id
    JOIN 
        adviser a ON r.adviser_id = a.adviser_id
    JOIN 
        proposal_monitoring_form p ON r.route2_id = p.route2_id AND p.adviser_id = r.adviser_id
    GROUP BY 
        r.route2_id, r.adviser_id
    HAVING 
        total_forms > 0 AND total_forms = approved_forms
";

$route2Result = $conn->query($route2Query);

if ($route2Result && $route2Result->num_rows > 0) {
    while ($row = $route2Result->fetch_assoc()) {
        $student_email = $row['student_email'];
        $student_name = $row['student_name'];
        $adviser_name = $row['adviser_name'];
        $chapters = $row['chapters'];
        
        // Check if email should be sent
        if (!empty($student_email) && isValidEmail($student_email)) {
            $feedback_summary = "Approved feedback for chapters: " . $chapters;
            
            // Send email
            $emailSent = sendStudentNotificationEmail($student_email, $student_name, $adviser_name, $feedback_summary, true);
            
            echo "Processed Route 2 - Student: {$student_name}, Email Sent: " . ($emailSent ? "Yes" : "No") . "<br>";
        }
    }
}

// Check for route1 and route3 proposals where all adviser feedback is approved
foreach(['route1', 'route3'] as $route) {
    $routeQuery = "
        SELECT 
            r.student_id,
            r.{$route}_id AS route_id,
            s.fullname AS student_name,
            s.email AS student_email,
            COUNT(p.id) AS total_forms,
            SUM(CASE WHEN p.status = 'Approved' THEN 1 ELSE 0 END) AS approved_forms,
            GROUP_CONCAT(p.chapter ORDER BY p.date_submitted DESC SEPARATOR ', ') AS chapters,
            GROUP_CONCAT(DISTINCT p.adviser_name SEPARATOR ', ') AS adviser_names
        FROM 
            {$route}proposal_files r
        JOIN 
            student s ON r.student_id = s.student_id
        JOIN 
            proposal_monitoring_form p ON r.{$route}_id = p.{$route}_id AND p.adviser_id IS NOT NULL
        GROUP BY 
            r.{$route}_id
        HAVING 
            total_forms > 0 AND total_forms = approved_forms
    ";

    $routeResult = $conn->query($routeQuery);

    if ($routeResult && $routeResult->num_rows > 0) {
        while ($row = $routeResult->fetch_assoc()) {
            $student_email = $row['student_email'];
            $student_name = $row['student_name'];
            $adviser_names = $row['adviser_names'];
            $chapters = $row['chapters'];
            
            // Check if email should be sent
            if (!empty($student_email) && isValidEmail($student_email)) {
                $feedback_summary = "Approved feedback for chapters: " . $chapters;
                
                // Send email
                $emailSent = sendStudentNotificationEmail($student_email, $student_name, $adviser_names, $feedback_summary, true);
                
                echo "Processed " . ucfirst($route) . " - Student: {$student_name}, Email Sent: " . ($emailSent ? "Yes" : "No") . "<br>";
            }
        }
    }
}

echo "Email check completed.";
?> 