<?php
session_start(); // Start the session

// Check if the user is not logged in (session variable is not set)
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../../../logout.php");
    exit;
}

// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch departments
$departments = [];
$deptQuery = "SELECT DISTINCT department FROM student";
$deptResult = $conn->query($deptQuery);
if ($deptResult->num_rows > 0) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Initialize variables
$panel = [];
$adviser = [];
$files = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['department'])) {
    $selectedDepartment = $_POST['department'];
    $_SESSION['selected_department'] = $selectedDepartment;
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Load data for selected department
if (isset($_SESSION['selected_department'])) {
    $selectedDepartment = $_SESSION['selected_department'];

    // Fetch panel data
    $panelStmt = $conn->prepare("SELECT * FROM panel WHERE department = ?");
    $panelStmt->bind_param("s", $selectedDepartment);
    $panelStmt->execute();
    $panelResult = $panelStmt->get_result();
    while ($row = $panelResult->fetch_assoc()) {
        $panel[$row['position']][] = $row['fullname'];
    }
    $panelStmt->close();

    // Fetch adviser data
    $adviserStmt = $conn->prepare("SELECT * FROM adviser WHERE department = ?");
    $adviserStmt->bind_param("s", $selectedDepartment);
    $adviserStmt->execute();
    $adviserResult = $adviserStmt->get_result();
    while ($row = $adviserResult->fetch_assoc()) {
        $adviser[] = $row['fullname'];
    }
    $adviserStmt->close();

    // Fetch files
// Fetch files
$fileStmt = $conn->prepare(
    "SELECT 
        docuRoute3 AS filepath, 
        docuRoute3 AS filename, 
        date_submitted,
        controlNo,
        group_number,
        fullname,
        student_id, panel1_id, panel2_id, panel3_id, panel4_id, adviser_id
     FROM route3final_files 
     WHERE department = ?"
);

    
    $fileStmt->bind_param("s", $selectedDepartment);
    $fileStmt->execute();
    $fileResult = $fileStmt->get_result();
    while ($row = $fileResult->fetch_assoc()) {
        $files[] = [
            'filepath' => $row['filepath'],
            'filename' => $row['filename'],
            'controlNo' => $row['controlNo'],
            'group_number' => $row['group_number'],
            'fullname' => $row['fullname'],
            'student_id' => $row['student_id'],
            'panel1_id' => $row['panel1_id'],
            'panel2_id' => $row['panel2_id'],
            'panel3_id' => $row['panel3_id'],
            'panel4_id' => $row['panel4_id'],
            'adviser_id' => $row['adviser_id']
        ];
    }
    
    $fileStmt->close();
}

// Handle file submission for panelists and adviser
// Handle file submission for panelists and adviser
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selected_files'])) {
    $selectedFiles = $_POST['selected_files'];
    $selectedAdviser = $_POST['selected_adviser'];
    $panel1 = $_POST['panel1'] ?? null;
    $panel2 = $_POST['panel2'] ?? null;
    $panel3 = $_POST['panel3'] ?? null;
    $panel4 = $_POST['panel4'] ?? null;

    // Validation
    if (empty($selectedFiles)) {
        echo "<script>alert('Please select at least one file.');</script>";
    } elseif (empty($panel1) && empty($panel2) && empty($panel3) && empty($panel4)) {
        echo "<script>alert('Please select at least one panel.');</script>";
    } else {
        foreach ($selectedFiles as $filePath) {
            $fileName = $filePath; // Use the full path stored in the DB

            // Check if the file exists in DB
            $checkStmt = $conn->prepare("SELECT * FROM route3final_files WHERE docuRoute3 = ?");
            $checkStmt->bind_param("s", $fileName);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $fileExists = $result->num_rows > 0;
            $checkStmt->close();

            if ($fileExists) {
                // Update panel and adviser and set date_submitted
                $dateNow = date('Y-m-d H:i:s'); // Get the current date and time
                $updatePanelStmt = $conn->prepare("UPDATE route3final_files 
                    SET panel1_id = ?, panel2_id = ?, panel3_id = ?, panel4_id = ?, adviser_id = ?, date_submitted = ? 
                    WHERE docuRoute3 = ?");
                $updatePanelStmt->bind_param("iiiisss", $panel1, $panel2, $panel3, $panel4, $selectedAdviser, $dateNow, $fileName);
                $updatePanelStmt->execute();
                $updatePanelStmt->close();

                echo "<script>alert('Successfully Submitted.');</script>";
            } else {
                echo "<script>alert('File $fileName not found in the database.');</script>";
            }
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
    <link rel="stylesheet" href="stylesadmin.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <style>
        .view-button {
            align-items: center;
        }

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
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <header class="header">
            <div class="logo-container">
                <img src="../../../assets/logo.png" alt="Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>

        <!-- Top Navigation Bar -->
        <div class="top-bar">
            <div class="navigation">
                <a id="homepage" href="../homepage.php">Home Page</a>

                <!-- Department Dropdown -->
                <div class="dropdown-container">
                    <form action="" method="POST" style="display: inline;">
                        <select name="department" onchange="this.form.submit()">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= htmlspecialchars($department) ?>"
                                    <?= isset($selectedDepartment) && $selectedDepartment == $department ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($department) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <!-- Panel Dropdowns -->
                    <select id="panel1-dropdown" name="panel1">
                        <option value="">Panel 1</option>
                        <?php
                        $panelStmt = $conn->prepare("SELECT panel_id, fullname  FROM panel WHERE department = ? AND position = 'panel1'");
                        $panelStmt->bind_param("s", $selectedDepartment);
                        $panelStmt->execute();
                        $panelResult = $panelStmt->get_result();
                        while ($row = $panelResult->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                <?= htmlspecialchars($row['fullname']) ?>
                            </option>
                        <?php endwhile; ?>
                        <?php $panelStmt->close(); ?>
                    </select>

                    <select id="panel2-dropdown" name="panel2">
                        <option value="">Panel 2</option>
                        <?php
                        $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel2'");
                        $panelStmt->bind_param("s", $selectedDepartment);
                        $panelStmt->execute();
                        $panelResult = $panelStmt->get_result();
                        while ($row = $panelResult->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                <?= htmlspecialchars($row['fullname']) ?>
                            </option>
                        <?php endwhile; ?>
                        <?php $panelStmt->close(); ?>
                    </select> 
                    <select id="panel3-dropdown" name="panel3">
                        <option value="">Panel 3</option>
                        <?php
                        $panelStmt = $conn->prepare("SELECT panel_id, fullname  FROM panel WHERE department = ? AND position = 'panel3'");
                        $panelStmt->bind_param("s", $selectedDepartment);
                        $panelStmt->execute();
                        $panelResult = $panelStmt->get_result();
                        while ($row = $panelResult->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                <?= htmlspecialchars($row['fullname']) ?>
                            </option>
                        <?php endwhile; ?>
                        <?php $panelStmt->close(); ?>
                    </select> 
                    <select id="panel4-dropdown" name="panel4">
                        <option value="">Panel 4</option>
                        <?php
                        $panelStmt = $conn->prepare("SELECT panel_id, fullname  FROM panel WHERE department = ? AND position = 'panel4'");
                        $panelStmt->bind_param("s", $selectedDepartment);
                        $panelStmt->execute();
                        $panelResult = $panelStmt->get_result();
                        while ($row = $panelResult->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                <?= htmlspecialchars($row['fullname']) ?>
                            </option>
                        <?php endwhile; ?>
                        <?php $panelStmt->close(); ?>
                    </select>

                    <select id="adviser-dropdown" name="selected_adviser">
                        <option value="">Adviser</option>
                        <?php
                        $adviserStmt = $conn->prepare("SELECT adviser_id, fullname FROM adviser WHERE department = ?");
                        $adviserStmt->bind_param("s", $selectedDepartment);
                        $adviserStmt->execute();
                        $adviserResult = $adviserStmt->get_result();
                        while ($row = $adviserResult->fetch_assoc()):
                        ?>
                            <option value="<?= htmlspecialchars($row['adviser_id']) ?>">
                                <?= htmlspecialchars($row['fullname']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <button id="external-submit-button">Submit</button>
            <div class="user-info">
            <div class="routeNo" style="margin-right: 20px;">Final - Route 3</div>
                <div class="vl"></div>
                <span class="role">Admin:</span>
                <span class="user-name">
                    <?php echo isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Guest'; ?>
                </span>
            </div>
        </div>
        <!-- Main Content -->
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
                    <div class="menu-section">
                        <div class="menu-title">Register Account</div>
                        <ul>
                            <li><a href="../registeraccount/panel.php">Panel</a></li>
                            <li><a href="../registeraccount/adviser.php">Adviser</a></li>
                        </ul>
                    </div>
                    <div class="menu-section">
                        <div class="menu-title">Registered Account</div>
                        <ul>
                            <li><a href="../registeredaccount/panel_register.php">Panel</a></li>
                            <li><a href="../registeredaccount/adviser_register.php">Adviser</a></li>
                            <li><a href="../registeredaccount/student_register.php">Student</a></li>
                        </ul>
                    </div>
                </div>
                <div class="logout">
                <a href="../../../logout.php">Logout</a>
                </div>
            </nav>

            <div class="content" id="content-area" style="display: flex; justify-content: center; padding: 20px; justify-items: center; width: 90%;">
    <form id="submission-form" action="route3.php" method="POST" style="width: 100%; max-width: 1200px;">
        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse; background-color: #f1f1f1; text-align: left;">
            <thead>
                <tr style="background-color: #ccc; text-align: center;">
                    <th>Select</th>
                    <th>Control No.</th>
                    <th>Leader</th>
                    <th>Group No.</th>
                    <th>File Name</th>
                    <th>Assigned</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="file-list">
                <?php if (!empty($files)): ?>
                    <?php foreach ($files as $file): ?>
                        <?php
                            $filepath = htmlspecialchars($file['filepath'], ENT_QUOTES);
                            $filename = htmlspecialchars(basename($file['filename']), ENT_QUOTES);
                            $controlNo = htmlspecialchars($file['controlNo'] ?? '', ENT_QUOTES);
                            $fullname = htmlspecialchars($file['fullname'] ?? '', ENT_QUOTES);
                            $group_number = htmlspecialchars($file['group_number'] ?? '', ENT_QUOTES);
                            $route3_id = htmlspecialchars($file['route3_id'] ?? '', ENT_QUOTES);
                            $student_id = htmlspecialchars($file['student_id'] ?? '', ENT_QUOTES);
                            // Check if file is assigned to a panelist and adviser
                            $assigned = '';
                            if ($file['panel1_id'] || $file['panel2_id'] || $file['panel3_id'] || $file['panel4_id']) {
                                $assigned = 'Panelists Assigned';
                            }
                            if ($file['adviser_id']) {
                                $assigned .= $assigned ? ' & Adviser Assigned' : 'Adviser Assigned';
                            }
                            $assigned = $assigned ?: 'Not Assigned';
                        ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="selected_files[]" value="<?= $filepath ?>">
                            </td>
                            <td><?= $controlNo ?></td>
                            <td><?= $fullname ?></td>
                            <td><?= $group_number ?></td>
                            <td><?= $filename ?></td>
                            <td style="text-align: center;"><?= $assigned ?></td>
                            <td style="text-align: center;">
                            <button type="button" class="view-button" onclick="viewFile('<?= $filepath ?>', '<?= $student_id ?>', '<?= $route3_id ?>')">View</button>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No files found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Hidden Inputs -->
        <input type="hidden" name="panel1" id="hidden-panel1">
        <input type="hidden" name="panel2" id="hidden-panel2">
        <input type="hidden" name="panel3" id="hidden-panel3">
        <input type="hidden" name="panel4" id="hidden-panel4">
        <input type="hidden" name="selected_adviser" id="hidden-adviser">
    </form>
</div>

<!-- Modal for Viewing Files -->
<div id="fileModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <div class="modal-layout">
                <div id="fileModalContent" class="file-preview-section"></div>
                <div id="routingForm" class="routing-form-section"></div>
            </div>
        </div>
    </div>





        </div>
    </div>
</body>

</html>
<script>
    // Handles file selection toggle
    function toggleFileSelection(fileElement) {
        const checkbox = fileElement.querySelector("input[type='checkbox']");
        checkbox.checked = !checkbox.checked;
        fileElement.classList.toggle("selected");
    }

    // Handles form submission
    // Handles form submission
document.getElementById("external-submit-button").addEventListener("click", function () {
    const panel1 = document.getElementById("panel1-dropdown").value.trim();
    const panel2 = document.getElementById("panel2-dropdown").value.trim();
    const panel3 = document.getElementById("panel3-dropdown").value.trim();
    const panel4 = document.getElementById("panel4-dropdown").value.trim();
    const adviser = document.getElementById("adviser-dropdown").value.trim();

    // Set hidden fields
    document.getElementById("hidden-panel1").value = panel1;
    document.getElementById("hidden-panel2").value = panel2;
    document.getElementById("hidden-panel3").value = panel3;
    document.getElementById("hidden-panel4").value = panel4;
    document.getElementById("hidden-adviser").value = adviser;

    // Validate file selection
    const selectedFiles = document.querySelectorAll("input[name='selected_files[]']:checked");
    if (selectedFiles.length === 0) {
        alert("Please select at least one file.");
        return;
    }

    // Validate at least one panelist is selected and an adviser is selected
    if (!adviser) {
        alert("Please select an adviser.");
        return;
    }

    // If at least one panelist is selected, proceed
    const panelSelected = panel1 || panel2 || panel3 || panel4;
    if (!panelSelected) {
        alert("Please select at least one panelist.");
        return;
    }

    // Submit the form
    document.getElementById("submission-form").submit();
});


function viewFile(filePath, student_id, route1_id, route2_id, route3_id) {
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
            fetch(`route3get_all_forms.php?student_id=${encodeURIComponent(student_id)}&route1_id=${encodeURIComponent(route1_id)}&route2_id=${encodeURIComponent(route2_id)}&route3_id=${encodeURIComponent(route3_id)}`)
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


</script>

