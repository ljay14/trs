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

    $stmt = $conn->prepare("SELECT docuRoute2 FROM route2final_files WHERE student_id = ? AND docuRoute2 = ?");
    $stmt->bind_param("ss", $student_id, $fileToDelete);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        if (file_exists($fileToDelete)) {
            unlink($fileToDelete); // Delete the file from folder
        }
        $deleteStmt = $conn->prepare("DELETE FROM route2final_files WHERE student_id = ? AND docuRoute2 = ?");
        $deleteStmt->bind_param("ss", $student_id, $fileToDelete);
        $deleteStmt->execute();
        $deleteStmt->close();
        $alertMessage = "File deleted successfully.";
    } else {
        $alertMessage = "File not found or you don't have permission.";
    }
    $stmt->close();

    $_SESSION['alert_message'] = $alertMessage;
    header("Location: route2.php");
    exit;
}
// HANDLE UPLOAD
// HANDLE UPLOAD

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['csrf_token'], $_POST['csrf_token']) && $_SESSION['csrf_token'] === $_POST['csrf_token']) {
    $student_id = $_POST["student_id"];

    // Fetch the department from the student's account
    $stmt = $conn->prepare("SELECT department, controlNo, fullname, group_number FROM student WHERE student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $stmt->bind_result($department, $controlNo, $fullname, $group_number);
    $stmt->fetch();
    $stmt->close();

    if (!$department) {
        echo "<script>alert('No account found with the provided ID number.'); window.history.back();</script>";
        exit;
    } else {
        if (isset($_FILES["docuRoute2"]) && $_FILES["docuRoute2"]["error"] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES["docuRoute2"]["tmp_name"];
            $fileName = $_FILES["docuRoute2"]["name"];
            $uploadDir = "../../../uploads/";
            $filePath = $uploadDir . basename($fileName);

            $allowedTypes = [
                "application/pdf",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            ];

            if (in_array($_FILES["docuRoute2"]["type"], $allowedTypes)) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Check if the student already uploaded for Route 2
                $stmt = $conn->prepare("SELECT COUNT(*) FROM route2final_files WHERE student_id = ? AND department = ?");
                $stmt->bind_param("ss", $student_id, $department);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    echo "<script>alert('You can only upload one file.'); window.history.back();</script>";
                    exit;
                } elseif (move_uploaded_file($fileTmpPath, $filePath)) {

                    // Fetch panel and adviser IDs from Route 1
                    $panelStmt = $conn->prepare("SELECT panel1_id, panel2_id, panel3_id, panel4_id, adviser_id FROM route1final_files WHERE student_id = ?");
                    $panelStmt->bind_param("s", $student_id);
                    $panelStmt->execute();
                    $panelStmt->bind_result($panel1_id, $panel2_id, $panel3_id, $panel4_id, $adviser_id);
                    $panelStmt->fetch();
                    $panelStmt->close();

                    if (!isset($panel1_id)) {
                        echo "<script>alert('Route 1 information not found. Please complete Route 1 first.'); window.history.back();</script>";
                        exit;
                    }

                    // Get current date/time
                    $date_submitted = date("Y-m-d H:i:s");

                    // Insert into Route 2 with date_submitted
                    $stmt = $conn->prepare("INSERT INTO route2final_files (student_id, docuRoute2, department, panel1_id, panel2_id, panel3_id, panel4_id, adviser_id, date_submitted, controlNo, fullname, group_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("sssiiiiissss", $student_id, $filePath, $department, $panel1_id, $panel2_id, $panel3_id, $panel4_id, $adviser_id, $date_submitted, $controlNo, $fullname, $group_number);
                        if ($stmt->execute()) {
                            echo "<script>alert('File uploaded successfully.'); window.location.href = 'route2.php';</script>";
                        } else {
                            echo "<script>alert('Error saving record: " . $stmt->error . "'); window.history.back();</script>";
                        }
                        $stmt->close();
                    }
                } else {
                    echo "<script>alert('Error moving the file.'); window.history.back();</script>";
                }
            } else {
                echo "<script>alert('Invalid file type. Only PDF and DOCX files are allowed.'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('Error uploading file.'); window.history.back();</script>";
        }
    }
}



$student_id = $_SESSION['student_id'];

