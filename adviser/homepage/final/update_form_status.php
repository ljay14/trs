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
error_log("final/update_form_status.php called - REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION: " . print_r($_SESSION, true));

// Read JSON from request body
$data = json_decode(file_get_contents("php://input"), true);

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
        $mail->Subject = "Final Defense Form Approved - Thesis Routing System";
        
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
                <p>Your adviser <strong>{$adviser_name}</strong> has <span style='color: green; font-weight: bold;'>APPROVED</span> your final defense submission.</p>
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
        $mail->AltBody = "Dear {$student_name}, Your adviser {$adviser_name} has APPROVED your final defense submission for Chapter: {$form_details['chapter']}, Paragraph: {$form_details['paragraph_number']}, Page: {$form_details['page_number']}, Route: {$form_details['routeNumber']}. Please login at: {$login_url} to review.";

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
        $mail->Subject = "All Final Defense Feedback Approved - Thesis Routing System";
        
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
                <p>Congratulations! Your adviser <strong>{$adviser_name}</strong> has <span style='color: green; font-weight: bold;'>APPROVED ALL</span> feedback for your final defense!</p>
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
        $mail->AltBody = "Dear {$student_name}, Congratulations! Your adviser {$adviser_name} has APPROVED ALL feedback for your final defense. Summary: {$feedback_summary}, Route: {$route_number}. Please login at: {$login_url} to view all approved feedback and continue with your thesis progress.";

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

