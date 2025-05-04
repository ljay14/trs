<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: ../../../logout.php");
    exit;
}

include '../../../connection.php';

$alertMessage = "";

// HANDLE REUPLOAD REQUEST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["docuRoute1"]) && isset($_POST['old_file_path'])) {
    $student_id = $_SESSION['student_id'];
    $oldFilePath = $_POST['old_file_path'];
    
    // Check if old file exists in database
    $stmt = $conn->prepare("SELECT route1_id FROM route1proposal_files WHERE student_id = ? AND docuRoute1 = ?");
    $stmt->bind_param("ss", $student_id, $oldFilePath);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $route1_id = $row['route1_id'];
        
        // Process the new file upload
        $fileTmpPath = $_FILES["docuRoute1"]["tmp_name"];
        $fileName = $_FILES["docuRoute1"]["name"];
        $uploadDir = "../../../uploads/";
        $newFilePath = $uploadDir . basename($fileName);
        
        $allowedTypes = [
            "application/pdf"
        ];
        
        if (in_array($_FILES["docuRoute1"]["type"], $allowedTypes)) {
            // Delete old file if it exists
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
            
            if (move_uploaded_file($fileTmpPath, $newFilePath)) {
                // Update the database with the new file path
                $updateStmt = $conn->prepare("UPDATE route1proposal_files SET docuRoute1 = ? WHERE route1_id = ?");
                $updateStmt->bind_param("si", $newFilePath, $route1_id);
                
                if ($updateStmt->execute()) {
                    $alertMessage = "File reuploaded successfully.";
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
    header("Location: route1.php");
    exit;
}

// HANDLE DELETE REQUEST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_file'])) {
    $student_id = $_SESSION['student_id'];
    $fileToDelete = $_POST['delete_file'];

    $stmt = $conn->prepare("SELECT docuRoute1 FROM route1proposal_files WHERE student_id = ? AND docuRoute1 = ?");
    $stmt->bind_param("ss", $student_id, $fileToDelete);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        if (file_exists($fileToDelete)) {
            unlink($fileToDelete); // Delete the file from folder
        }
        $deleteStmt = $conn->prepare("DELETE FROM route1proposal_files WHERE student_id = ? AND docuRoute1 = ?");
        $deleteStmt->bind_param("ss", $student_id, $fileToDelete);
        $deleteStmt->execute();
        $deleteStmt->close();
        $alertMessage = "File deleted successfully.";
    } else {
        $alertMessage = "File not found or you don't have permission.";
    }
    $stmt->close();

    $_SESSION['alert_message'] = $alertMessage;
    header("Location: route1.php");
    exit;
}