// Get route1_id for the student
$stmt = $conn->prepare("SELECT route1_id FROM route1final_files WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$stmt->bind_result($route1_id);
$stmt->fetch();
$stmt->close();

if (!$route1_id) {
    echo "<script>alert('Route 1 submission not found.'); window.location.href='route1.php';</script>";
    exit;
}

// Get panel and adviser IDs
$stmt = $conn->prepare("SELECT panel1_id, panel2_id, panel3_id, panel4_id, adviser_id FROM route1final_files WHERE route1_id = ?");
$stmt->bind_param("i", $route1_id);
$stmt->execute();
$result = $stmt->get_result();
$route = $result->fetch_assoc();
$stmt->close();

$panel_ids = [];
foreach (['panel1_id', 'panel2_id', 'panel3_id', 'panel4_id'] as $key) {
    if (!empty($route[$key])) {
        $panel_ids[] = (int) $route[$key];
    }
}
$panel_ids = array_unique($panel_ids);
$adviser_id = (int) $route['adviser_id'];

$panel_submitted = 0;
$adviser_submitted = 0;

// Check how many panelists submitted
if (!empty($panel_ids)) {
    $placeholders = implode(',', array_fill(0, count($panel_ids), '?'));
    $sql = "SELECT COUNT(DISTINCT panel_id) FROM final_monitoring_form WHERE route1_id = ? AND panel_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);

    $params = array_merge([$route1_id], $panel_ids);
    $types = str_repeat('i', count($params));
    $bind_names[] = $types;
    foreach ($params as $i => $param) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    $stmt->execute();
    $stmt->bind_result($panel_submitted);
    $stmt->fetch();
    $stmt->close();
}

// Check if adviser submitted
$stmt = $conn->prepare("SELECT COUNT(*) FROM final_monitoring_form WHERE route1_id = ? AND adviser_id = ?");
$stmt->bind_param("ii", $route1_id, $adviser_id);
$stmt->execute();
$stmt->bind_result($adviser_submitted);
$stmt->fetch();
$stmt->close();

$total_required = count($panel_ids) + 1; // +1 for adviser
$total_submitted = $panel_submitted + $adviser_submitted;

echo "<script>console.log('Submitted: $total_submitted, Required: $total_required');</script>";

if ($total_submitted < $total_required) {
    echo "<script>alert('You cannot upload for Route 2 yet. Not all panelists and adviser have submitted feedback.'); window.history.back();</script>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Route 2 - Thesis Routing System</title>
    <link rel="stylesheet" href="studstyles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <style>
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
        }

        .modal-layout {
            display: flex;
            height: 100%;
            width: 98%;
        }

        .file-preview-section,
        .routing-form-section {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            border-right: 1px solid #ccc;
            min-width: 50%;
            /* Ensure it's taking 50% of the available space */
        }

        .routing-form-section {
            flex: 1;
            padding: 1rem;
            background-color: #f9f9f9;
            font-size: 0.85rem;
            box-sizing: border-box;
            overflow-y: auto;
            min-width: 50%;
            /* Ensure it's taking 50% of the available space */
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 5px;
            margin-bottom: 10px;
        }

        .form-input-row input,
        .form-input-row textarea {
            text-align: center;
        }

        .close-button {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            cursor: pointer;
        }

        .form-grid-container {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            border: 1px outset #ccc;
            border-radius: 6px;
            overflow: hidden;
        }

        .form-grid-container>div {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            font-size: 0.8rem;
            border: 1px solid #ccc;
            background-color: white;
            box-sizing: border-box;
        }

        .form-grid-container input,
        .form-grid-container textarea {
            width: 100%;
            height: 100%;
            padding: 4px;
            font-size: 0.75rem;
            border: none;
            outline: none;
            box-sizing: border-box;
            resize: none;
        }


        @media (max-width: 768px) {
            .modal-layout {
                flex-direction: column;
            }

            .file-preview-section {
                border-right: none;
                border-bottom: 1px solid #ccc;
            }
        }
    </style>
    <script>
        function viewFile(filePath, student_id, route2_id, route1_id) {
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
    <div><strong>Submitted By</strong></div>
    <div><strong>Date Released</strong></div>
    <div><strong>Status</strong></div>
</div>
<!-- Container for submitted form data -->
<div id="submittedFormsContainer" class="form-grid-container"></div>
<div id="noFormsMessage" style="margin-top: 10px; color: gray;"></div>

    `;

            // Load form data dynamically
            // Load form data dynamically using route2_id
            fetch(`route2get_all_forms.php?student_id=${encodeURIComponent(student_id)}&route1_id=${encodeURIComponent(route1_id)}&route2_id=${encodeURIComponent(route2_id)}`)
                .then(res => res.json())
                .then(data => {
                    console.log("Fetched forms:", data);
                    const rowsContainer = document.getElementById("submittedFormsContainer");

                    if (!Array.isArray(data) || data.length === 0) {
                        rowsContainer.innerHTML = `<div style="grid-column: span 9; text-align: center;">No routing form data available.</div>`;
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
        <div>${row.status}</div>
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
                form.action = "route2.php";

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
                <div class="routeNo" style="margin-right: 20px;">Final - Route 2</div>
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

$stmt = $conn->prepare("
    SELECT 
        docuRoute2, 
        route2_id, 
        controlNo, 
        fullname, 
        group_number 
    FROM 
        route2final_files 
    WHERE 
        student_id = ?
");

$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "
    <table border='1' cellpadding='10' cellspacing='0' style='width: 100%; border-collapse: collapse; text-align: left; background-color: rgb(202, 200, 200);'>
        <thead>
            <tr style='text-align: center;'>
                <th>Control No.</th>
                <th>Full Name</th>
                <th>Group No.</th>
                <th>File Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
    ";

    while ($row = $result->fetch_assoc()) {
        $filePath = htmlspecialchars($row['docuRoute2'], ENT_QUOTES);
        $route3_id = htmlspecialchars($row['route2_id'], ENT_QUOTES);
        $fileName = basename($filePath);
        $controlNo = htmlspecialchars($row['controlNo'], ENT_QUOTES);
        $fullName = htmlspecialchars($row['fullname'], ENT_QUOTES);
        $groupNo = htmlspecialchars($row['group_number'], ENT_QUOTES);

        echo "
            <tr>
                <td>$controlNo</td>
                <td>$fullName</td>
                <td>$groupNo</td>
                <td>$fileName</td>
                <td style='text-align: center;'>
                    <button class='view-button' onclick=\"viewFile('$filePath', '$student_id', '$route3_id')\">View</button>
                    <button class='delete-button' onclick=\"confirmDelete('$filePath')\">Delete</button>
                </td>
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
    <form action="route2.php" method="POST" enctype="multipart/form-data" id="file-upload-form" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>">
        <input type="hidden" name="student_id" value="<?= htmlspecialchars($_SESSION['student_id']); ?>">
        <input type="file" name="docuRoute2" id="docuRoute2" accept=".pdf" required>
    </form>

    <script>
        document.getElementById("submit-file-button").addEventListener("click", function (e) {
            e.preventDefault();
            document.querySelector("#docuRoute2").click();
        });
        document.querySelector("#docuRoute2").addEventListener("change", function () {
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