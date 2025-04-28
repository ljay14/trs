<?php
session_start(); // Start the session

// Check if the user is not logged in (session variable is not set)
if (!isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../../../logout.php");
    exit;
}

// Database connection settings
include '../../../connection.php';



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
        finaldocu AS filepath, 
        finaldocu AS filename, 
        date_submitted,
        controlNo,
        group_number,
        fullname,
        title,
        student_id, panel1_id, panel2_id, panel3_id, panel4_id, adviser_id
     FROM finaldocuproposal_files 
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
            'adviser_id' => $row['adviser_id'],
            'title' => $row['title']
        ];
    }
    
    $fileStmt->close();
}

// Handle file submission for panelists and adviser
// Handle file submission for panelists and adviser
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['selected_files'])) {
    $selectedFiles = $_POST['selected_files'];
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
            $checkStmt = $conn->prepare("SELECT * FROM finaldocuproposal_files WHERE finaldocu = ?");
            $checkStmt->bind_param("s", $fileName);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $fileExists = $result->num_rows > 0;
            $checkStmt->close();

            if ($fileExists) {
                // Update panel and adviser and set date_submitted
                $dateNow = date('Y-m-d H:i:s'); // Get the current date and time
                $updatePanelStmt = $conn->prepare("UPDATE finaldocuproposal_files 
                    SET panel1_id = ?, panel2_id = ?, panel3_id = ?, panel4_id = ?, date_submitted = ? 
                    WHERE finaldocu = ?");
                $updatePanelStmt->bind_param("iiiiss", $panel1, $panel2, $panel3, $panel4, $dateNow, $fileName);
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
    <title>Final Document - Thesis Routing System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    <link rel="stylesheet" href="proposalstyles.css">
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <header class="header">
            <div class="logo-container">
                <img src="../../../assets/logo.png" alt="SMCC Logo">
                <div class="logo">Thesis Routing System</div>
            </div>
        </header>

        <!-- Top Navigation Bar -->
        <div class="top-bar">
            <div class="navigation">
            <div class="homepage">
                <a href="../homepage.php">Home Page</a>
            </div>

                <!-- Department Dropdown -->
                <div class="dropdown-container">
                    <form method="POST" style="display: inline;">
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
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel1'");
                            $panelStmt->bind_param("s", $selectedDepartment);
                            $panelStmt->execute();
                            $panelResult = $panelStmt->get_result();
                            while ($row = $panelResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                    <?= htmlspecialchars($row['fullname']) ?>
                                </option>
                            <?php endwhile;
                            $panelStmt->close();
                        }
                        ?>
                    </select>

                    <select id="panel2-dropdown" name="panel2">
                        <option value="">Panel 2</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel2'");
                            $panelStmt->bind_param("s", $selectedDepartment);
                            $panelStmt->execute();
                            $panelResult = $panelStmt->get_result();
                            while ($row = $panelResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                    <?= htmlspecialchars($row['fullname']) ?>
                                </option>
                            <?php endwhile;
                            $panelStmt->close();
                        }
                        ?>
                    </select> 
                    
                    <select id="panel3-dropdown" name="panel3">
                        <option value="">Panel 3</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel3'");
                            $panelStmt->bind_param("s", $selectedDepartment);
                            $panelStmt->execute();
                            $panelResult = $panelStmt->get_result();
                            while ($row = $panelResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                    <?= htmlspecialchars($row['fullname']) ?>
                                </option>
                            <?php endwhile;
                            $panelStmt->close();
                        }
                        ?>
                    </select>
                    
                    <select id="panel4-dropdown" name="panel4">
                        <option value="">Panel 4</option>
                        <?php
                        if (isset($selectedDepartment)) {
                            $panelStmt = $conn->prepare("SELECT panel_id, fullname FROM panel WHERE department = ? AND position = 'panel4'");
                            $panelStmt->bind_param("s", $selectedDepartment);
                            $panelStmt->execute();
                            $panelResult = $panelStmt->get_result();
                            while ($row = $panelResult->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($row['panel_id']) ?>">
                                    <?= htmlspecialchars($row['fullname']) ?>
                                </option>
                            <?php endwhile;
                            $panelStmt->close();
                        }
                        ?>
                    </select>
                </div>
            </div>
            <button id="external-submit-button">Submit</button>
            <div class="user-info">
                <div class="routeNo" style="margin-right: 20px;">Proposal - Final Document</div>
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
    <nav class="nav-menu">
        <!-- Title Proposal Section -->
        <div class="menu-item dropdown">
            <div class="menu-header">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <span>Title Proposal</span>
                <div class="dropdown-icon expanded">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="dropdown-content show">
                <a href="../titleproposal/route1.php" class="submenu-item">Route 1</a>
                <a href="../titleproposal/route2.php" class="submenu-item">Route 2</a>
                <a href="../titleproposal/route3.php" class="submenu-item">Route 3</a>
                <a href="../titleproposal/finaldocu.php" class="submenu-item">Endorsement Form</a>
            </div>
        </div>

        <!-- Final Defense Section -->
        <div class="menu-item dropdown">
            <div class="menu-header">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                        <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                    </svg>
                </div>
                <span>Final</span>
                <div class="dropdown-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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

        <!-- Department Course Section -->
        <div class="menu-item dropdown">
            <div class="menu-header">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <span>Department Course</span>
                <div class="dropdown-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="dropdown-content">
                <a href="../departmentcourse/departmentcourse.php" class="submenu-item">Department Course</a>
            </div>
        </div>

        <!-- Register Account Section -->
        <div class="menu-item dropdown">
            <div class="menu-header">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                </div>
                <span>Register Account</span>
                <div class="dropdown-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="dropdown-content">
                <a href="../registeraccount/panel.php" class="submenu-item">Panel</a>
                <a href="../registeraccount/adviser.php" class="submenu-item">Adviser</a>
            </div>
        </div>

        <!-- Registered Account Section -->
        <div class="menu-item dropdown">
            <div class="menu-header">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <span>Registered Account</span>
                <div class="dropdown-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="dropdown-content">
                <a href="../registeredaccount/panel_register.php" class="submenu-item">Panel</a>
                <a href="../registeredaccount/adviser_register.php" class="submenu-item">Adviser</a>
                <a href="../registeredaccount/student_register.php" class="submenu-item">Student</a>
            </div>
        </div>
    </nav>
    <div class="logout">
        <a href="../../../logout.php">Logout</a>
    </div>
</nav>

            <div class="content" id="content-area">
                <form id="submission-form" action="finaldocu.php" method="POST">
                    <table>
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Control No.</th>
                                <th>Leader</th>
                                <th>Group No.</th>
                                <th>Title</th>
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
                                        $finaldocu_id = htmlspecialchars($file['finaldocu_id'] ?? '', ENT_QUOTES);
                                        $student_id = htmlspecialchars($file['student_id'] ?? '', ENT_QUOTES);
                                        $title = htmlspecialchars($file['title'] ?? '', ENT_QUOTES);
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
                                        <td><?= $title ?></td>
                                        <td style="text-align: center;"><?= $assigned ?></td>
                                        <td style="text-align: center;">
                                            <button type="button" class="view-button" onclick="viewFile('<?= $filepath ?>', '<?= $student_id ?>', '<?= $finaldocu_id ?>')">View</button>
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
        </div>
    </div>

    <!-- Modal for Viewing Files -->
    <div id="fileModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">×</span>
            <div class="modal-layout">
                <div id="fileModalContent" class="file-preview-section"></div>
                <div id="routingForm" class="routing-form-section"></div>
            </div>
        </div>
    </div>
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
  

    // Set hidden fields
    document.getElementById("hidden-panel1").value = panel1;
    document.getElementById("hidden-panel2").value = panel2;
    document.getElementById("hidden-panel3").value = panel3;
    document.getElementById("hidden-panel4").value = panel4;


    // Validate file selection
    const selectedFiles = document.querySelectorAll("input[name='selected_files[]']:checked");
    if (selectedFiles.length === 0) {
        alert("Please select at least one file.");
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

<script src="../sidebar.js"></script>