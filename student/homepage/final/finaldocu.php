<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Import PHPMailer classes at the top of the file
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../../logout.php");
    exit;
}

include '../../../connection.php';

// Function to validate email address
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Create function to send email notification to adviser
function sendAdviserNotificationEmail($adviser_email, $adviser_name, $fullname, $title) {
    try {
        // Validate email address first
        if (!isValidEmail($adviser_email)) {
            error_log("Invalid email address format: $adviser_email");
            return false;
        }
        
        // Check for Composer autoloader
        $autoloader_path = __DIR__ . '/../../../vendor/autoload.php';
        
        if (!file_exists($autoloader_path)) {
            error_log("PHPMailer autoloader not found. Please install PHPMailer via Composer.");
            return false;
        }
        
        // Include the autoloader
        require_once $autoloader_path;
        
        // Create instance of PHPMailer
        $mail = new PHPMailer(true);

        // Server settings
        $mail->SMTPDebug  = 0;  // Enable verbose debug output (0 for no output, 2 for verbose)
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
        $mail->addAddress($adviser_email, $adviser_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Final Defense Document (Final Document) Submitted for Review';
        
        // Get server URL dynamically
        $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        $server_port = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' ? ':' . $_SERVER['SERVER_PORT'] : '';
        $http_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $base_url = $http_protocol . '://' . $server_name . $server_port;
        
        $login_url = $base_url . '/TRS/adviser/';
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
                <h2 style='color: #4366b3; text-align: center;'>Thesis Routing System Notification</h2>
                <p>Dear <strong>{$adviser_name}</strong>,</p>
                <p>A new final defense document (Final Document) has been submitted and requires your review.</p>
                <p><strong>Student:</strong> {$fullname}</p>
                <p><strong>Title:</strong> {$title}</p>
                <p>Please log in to the Thesis Routing System to review this document.</p>
                <div style='margin-top: 30px; text-align: center;'>
                    <a href='{$login_url}' style='background-color: #4366b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login to Review</a>
                </div>
                <p style='margin-top: 10px; text-align: center;'>If the button above doesn't work, copy and paste this URL into your browser: <br><a href='{$login_url}'>{$login_url}</a></p>
                <p style='margin-top: 30px; font-size: 12px; color: #777; text-align: center;'>This is an automated message from the Thesis Routing System. Please do not reply to this email.</p>
            </div>
        ";
        $mail->AltBody = "Dear {$adviser_name}, A new final defense document (Final Document) has been submitted by {$fullname} with the title '{$title}' and requires your review. Please login at: {$login_url}";

        // Add additional headers that may help with deliverability
        $mail->addCustomHeader('X-Mailer', 'Thesis Routing System');
        $mail->addCustomHeader('X-Priority', '3');

        $mail->send();
        error_log("Email sent successfully to: $adviser_email using PHPMailer");
        return true;
    } catch (Exception $e) {
        $errorMsg = "Email could not be sent to: $adviser_email. ";
        
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
        
        error_log($errorMsg);
        return false;
    }
}

$alertMessage = "";

// HANDLE DELETE REQUEST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_file'])) {
    $student_id = $_SESSION['student_id'];
    $fileToDelete = $_POST['delete_file'];

    $stmt = $conn->prepare("SELECT finaldocu FROM finaldocufinal_files WHERE student_id = ? AND finaldocu = ?");
    $stmt->bind_param("ss", $student_id, $fileToDelete);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        if (file_exists($fileToDelete)) {
            unlink($fileToDelete); // Delete the file from folder
        }
        $deleteStmt = $conn->prepare("DELETE FROM finaldocufinal_files WHERE student_id = ? AND finaldocu = ?");
        $deleteStmt->bind_param("ss", $student_id, $fileToDelete);
        $deleteStmt->execute();
        $deleteStmt->close();
        $alertMessage = "File deleted successfully.";
    } else {
        $alertMessage = "File not found or you don't have permission.";
    }
    $stmt->close();

    $_SESSION['alert_message'] = $alertMessage;
    header("Location: finaldocu.php");
    exit;
}