if (isset($data['id']) && isset($data['status']) && isset($_SESSION['adviser_id'])) {
    $id = $data['id']; // form id
    $status = $data['status'];
    $adviser_id = $_SESSION['adviser_id'];

    // Check if the adviser is authorized for this form
    $checkQuery = "SELECT adviser_id FROM final_monitoring_form WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['adviser_id'] != $adviser_id) {
            echo json_encode(["success" => false, "message" => "You are not allowed to approve the status because you are not the assigned adviser."]);
        } else {
            // Authorized - proceed with update
            $query = "UPDATE final_monitoring_form SET status = ? WHERE id = ? AND adviser_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $status, $id, $adviser_id);
            
            if ($stmt->execute()) {
                // If status was changed to "Approved", send email notification
                if ($status === 'Approved') {
                    // Get form details and student information
                    $getFormStmt = $conn->prepare("
                        SELECT 
                            p.student_id, 
                            p.chapter, 
                            p.paragraph_number, 
                            p.page_number, 
                            p.adviser_name,
                            p.routeNumber,
                            p.route1_id,
                            p.route2_id,
                            p.route3_id,
                            p.finaldocu_id,
                            p.adviser_id,
                            s.fullname AS student_name,
                            s.email AS student_email
                        FROM 
                            final_monitoring_form p
                        JOIN 
                            student s ON p.student_id = s.student_id
                        WHERE 
                            p.id = ?
                    ");
                    
                    $getFormStmt->bind_param("i", $id);
                    $getFormStmt->execute();
                    $result = $getFormStmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $formData = $result->fetch_assoc();
                        $student_email = $formData['student_email'];
                        $student_name = $formData['student_name'];
                        $adviser_name = $formData['adviser_name'];
                        $route1_id = $formData['route1_id'];
                        $route2_id = $formData['route2_id'];
                        $route3_id = $formData['route3_id'];
                        $finaldocu_id = $formData['finaldocu_id'];
                        
                        $form_details = [
                            'chapter' => $formData['chapter'],
                            'paragraph_number' => $formData['paragraph_number'],
                            'page_number' => $formData['page_number'],
                            'routeNumber' => $formData['routeNumber']
                        ];
                        
                        // Always send individual approval notification first
                        $emailSent = false;
                        if (isValidEmail($student_email)) {
                            $emailSent = sendApprovalNotificationEmail($student_email, $student_name, $adviser_name, $form_details);
                            if ($emailSent) {
                                error_log("Individual approval email sent successfully to student: $student_email");
                            } else {
                                error_log("Failed to send individual approval email to student: $student_email");
                            }
                        } else {
                            error_log("Invalid student email: $student_email");
                        }
                        
                        // Check if all adviser feedback for the relevant route is approved
                        $allApproved = false;
                        $debugInfo = [];
                        
                        if ($route1_id) {
                            // Check status of all route1 forms by this adviser
                            $checkStmt = $conn->prepare("
                                SELECT 
                                    COUNT(*) as total, 
                                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                                    SUM(CASE WHEN status != 'Approved' THEN 1 ELSE 0 END) as not_approved
                                FROM final_monitoring_form 
                                WHERE route1_id = ? AND adviser_id = ?
                            ");
                            $checkStmt->bind_param("is", $route1_id, $adviser_id);
                            $checkStmt->execute();
                            $checkResult = $checkStmt->get_result();
                            $checkData = $checkResult->fetch_assoc();
                            
                            if ($checkData['total'] > 0 && $checkData['not_approved'] == 0) {
                                $allApproved = true;
                                error_log("All forms for route1_id $route1_id by adviser $adviser_id are approved");
                                
                                // Create a summary message for the email
                                $feedbackSummary = "All adviser feedback for Route 1 has been approved.";
                                
                                // Send notification email about all approvals
                                if (isValidEmail($student_email)) {
                                    $emailSent = sendAllApprovedNotification($student_email, $student_name, $adviser_name, $feedbackSummary, "Route 1");
                                    
                                    if ($emailSent) {
                                        error_log("All approvals notification email sent to student: $student_email for Route 1");
                                    } else {
                                        error_log("Failed to send all approvals notification email to student: $student_email for Route 1");
                                    }
                                }
                            }
                        } elseif ($route2_id) {
                            // Check status of all route2 forms by this adviser
                            $checkStmt = $conn->prepare("
                                SELECT 
                                    COUNT(*) as total, 
                                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                                    SUM(CASE WHEN status != 'Approved' THEN 1 ELSE 0 END) as not_approved
                                FROM final_monitoring_form 
                                WHERE route2_id = ? AND adviser_id = ?
                            ");
                            $checkStmt->bind_param("is", $route2_id, $adviser_id);
                            $checkStmt->execute();
                            $checkResult = $checkStmt->get_result();
                            $checkData = $checkResult->fetch_assoc();
                            
                            if ($checkData['total'] > 0 && $checkData['not_approved'] == 0) {
                                $allApproved = true;
                                error_log("All forms for route2_id $route2_id by adviser $adviser_id are approved");
                                
                                // Create a summary message for the email
                                $feedbackSummary = "All adviser feedback for Route 2 has been approved.";
                                
                                // Send notification email about all approvals
                                if (isValidEmail($student_email)) {
                                    $emailSent = sendAllApprovedNotification($student_email, $student_name, $adviser_name, $feedbackSummary, "Route 2");
                                    
                                    if ($emailSent) {
                                        error_log("All approvals notification email sent to student: $student_email for Route 2");
                                    } else {
                                        error_log("Failed to send all approvals notification email to student: $student_email for Route 2");
                                    }
                                }
                            }
                        } elseif ($route3_id) {
                            // Check status of all route3 forms by this adviser
                            $checkStmt = $conn->prepare("
                                SELECT 
                                    COUNT(*) as total, 
                                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                                    SUM(CASE WHEN status != 'Approved' THEN 1 ELSE 0 END) as not_approved
                                FROM final_monitoring_form 
                                WHERE route3_id = ? AND adviser_id = ?
                            ");
                            $checkStmt->bind_param("is", $route3_id, $adviser_id);
                            $checkStmt->execute();
                            $checkResult = $checkStmt->get_result();
                            $checkData = $checkResult->fetch_assoc();
                            
                            if ($checkData['total'] > 0 && $checkData['not_approved'] == 0) {
                                $allApproved = true;
                                error_log("All forms for route3_id $route3_id by adviser $adviser_id are approved");
                                
                                // Create a summary message for the email
                                $feedbackSummary = "All adviser feedback for Route 3 has been approved.";
                                
                                // Send notification email about all approvals
                                if (isValidEmail($student_email)) {
                                    $emailSent = sendAllApprovedNotification($student_email, $student_name, $adviser_name, $feedbackSummary, "Route 3");
                                    
                                    if ($emailSent) {
                                        error_log("All approvals notification email sent to student: $student_email for Route 3");
                                    } else {
                                        error_log("Failed to send all approvals notification email to student: $student_email for Route 3");
                                    }
                                }
                            }
                        } elseif ($finaldocu_id) {
                            // Check status of all finaldocu forms by this adviser
                            $checkStmt = $conn->prepare("
                                SELECT 
                                    COUNT(*) as total, 
                                    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                                    SUM(CASE WHEN status != 'Approved' THEN 1 ELSE 0 END) as not_approved
                                FROM final_monitoring_form 
                                WHERE finaldocu_id = ? AND adviser_id = ?
                            ");
                            $checkStmt->bind_param("is", $finaldocu_id, $adviser_id);
                            $checkStmt->execute();
                            $checkResult = $checkStmt->get_result();
                            $checkData = $checkResult->fetch_assoc();
                            
                            if ($checkData['total'] > 0 && $checkData['not_approved'] == 0) {
                                $allApproved = true;
                                error_log("All forms for finaldocu_id $finaldocu_id by adviser $adviser_id are approved");
                                
                                // Create a summary message for the email
                                $feedbackSummary = "All adviser feedback for Final Document has been approved.";
                                
                                // Send notification email about all approvals
                                if (isValidEmail($student_email)) {
                                    $emailSent = sendAllApprovedNotification($student_email, $student_name, $adviser_name, $feedbackSummary, "Final Document");
                                    
                                    if ($emailSent) {
                                        error_log("All approvals notification email sent to student: $student_email for Final Document");
                                    } else {
                                        error_log("Failed to send all approvals notification email to student: $student_email for Final Document");
                                    }
                                }
                            }
                        }
                        
                        // Always return success with email status
                        echo json_encode([
                            "success" => true, 
                            "message" => "Status updated successfully for form ID $id.", 
                            "email_sent" => $emailSent,
                            "all_approved" => $allApproved
                        ]);
                        exit;
                    } else {
                        echo json_encode(["success" => true, "message" => "Status updated successfully but could not retrieve form details for notification."]);
                        exit;
                    }
                    
                    $getFormStmt->close();
                } else {
                    echo json_encode(["success" => true, "message" => "Status updated successfully."]);
                    exit;
                }
            } else {
                echo json_encode(["success" => false, "message" => "Failed to update status: " . $stmt->error]);
                exit;
            }
            $stmt->close();
        }
    } else {
        echo json_encode(["success" => false, "message" => "Form not found."]);
        exit;
    }

    $checkStmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Missing required data."]);
}
?>