// HANDLE UPLOAD
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['csrf_token'], $_POST['csrf_token']) && $_SESSION['csrf_token'] === $_POST['csrf_token']) {
    $student_id = $_POST["student_id"];

    // Fetch the department, controlNo, fullname, group_number, title, and adviser from the student's account
    $stmt = $conn->prepare("SELECT department, controlNo, fullname, group_number, title, adviser, school_year FROM student WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $stmt->bind_result($department, $controlNo, $fullname, $group_number, $title, $adviser_name, $school_year);
    $stmt->fetch();
    $stmt->close();

    if (!$department) {
        echo "<script>alert('No account found with the provided ID number.'); window.history.back();</script>";
        exit;
    } else {
        // Find the adviser_id based on the adviser name from the adviser table
        $adviser_id = null;
        $stmt = $conn->prepare("SELECT adviser_id FROM adviser WHERE fullname = ?");
        $stmt->bind_param("s", $adviser_name);
        $stmt->execute();
        $stmt->bind_result($adviser_id);
        $stmt->fetch();
        $stmt->close();

        if (!$adviser_id) {
            echo "<script>alert('Adviser not found. Please check the adviser\'s name.'); window.history.back();</script>";
            exit;
        }

        if (isset($_FILES["docuRoute1"]) && $_FILES["docuRoute1"]["error"] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES["docuRoute1"]["tmp_name"];
            $fileName = $_FILES["docuRoute1"]["name"];
            $uploadDir = "../../../uploads/";
            $filePath = $uploadDir . basename($fileName);

            $allowedTypes = [
                "application/pdf"
            ];

            if (in_array($_FILES["docuRoute1"]["type"], $allowedTypes)) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Check if the student already uploaded a file
                $stmt = $conn->prepare("SELECT COUNT(*) FROM route1proposal_files WHERE student_id = ? AND department = ?");
                $stmt->bind_param("ss", $student_id, $department);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    echo "<script>alert('You can only upload one file.'); window.history.back();</script>";
                    exit;
                } elseif (move_uploaded_file($fileTmpPath, $filePath)) {
                    // Insert the new file info including adviser_id
                    $stmt = $conn->prepare("INSERT INTO route1proposal_files (student_id, docuRoute1, department, controlNo, fullname, group_number, title, adviser_id, school_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("sssssssis", $student_id, $filePath, $department, $controlNo, $fullname, $group_number, $title, $adviser_id, $school_year);
                        if ($stmt->execute()) {
                            echo "<script>alert('File uploaded successfully.'); window.location.href = 'route1.php';</script>";
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route 1 - Thesis Routing System</title>
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
    max-height: none; 
    height: auto;
    white-space: pre-wrap;
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
            <div class="homepage">
                <a href="../homepage.php">Home Page</a>
                
            </div>
            <div class="submit-button">
            <a href="#" id="submit-file-button">Submit File</a>
            </div>
                
            </div>
            <div class="user-info">
                <div class="routeNo" style="margin-right: 20px;">Proposal - Route 1</div>
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
                        docuRoute1, 
                        route1_id, 
                        controlNo, 
                        fullname, 
                        group_number,
                        title
                    FROM 
                        route1proposal_files
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
                        $filePath = htmlspecialchars($row['docuRoute1'], ENT_QUOTES);
                        $route1_id = htmlspecialchars($row['route1_id'], ENT_QUOTES);
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
                                    <button class='view-button' onclick=\"viewFile('$filePath', '$student_id', '$route1_id')\">View</button>
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
                    echo "<div class='welcome-card' style='background-color: white; border-radius: 10px; box-shadow: var(--shadow); padding: 2rem; text-align: center;'>
                            <h1 style='color: var(--primary); margin-bottom: 1rem;'>No Files Uploaded Yet</h1>
                            <p style='color: #666; line-height: 1.6; margin-bottom: 1.5rem;'>Click on 'Submit File' to upload your thesis documents.</p>
                          </div>";
                }

                $stmt->close();
                ?>
            </div>
        </div>
    </div>
    
    <form action="route1.php" method="POST" enctype="multipart/form-data" id="file-upload-form" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>">
        <input type="hidden" name="student_id" value="<?= htmlspecialchars($_SESSION['student_id']); ?>">
        <input type="file" name="docuRoute1" id="docuRoute1" accept=".pdf" required>
    </form>

    <!-- Form for reupload -->
    <form action="route1.php" method="POST" enctype="multipart/form-data" id="file-reupload-form" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="student_id" value="<?= htmlspecialchars($_SESSION['student_id']); ?>">
        <input type="hidden" name="old_file_path" id="old_file_path">
        <input type="file" name="docuRoute1" id="docuRoute1_reupload" accept=".pdf" required>
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

    <script>
        document.getElementById("submit-file-button").addEventListener("click", function(e) {
            e.preventDefault();
            document.querySelector("#docuRoute1").click();
        });
        
        document.querySelector("#docuRoute1").addEventListener("change", function() {
            document.querySelector("#file-upload-form").submit();
        });

        function viewFile(filePath, student_id, route1_id) {
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
                    <div><strong>Route Number</strong></div>
                </div>
                
                <!-- Container for submitted form data -->
                <div id="submittedFormsContainer" class="form-grid-container"></div>
                <div id="noFormsMessage" style="margin-top: 10px; color: gray;"></div>
            `;

            // Change the event listener for the print button to use an iframe within the modal
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
                                    grid-template-columns: repeat(8, 1fr);
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

            // Load form data dynamically using route1_id
            fetch(`get_all_forms.php?route1_id=${encodeURIComponent(route1_id)}`)
                .then(res => res.json())
                .then(data => {
                    console.log("Fetched forms:", data);
                    const rowsContainer = document.getElementById("submittedFormsContainer");

                    if (!Array.isArray(data) || data.length === 0) {
                        rowsContainer.innerHTML = `<div style="grid-column: span 7; text-align: center; padding: 1rem;">No routing form data available.</div>`;
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
                            <div>${row.routeNumber}</div>
                        `;
                    });
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
                form.action = "route1.php";

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
                document.getElementById("docuRoute1_reupload").click();
            }
        }
        
        document.getElementById("docuRoute1_reupload").addEventListener("change", function() {
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


