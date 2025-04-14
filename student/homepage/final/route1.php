<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['student_id'])) {
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

$alertMessage = "";

// HANDLE DELETE REQUEST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_file'])) {
    $student_id = $_SESSION['student_id'];
    $fileToDelete = $_POST['delete_file'];

    $stmt = $conn->prepare("SELECT docuRoute1 FROM route1final_files WHERE student_id = ? AND docuRoute1 = ?");
    $stmt->bind_param("ss", $student_id, $fileToDelete);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        if (file_exists($fileToDelete)) {
            unlink($fileToDelete); // Delete the file from folder
        }
        $deleteStmt = $conn->prepare("DELETE FROM route1final_files WHERE student_id = ? AND docuRoute1 = ?");
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

    // Fetch the department from the student's account
    $stmt = $conn->prepare("SELECT department FROM student WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $stmt->bind_result($department);
    $stmt->fetch();
    $stmt->close();

    if (!$department) {
        // If no department is found for the student ID, show an alert and go back
        echo "<script>alert('No account found with the provided ID number.'); window.history.back();</script>";
        exit; // Exit to prevent further processing
    } else {
        if (isset($_FILES["docuRoute1"]) && $_FILES["docuRoute1"]["error"] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES["docuRoute1"]["tmp_name"];
            $fileName = $_FILES["docuRoute1"]["name"];
            $uploadDir = "../../../uploads/";  // Ensure this directory exists
            $filePath = $uploadDir . basename($fileName);

            $allowedTypes = [
                "application/pdf",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            ];

            if (in_array($_FILES["docuRoute1"]["type"], $allowedTypes)) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);  // Create the upload directory if not exists
                }

                // Check if the student already has an uploaded file for Route 1
                $stmt = $conn->prepare("SELECT COUNT(*) FROM route1final_files WHERE student_id = ? AND department = ?");
                $stmt->bind_param("ss", $student_id, $department);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    // Alert if student has already uploaded a file for Route 1
                    echo "<script>alert('You can only upload one file for Route 1.'); window.history.back();</script>";
                    exit; // Exit to prevent further processing
                } elseif (move_uploaded_file($fileTmpPath, $filePath)) {
                    // Fetch panel and adviser IDs from Route 1
                    $panelStmt = $conn->prepare("SELECT panel1_id, panel2_id, panel3_id, panel4_id, adviser_id FROM route1proposal_files WHERE student_id = ?");
                    $panelStmt->bind_param("s", $student_id);
                    $panelStmt->execute();
                    $panelStmt->bind_result($panel1_id, $panel2_id, $panel3_id, $panel4_id, $adviser_id);
                    $panelStmt->fetch();
                    $panelStmt->close();

                    if (!isset($panel1_id)) {
                        // Alert if Route 1 information is not found
                        echo "<script>alert('Route 1 information not found. Please complete Route 1 first.'); window.history.back();</script>";
                        exit;
                    }

                    // Get current date/time
                    $date_submitted = date("Y-m-d H:i:s");

                    // Insert into Route 1 with panel and adviser IDs, and date_submitted
                    $stmt = $conn->prepare("INSERT INTO route1final_files (student_id, docuRoute1, department, panel1_id, panel2_id, panel3_id, panel4_id, adviser_id, date_submitted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("sssiiiiis", $student_id, $filePath, $department, $panel1_id, $panel2_id, $panel3_id, $panel4_id, $adviser_id, $date_submitted);
                        if ($stmt->execute()) {
                            // Alert on successful file upload
                            echo "<script>alert('File uploaded successfully.'); window.location.href = 'route1.php';</script>";
                        } else {
                            // Alert if there's an error saving the record
                            echo "<script>alert('Error saving record: " . $stmt->error . "'); window.history.back();</script>";
                        }
                        $stmt->close();
                    }
                } else {
                    // Alert if there was an error moving the file
                    echo "<script>alert('Error moving the file.'); window.history.back();</script>";
                }
            } else {
                // Alert if the file type is not allowed
                echo "<script>alert('Invalid file type. Only PDF and DOCX files are allowed.'); window.history.back();</script>";
            }
        } else {
            // Alert if there was an error with the file upload
            echo "<script>alert('Error uploading file.'); window.history.back();</script>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Route 1 - Thesis Routing System</title>
    <link rel="stylesheet" href="studstyles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <style>

    </style>
    <script>
        function viewFile(filePath, student_id, route1_id) {
            const modal = document.getElementById("fileModal");
            const contentArea = document.getElementById("fileModalContent");
            const routingFormArea = document.getElementById("routingForm");

            modal.style.display = "flex";
            contentArea.innerHTML = "Loading file...";
            routingFormArea.innerHTML = `
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
    <div><strong>adviser Name</strong></div>
    <div><strong>panel Name</strong></div>
    <div><strong>Date Released</strong></div>
</div>
<!-- Container for submitted form data -->
<div id="submittedFormsContainer" class="form-grid-container"></div>
<div id="noFormsMessage" style="margin-top: 10px; color: gray;"></div>

    `;

            // Load form data dynamically
            // Load form data dynamically using route1_id
            fetch(`get_all_forms.php?route1_id=${encodeURIComponent(route1_id)}`)
                .then(res => res.json())
                .then(data => {
                    console.log("Fetched forms:", data);
                    const rowsContainer = document.getElementById("submittedFormsContainer");

                    if (!Array.isArray(data) || data.length === 0) {
                        rowsContainer.innerHTML = `<div style="grid-column: span 9; text-align: center;">No routing form data available.</div>`;
                        return;
                    }
                    data.forEach(row => {
                        rowsContainer.innerHTML += `
                <div>${row.date_submitted}</div>
                <div>${row.chapter}</div>
                <div>${row.feedback}</div>
                <div>${row.paragraph_number}</div>
                <div>${row.page_number}</div>
                <div>${row.adviser_name}</div>
                <div>${row.panel_name}</div>
                <div>${row.date_released}</div>
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
    </script>
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
                <img src="../../../assets/logo.png" alt="Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>

        <div class="top-bar">
            <div class="navigation">
                <a id="homepage" href="../homepage.php">Home Page</a>
                <a href="#" id="submit-file-button">Submit File</a>

            </div>
            <div class="user-info">
                <div class="vl"></div>
                <span class="role">Student:</span>
                <span class="user-name"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Guest'); ?></span>
            </div>
        </div>

        <div class="main-content">
            <nav class="sidebar">
                <div class="menu">
                    <div class="menu-section">
                        <div class="menu-title">Research Proposal</div>
                        <ul>
                            <li><a href="../titleproposal/route1.php">Route 1</a></li>
                            <li><a href="../titleproposal/route2.php">Route 2</a></li>
                            <li><a href="../titleproposal/route3.php">Route 3</a></li>
                            <li><a href="../titleproposal/finaldocu.php">Final Document</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Final Defense</div>
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
                $student_id = $_SESSION['student_id'];
                $stmt = $conn->prepare("SELECT docuRoute1, route1_id FROM route1final_files WHERE student_id = ?");
                $stmt->bind_param("s", $student_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $filePath = htmlspecialchars($row['docuRoute1'], ENT_QUOTES);
                        $route1_id = htmlspecialchars($row['route1_id'], ENT_QUOTES);
                        $fileName = basename($filePath);

                        echo "
        <div class='file-preview'>
            <div class='file-name'>$fileName</div>
            <button class='view-button' onclick=\"viewFile('$filePath', '$student_id', '$route1_id')\">View</button>
            <button class='delete-button' onclick=\"confirmDelete('$filePath')\">Delete</button>
        </div>
        ";
                    }
                } else {
                    echo "<p>No files uploaded yet.</p>";
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

    <script>
        document.getElementById("submit-file-button").addEventListener("click", function (e) {
            e.preventDefault();
            document.querySelector("#docuRoute1").click();
        });
        document.querySelector("#docuRoute1").addEventListener("change", function () {
            document.querySelector("#file-upload-form").submit();
        });
    </script>


    <div id="fileModal" class="modal" style="display: none;">
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