// HANDLE REUPLOAD REQUEST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["docuFinal"]) && isset($_POST['old_file_path'])) {
    $student_id = $_SESSION['student_id'];
    $oldFilePath = $_POST['old_file_path'];
    
    // Check if old file exists in database
    $stmt = $conn->prepare("SELECT f.finaldocu_id, f.title, f.fullname, f.adviser_id 
                           FROM finaldocu_files f 
                           WHERE f.student_id = ? AND f.docuFinal = ?");
    $stmt->bind_param("ss", $student_id, $oldFilePath);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $finaldocu_id = $row['finaldocu_id'];
        $title = $row['title'];
        $fullname = $row['fullname'];
        $adviser_id = $row['adviser_id'];
        
        // Get adviser name and email from student table
        $stmt = $conn->prepare("SELECT adviser, adviser_email FROM student WHERE student_id = ?");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $adviserResult = $stmt->get_result();
        if ($adviserResult->num_rows > 0) {
            $adviserRow = $adviserResult->fetch_assoc();
            $adviser_name = $adviserRow['adviser'];
            $adviser_email = $adviserRow['adviser_email'];
        } else {
            $adviser_name = "";
            $adviser_email = "";
        }
        $stmt->close();
        
        // Process the new file upload
        $fileTmpPath = $_FILES["docuFinal"]["tmp_name"];
        $fileName = $_FILES["docuFinal"]["name"];
        $uploadDir = "../../../uploads/";
        $newFilePath = $uploadDir . basename($fileName);
        
        $allowedTypes = [
            "application/pdf"
        ];
        
        if (in_array($_FILES["docuFinal"]["type"], $allowedTypes)) {
            // Delete old file if it exists
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
            
            if (move_uploaded_file($fileTmpPath, $newFilePath)) {
                // Update the database with the new file path
                $updateStmt = $conn->prepare("UPDATE finaldocu_files SET docuFinal = ? WHERE finaldocu_id = ?");
                $updateStmt->bind_param("si", $newFilePath, $finaldocu_id);
                
                if ($updateStmt->execute()) {
                    // Send email notification about reupload
                    if (!empty($adviser_email)) {
                        if (isValidEmail($adviser_email)) {
                            $emailSent = sendAdviserNotificationEmail($adviser_email, $adviser_name, $fullname, $title);
                            if ($emailSent) {
                                $alertMessage = "File reuploaded successfully and notification email sent to adviser.";
                                error_log("Success: Notification email sent to adviser ($adviser_email) for file reupload.");
                            } else {
                                $alertMessage = "File reuploaded successfully but failed to send notification email to adviser.";
                                error_log("Error: Failed to send notification email to adviser ($adviser_email) for file reupload.");
                            }
                        } else {
                            $alertMessage = "File reuploaded successfully but adviser email address is invalid.";
                            error_log("Error: Invalid adviser email address ($adviser_email) for file reupload.");
                        }
                    } else {
                        $alertMessage = "File reuploaded successfully. No adviser email available for notification.";
                        error_log("Warning: No adviser email available for notification during file reupload.");
                    }
                } else {
                    $alertMessage = "Error updating database: " . $updateStmt->error;
                }
                $updateStmt->close();
            } else {
                $alertMessage = "Error moving the uploaded file.";
            }
        } else {
            $alertMessage = "Invalid file type. Only PDF files are allowed.";
        }
    } else {
        $alertMessage = "Original file not found in database.";
    }
    $stmt->close();
    
    $_SESSION['alert_message'] = $alertMessage;
    header("Location: finaldocu.php");
    exit;
}

