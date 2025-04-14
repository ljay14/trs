<?php
session_start();

if (!isset($_SESSION['adviser_id'])) {
    header("Location: ../../../logout.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$selectedDepartment = $_POST['department'] ?? '';
$adviser_id = $_SESSION['adviser_id'];
$fullname = $_SESSION['fullname'] ?? 'Adviser';

// Query to get student id and route3_id based on adviser_id
$stmt = $conn->prepare("SELECT student_id, finaldocu_id FROM finaldocuproposal_files WHERE adviser_id = ?");
if ($stmt === false) {
    die("Error preparing the query: " . $conn->error); // This will show the MySQL error
}

$stmt->bind_param("s", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the student id and route3_id
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_id = $row['student_id'];
    $finaldocu_id = $row['finaldocu_id']; // Now you have the route3_id
} else {
    // Handle case if no student is found (optional)
    $student_id = null;
    $finaldocu_id = null;
}


?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Thesis Routing System</title>
    <link rel="stylesheet" href="adstyless.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <style>
/* Modal background */
/* Modal overlay */
.modal {
    position: fixed;
    z-index: 999;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.6);
    display: none;
    align-items: center;
    justify-content: center;
}

/* Modal content container */
.modal-content {
    width: 90vw;
    height: 90vh;
    background-color: #fff;
    border-radius: 8px;
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* File display area */
#fileModalContent {
    flex: 1;
    width: 100%;
    height: 100%;
}

/* Embedded PDF/DOCX content */
#fileModalContent iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* DOCX content styling */
#fileModalContent .file-content {
    width: 100%;
    height: 100%;
    overflow-y: auto;
    padding: 10px;
    box-sizing: border-box;
}

/* Close button */
.close-button {
    position: absolute;
    top: 10px;
    right: 20px;
    font-size: 28px;
    cursor: pointer;
    color: #fff;
    background-color: rgba(0, 0, 0, 0.6);
    border: none;
    padding: 4px;
    border-radius: 50%;
    z-index: 1000;
}

    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <div class="logo-container">
                <img src="../../../assets/logo.png" alt="Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>

        <div class="top-bar">
            <div class="homepage">
                <a href="../homepage.php">Home Page</a>
            </div>
            <div class="user-info">
                <div class="vl"></div>
                <span class="role">Adviser:</span>
                <span class="user-name"><?= htmlspecialchars($fullname) ?></span>
            </div>
        </div>

        <div class="main-content">
            <nav class="sidebar">
                <div class="menu">
                    <div class="menu-section">
                        <div class="menu-title">Title Proposal</div>
                        <ul>
                            <li><a href="../titleproposal/route1.php">Route 1</a></li>
                            <li><a href="../titleproposal/route2.php">Route 2</a></li>
                            <li><a href="../titleproposal/route3.php">Route 3</a></li>
                            <li><a href="../titleproposal/finaldocu.php">Final Document</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Final</div>
                        <ul>
                            <li><a href="../final/route1.php">Route 1</a></li>
                            <li><a href="../final/route2.php">Route 2</a></li>
                            <li><a href="../final/route3.php">Route 3</a></li>
                            <li><a href="../final/finaldocu.php">Final Document</a></li>
                        </ul>
                    </div>
                </div>
                <div class="logout">
                    <a href="../../../logout.php">Logout</a>
                </div>
            </nav>
            <div class="content" id="content-area">
                <?php
                $stmt = $conn->prepare("SELECT finaldocu, student_id, finaldocu_id FROM finaldocuproposal_files WHERE adviser_id = ?");
                $stmt->bind_param("s", $adviser_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $filePath = htmlspecialchars($row['finaldocu']);
                        $fileName = basename($filePath);
                        $student_id = htmlspecialchars($row['student_id']);
                        $finaldocu_id = htmlspecialchars($row['finaldocu_id']);
                
                        echo "
                            <div class='file-preview'>
                                <div class='file-name'>{$fileName}</div>
                                <button class='view-button' onclick=\"viewFile('{$filePath}', '{$student_id}', '{$finaldocu_id}')\">View</button>
                            </div>
                        ";
                    }
                }
                 else {
                    echo "<p>No files uploaded yet.</p>";
                }

                $stmt->close();
                ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="fileModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeModal()">&times;</span>
        <div id="fileModalContent"></div>
    </div>
</div>
</body>
</html>
<script>
    const panelName = <?= json_encode($fullname) ?>;

    function viewFile(filePath, finaldocu, student_id) {
    const modal = document.getElementById("fileModal");
    const contentArea = document.getElementById("fileModalContent");
    const extension = filePath.split('.').pop().toLowerCase();

    modal.style.display = "flex";
    contentArea.innerHTML = "Loading file...";

    if (extension === "pdf") {
        contentArea.innerHTML = `
            <iframe src="${filePath}" allowfullscreen></iframe>
        `;
    } else if (extension === "docx") {
        fetch(filePath)
            .then(res => res.arrayBuffer())
            .then(buffer => mammoth.convertToHtml({ arrayBuffer: buffer }))
            .then(result => {
                contentArea.innerHTML = `<div class="file-content">${result.value}</div>`;
            })
            .catch(() => contentArea.innerHTML = "Error loading file.");
    } else {
        contentArea.innerHTML = "Unsupported file type.";
    }
}


    function closeModal() {
        document.getElementById("fileModal").style.display = "none";
    }
</script>