// HANDLE UPLOAD
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['csrf_token'], $_POST['csrf_token']) && $_SESSION['csrf_token'] === $_POST['csrf_token']) {
    $student_id = $_POST["student_id"];

    // Fetch the department from the student's account
    $stmt = $conn->prepare("SELECT department, controlNo, fullname, group_number, title, adviser, adviser_email, school_year FROM student WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $stmt->bind_result($department, $controlNo, $fullname, $group_number, $title, $adviser_name, $adviser_email, $school_year);
    $stmt->fetch();
    $stmt->close();

    if (!$department) {
        echo "<script>alert('No account found with the provided ID number.'); window.history.back();</script>";
        exit;
    } else {
        // Check Route 1 approval status by checking the status for the panels and adviser
        $stmt = $conn->prepare("SELECT status, route3_id FROM final_monitoring_form WHERE student_id = ?");
        if (!$stmt) {
            die("Error preparing statement: " . $conn->error);
        }
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $stmt->bind_result($status, $route3_id);

        // Check
        $allowUpload = true;
        while ($stmt->fetch()) {
            if ($status != 'Approved') {
                if (empty($route3_id)) {
                    // Meaning it's NOT Route 3, still pending => NOT allowed
                    $allowUpload = false;
                    break;
                }
            }
        }
        $stmt->close();

        if (!$allowUpload) {
            echo "<script>alert('You cannot proceed to Route 3 until all panels and adviser approve your Route 1 and Route 2 submissions.'); window.history.back();</script>";
            exit;
        }
        // Proceed with file upload if Route 1 is approved
        if (isset($_FILES["finaldocu"]) && $_FILES["finaldocu"]["error"] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES["finaldocu"]["tmp_name"];
            $fileName = $_FILES["finaldocu"]["name"];
            $uploadDir = "../../../uploads/";
            $filePath = $uploadDir . basename($fileName);

            $allowedTypes = [
                "application/pdf"
            ];

            if (in_array($_FILES["finaldocu"]["type"], $allowedTypes)) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Check if the student already uploaded for Route 3
                $stmt = $conn->prepare("SELECT COUNT(*) FROM finaldocufinal_files WHERE student_id = ? AND department = ?");
                if (!$stmt) {
                    die("Error preparing statement: " . $conn->error); // Output error if statement preparation fails
                }
                $stmt->bind_param("ss", $student_id, $department);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    echo "<script>alert('You can only upload one file for Route 3.'); window.history.back();</script>";
                    exit;
                } elseif (move_uploaded_file($fileTmpPath, $filePath)) {
                    // Fetch panel and adviser IDs from Route 1
                    $panelStmt = $conn->prepare("SELECT panel1_id, panel2_id, panel3_id, panel4_id, panel5_id, adviser_id FROM route1final_files WHERE student_id = ?");
                    if (!$panelStmt) {
                        die("Error preparing statement: " . $conn->error); // Output error if statement preparation fails
                    }
                    $panelStmt->bind_param("s", $student_id);
                    $panelStmt->execute();
                    $panelStmt->bind_result($panel1_id, $panel2_id, $panel3_id, $panel4_id, $panel5_id, $adviser_id);
                    $panelStmt->fetch();
                    $panelStmt->close();

                    if (!isset($panel1_id)) {
                        echo "<script>alert('Route 1 and Route 2 information not found. Please complete Route 1 and Route 2 first.'); window.history.back();</script>";
                        exit;
                    }

                    // Get current date/time
                    $date_submitted = date("Y-m-d H:i:s");

                    // Insert into Route 3 with date_submitted
                    $stmt = $conn->prepare("INSERT INTO finaldocufinal_files (student_id, finaldocu, department, panel1_id, panel2_id, panel3_id, panel4_id, panel5_id, adviser_id, date_submitted, controlNo, fullname, group_number, title, school_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("sssiiiiiissssss", $student_id, $filePath, $department, $panel1_id, $panel2_id, $panel3_id, $panel4_id, $panel5_id, $adviser_id, $date_submitted, $controlNo, $fullname, $group_number, $title, $school_year);
                        if ($stmt->execute()) {
                            // Send email notification to adviser if email is available
                            if ($adviser_email) {
                                if (isValidEmail($adviser_email)) {
                                    $emailSent = sendAdviserNotificationEmail($adviser_email, $adviser_name, $fullname, $title);
                                    if ($emailSent) {
                                        echo "<script>alert('File uploaded successfully and notification email sent to adviser.'); window.location.href = 'finaldocu.php';</script>";
                                        error_log("Success: Notification email sent to adviser ($adviser_email) for Final Document file upload.");
                                    } else {
                                        echo "<script>alert('File uploaded successfully but failed to send notification email to adviser.'); window.location.href = 'finaldocu.php';</script>";
                                        error_log("Error: Failed to send notification email to adviser ($adviser_email) for Final Document file upload.");
                                    }
                                } else {
                                    echo "<script>alert('File uploaded successfully but adviser email address is invalid.'); window.location.href = 'finaldocu.php';</script>";
                                    error_log("Error: Invalid adviser email address ($adviser_email) for Final Document file upload.");
                                }
                            } else {
                                echo "<script>alert('File uploaded successfully. No adviser email available for notification.'); window.location.href = 'finaldocu.php';</script>";
                                error_log("Warning: No adviser email available for notification during Final Document file upload.");
                            }
                        } else {
                            echo "<script>alert('Error saving record: " . $stmt->error . "'); window.history.back();</script>";
                        }
                        $stmt->close();
                    }
                } else {
                    echo "<script>alert('Error moving the file.'); window.history.back();</script>";
                }
            } else {
                echo "<script>alert('Invalid file type. Only PDF files are allowed.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Error uploading file.'); window.history.back();</script>";
        }
    }
}
$student_id = $_SESSION['student_id'];

$sql = "SELECT fullname, adviser, group_members FROM student WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();

    $fullname = $student['fullname'];
    $adviser = $student['adviser'];
    $groupMembers = $student['group_members'];

    $groupMembersRaw = $student['group_members'];

    // Convert JSON string to PHP array
    $groupMembersArray = json_decode($groupMembersRaw, true);

    // Check if decoding was successful
    if (json_last_error() === JSON_ERROR_NONE && is_array($groupMembersArray)) {
        $allStudentsArray = array_merge([$fullname], $groupMembersArray); // Combine arrays
    } else {
        // Fallback if decoding fails (treat as plain string)
        $allStudentsArray = array_merge([$fullname], explode(',', $groupMembersRaw));
    }
    // Join names into one comma-separated string
    $allStudents = implode(', ', $allStudentsArray);

    // Combine main student name + group members
    // Example: Pass this to your PDF generator

} else {
    echo "No student found.";
}

// Get the student's department
$department = "";
$stmt = $conn->prepare("SELECT department FROM student WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$stmt->bind_result($department);
$stmt->fetch();
$stmt->close();

// Store department info in a JS variable for later use
$is_computing_student = (strpos(strtolower($department), 'computing') !== false || 
                         strpos(strtolower($department), 'computer') !== false ||
                         strpos(strtolower($department), 'information') !== false);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Document - Thesis Routing System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <link rel="stylesheet" href="styles.css">

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
    grid-template-columns: repeat(9, 1fr);
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
.form-grid-container > div:nth-child(9n + 3) {
    text-align: left;
    justify-content: flex-start;
    overflow-y: visible;
    max-height: none; /* Remove height limit */
    height: auto; /* Allow height to adjust to content */
    white-space: pre-wrap; /* Preserve line breaks and spacing */
}

/* Style for feedback cells in JavaScript output */
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

.close-button {
    position: absolute;
    top: -5px;
    right: 2px;
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
</head>
<body>
    <?php
    if (isset($_SESSION['alert_message'])) {
        echo "<script>alert('" . addslashes($_SESSION['alert_message']) . "');</script>";
        unset($_SESSION['alert_message']); // Clear it after showing
    }
    ?>
    <div class="container">
        <header class="header">
            <div class="logo-container">
                <img src="../../../assets/logo.png" alt="SMCC Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>
        <div class="top-bar">
        <div class="navigation">
                <a href="../homepage.php">Home Page</a>
                <div class="submit-button">
                <a href="#" id="submit-file-button">Submit File</a>
                </div>
                
            </div>
            <div class="user-info">

                <div class="routeNo" style="margin-right: 20px;">Final - Final Document</div>
                <div class="vl"></div>
                <span class="role">Student:</span>
                <span class="user-name"><?php echo isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Guest'; ?></span>
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
                <?php
                $student_id = $_SESSION['student_id'];

                $stmt = $conn->prepare("
                    SELECT 
                        finaldocu, 
                        finaldocu_id, 
                        controlNo, 
                        fullname, 
                        group_number,
                        title
                    FROM 
                        finaldocufinal_files 
                    WHERE 
                        student_id = ?
                ");

                $stmt->bind_param("s", $student_id);
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
                                <th class='action-label'>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                    ";

                    while ($row = $result->fetch_assoc()) {
                        $filePath = htmlspecialchars($row['finaldocu'], ENT_QUOTES);
                        $finaldocu_id = htmlspecialchars($row['finaldocu_id'], ENT_QUOTES);
                        $controlNo = htmlspecialchars($row['controlNo'], ENT_QUOTES);
                        $fullName = htmlspecialchars($row['fullname'], ENT_QUOTES);
                        $groupNo = htmlspecialchars($row['group_number'], ENT_QUOTES);
                        $title = htmlspecialchars($row['title'], ENT_QUOTES);

                        echo "
                        <tr>
                            <td>$controlNo</td>
                            <td>$fullName</td>
                            <td>$groupNo</td>
                            <td>$title</td>
                            <td>
                                <div class='action-buttons'>
                                    <button class='view-button' onclick=\"viewFile('$filePath', '$student_id', '$finaldocu_id')\">View</button>
                                    <button class='delete-button' onclick=\"confirmReupload('$filePath')\">Reupload</button>
                                </div>
                            </td>
                        </tr>
                        ";
                    }

                    echo "
                        </tbody>
                    </table>
                    ";
                } else {
                    echo "<div class='welcome-card'>
                            <h1>No Files Uploaded Yet</h1>
                            <p>Click on 'Submit File' to upload your thesis documents.</p>
                          </div>";
                }

                $stmt->close();
                ?>
            </div>
        </div>
    </div>
    
    <form action="finaldocu.php" method="POST" enctype="multipart/form-data" id="file-upload-form" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>">
        <input type="hidden" name="student_id" value="<?= htmlspecialchars($_SESSION['student_id']); ?>">
        <input type="file" name="finaldocu" id="finaldocu" accept=".pdf" required>
    </form>

    <!-- Form for reupload -->
    <form action="finaldocu.php" method="POST" enctype="multipart/form-data" id="file-reupload-form" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="student_id" value="<?= htmlspecialchars($_SESSION['student_id']); ?>">
        <input type="hidden" name="old_file_path" id="old_file_path">
        <input type="file" name="finaldocu" id="finaldocu_reupload" accept=".pdf" required>
    </form>

    <div id="fileModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <div class="modal-layout">
                <div id="fileModalContent" class="file-preview-section"></div>
                <div id="routingForm" class="routing-form-section"></div>
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
</script>



<script>
document.getElementById("submit-file-button").addEventListener("click", function(e) {
            e.preventDefault();
            document.querySelector("#finaldocu").click();
        });
        
        document.querySelector("#finaldocu").addEventListener("change", function() {
            document.querySelector("#file-upload-form").submit();
        });

        function viewFile(filePath, student_id, finaldocu_id) {
            const modal = document.getElementById("fileModal");
            const contentArea = document.getElementById("fileModalContent");
            const routingFormArea = document.getElementById("routingForm");

            modal.style.display = "flex";
            contentArea.innerHTML = "<div style='display: flex; justify-content: center; align-items: center; height: 100%;'><div style='text-align: center;'><div class='spinner' style='border: 4px solid rgba(0, 0, 0, 0.1); width: 40px; height: 40px; border-radius: 50%; border-left-color: var(--accent); animation: spin 1s linear infinite; margin: 0 auto;'></div><p style='margin-top: 10px;'>Loading file...</p></div></div>";
            
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
            fetch(`finaldocu_get_all_forms.php?student_id=${encodeURIComponent(student_id)}`)
                .then(res => res.json())
                .then(data => {
                    console.log("Fetched forms:", data);
                    const rowsContainer = document.getElementById("submittedFormsContainer");
                    rowsContainer.innerHTML = ""; // Important: Clear previous data

                    if (!Array.isArray(data) || data.length === 0) {
                        rowsContainer.innerHTML = `<div style="grid-column: span 9; text-align: center; padding: 1rem;">No routing form data available.</div>`;
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
                            <div class="feedback-cell">${row.feedback}</div>
                            <div>${row.paragraph_number}</div>
                            <div>${row.page_number}</div>
                            <div>${submittedBy}</div>
                            <div>${row.date_released}</div>
                            <div>${row.status}</div>
                            <div>${row.routeNumber}</div>
                        `;
                    });
                    
                    // Add event listener for print button
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
                                        
                                        /* Specific style for the feedback cell (3rd column) */
                                        .grid-container > div:nth-child(9n + 3) {
                                            text-align: left;
                                            justify-content: flex-start;
                                            overflow-y: visible;
                                            max-height: none;
                                            height: auto;
                                            white-space: pre-wrap;
                                            word-break: break-word;
                                            overflow-wrap: break-word;
                                        }
                                        
                                        /* Support for feedback-cell class */
                                        .feedback-cell {
                                            text-align: left !important;
                                            justify-content: flex-start !important;
                                            white-space: pre-wrap !important;
                                            word-break: break-word !important;
                                            overflow-wrap: break-word !important;
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
                })
                .catch(err => {
                    console.error("Error loading form data:", err);
                    document.getElementById("noFormsMessage").innerHTML = "Error loading form data. Please try again.";
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
                        contentArea.innerHTML = `<div class="file-content" style="padding: 2rem;">${result.value}</div>`;
                    })
                    .catch((err) => {
                        console.error("Error viewing file:", err);
                        contentArea.innerHTML = "<div style='text-align: center; padding: 2rem;'><p style='color: #dc3545;'>Failed to display the file. Please try again later.</p></div>";
                    });
            } else {
                contentArea.innerHTML = "<div style='text-align: center; padding: 2rem;'><p style='color: #dc3545;'>Unsupported file type.</p></div>";
            }
        }

        function closeModal() {
            const modal = document.getElementById("fileModal");
            modal.style.display = "none";
            document.getElementById("fileModalContent").innerHTML = '';
            document.getElementById("routingForm").innerHTML = '';
        }

        function confirmDelete(filePath) {
            if (confirm("Are you sure you want to delete this file?")) {
                const form = document.createElement("form");
                form.method = "POST";
                form.action = "finaldocu.php";

                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "delete_file";
                input.value = filePath;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function confirmReupload(filePath) {
            if (confirm("Do you want to reupload this file? The current file will be replaced.")) {
                document.getElementById("old_file_path").value = filePath;
                document.getElementById("finaldocu_reupload").click();
            }
        }
        
        document.getElementById("finaldocu_reupload").addEventListener("change", function() {
            document.getElementById("file-reupload-form").submit();
        });
        
        // For modal animation
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        
        // Style the spinner animation
        document.head.insertAdjacentHTML('beforeend', `
            <style>
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            </style>
        `);
</script>

