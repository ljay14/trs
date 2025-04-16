<?php
session_start();

if (!isset($_SESSION['panel_id'])) {
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

// Fetch departments
$departments = [];
$query = "SELECT DISTINCT department FROM student";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

$selectedDepartment = $_POST['department'] ?? '';
$panel_id = $_SESSION['panel_id'];
$fullname = $_SESSION['fullname'] ?? 'Panelist';

$stmt = $conn->prepare("SELECT student_id, route2_id FROM route2proposal_files 
                        WHERE panel1_id = ? OR panel2_id = ? OR panel3_id = ? OR panel4_id = ?");

if ($stmt === false) {
    die("Error preparing the query: " . $conn->error);
}

$stmt->bind_param("ssss", $panel_id, $panel_id, $panel_id, $panel_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_id = $row['student_id'];
    $route2_id = $row['route2_id'];
} else {
    $student_id = null;
    $route2_id = null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dateSubmitted'])) {
    $dateSubmittedArr = $_POST['dateSubmitted'];
    $chapterArr = $_POST['chapter'];
    $feedbackArr = $_POST['feedback'];
    $paragraphNumberArr = $_POST['paragraphNumber'];
    $pageNumberArr = $_POST['pageNumber'];
    $panelNameArr = $_POST['panelName'];
    $dateReleasedArr = $_POST['dateReleased'];
    $docuRoute2 = $_POST['docuRoute2'];
    $route2_id = $_POST['route2_id'];
    $student_id = $_POST['student_id'];

    // Prepare the query
    $stmt = $conn->prepare("INSERT INTO proposal_monitoring_form (panel_id, panel_name, date_submitted, chapter, feedback, paragraph_number, page_number, date_released, docuRoute2, route2_id, student_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Loop over the arrays and insert data
    for ($i = 0; $i < count($chapterArr); $i++) {
        $dateSubmitted = $dateSubmittedArr[$i];
        $chapter = $chapterArr[$i];
        $feedback = $feedbackArr[$i];
        $paragraphNumber = $paragraphNumberArr[$i];
        $pageNumber = $pageNumberArr[$i];
        $panelName = $panelNameArr[$i];
        $dateReleased = $dateReleasedArr[$i];

        $stmt->bind_param(
            "ssssssisssi",
            $panel_id,
            $panelName,
            $dateSubmitted,
            $chapter,
            $feedback,
            $paragraphNumber,
            $pageNumber,
            $dateReleased,
            $docuRoute2,
            $route2_id,
            $student_id
        );

        if (!$stmt->execute()) {
            echo "<script>alert('Error on row $i: " . addslashes($stmt->error) . "');</script>";
            break;
        }
    }

    echo "<script>alert('Form submitted successfully.'); window.location.href=window.location.href;</script>";
    exit;
}
$currentPanelPosition = '';
if (isset($_SESSION['panel_id'])) {
    $stmt = $conn->prepare("SELECT position FROM panel WHERE panel_id = ?");
    $stmt->bind_param("i", $_SESSION['panel_id']);
    $stmt->execute();
    $stmt->bind_result($currentPanelPosition);
    $stmt->fetch();
    $stmt->close();
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Thesis Routing System</title>
    <link rel="stylesheet" href="s.css">
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
            width: 100%;
        }

        .file-preview-section {
            flex: 0 0 37%;
            padding: 0.8rem;
            overflow-y: auto;
            border-right: 1px solid #ccc;
            min-width: 300px;
        }

        .routing-form-section {
            flex: 0 0 63%;
            padding: 2rem;
            background-color: #f9f9f9;
            font-size: 0.85rem;
            box-sizing: border-box;
            overflow-y: auto;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 5px;
            margin-bottom: 10px;
        }

        .form-row label {
            font-size: 0.75rem;
        }

        .form-row input,
        .form-row textarea {
            padding: 4px;
            font-size: 0.75rem;
            min-height: 24px;
        }

        .form-input-row input,
        .form-input-row textarea {
            font-size: 0.75rem;
            min-height: 24px;
            text-align: center;
        }

        .form-grid-container input,
        .form-grid-container textarea {
            width: 100%;
            height: 100%;
            padding: 4px;
            font-size: 0.75rem;
            box-sizing: border-box;
            border: none;
            outline: none;
            resize: none;
        }


        .form-input-row textarea {
            resize: vertical;
            min-height: 24px;
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
            grid-template-columns: repeat(10, 1fr);
            border: 1px solid #ccc;
            border-radius: 1px;
            overflow: hidden;
            text-align: center;

        }

        .form-grid-container>div {
            border: 1px solid #ccc;
            text-align: center;

            /* Optional: left-align for readability */
            font-size: 0.8rem;
            background-color: white;
            word-wrap: break-word;
            white-space: pre-wrap;
            vertical-align: center;
            /* Ensures content starts from the top */
        }
        .feedback-cell {
            max-height: 120px;
            overflow-y: auto;
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
            <div class="dropdown-container">
                <form method="POST">
                    <select name="department" onchange="this.form.submit()">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= htmlspecialchars($department) ?>" <?= $selectedDepartment == $department ? 'selected' : '' ?>>
                                <?= htmlspecialchars($department) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="user-info">
                <div class="vl"></div>
                <span class="role">Panelist:</span>
                <span class="user-name"><?= htmlspecialchars($fullname) ?></span>
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
                $query = "
                SELECT docuRoute2, department, student_id, route2_id 
                FROM route2proposal_files 
                WHERE (panel1_id = ? OR panel2_id = ? OR panel3_id = ? OR panel4_id = ?)
                " . ($selectedDepartment ? " AND department = ?" : "");


                $stmt = $conn->prepare($query);
                if ($selectedDepartment) {
                    $stmt->bind_param("sssss", $panel_id, $panel_id, $panel_id, $panel_id, $selectedDepartment);
                } else {
                    $stmt->bind_param("ssss", $panel_id, $panel_id, $panel_id, $panel_id);
                }
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $filePath = $row['docuRoute2']; // This is the path to the file
                        $route2_id = $row['route2_id'];
                        $student_id = $row['student_id'];
                        $fileName = basename($filePath); // ✅ Add this line with the correct field name
                
                        echo "
                        <div class='file-preview'>
                            <div class='file-name'>$fileName</div>
                            <button onclick=\"viewFile('$filePath', '$route2_id', '$student_id')\">View File</button>
                        </div>
                        ";
                    }


                } else {
                    echo "<p>No files uploaded yet.</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal Viewer -->
    <div id="fileModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">×</span>
            <div class="modal-layout">
                <div id="fileModalContent" class="file-preview-section"></div>
                <div id="routingForm" class="routing-form-section"></div>
            </div>
        </div>
    </div>


</body>

</html>
<?php
$currentPanelPosition = '';
if (isset($_SESSION['panel_id'])) {
    $stmt = $conn->prepare("SELECT position FROM panel WHERE panel_id = ?");
    $stmt->bind_param("i", $_SESSION['panel_id']);
    $stmt->execute();
    $stmt->bind_result($currentPanelPosition);
    $stmt->fetch();
    $stmt->close();
}

?>
<script>
    const currentPanelPosition = "<?php echo $currentPanelPosition; ?>"; // e.g., "panel1"
</script>


<script>
    const panelName = <?= json_encode($fullname) ?>;

    function viewFile(filePath, route2_id, student_id) {
        const modal = document.getElementById("fileModal");
        const contentArea = document.getElementById("fileModalContent");
        const routingForm = document.getElementById("routingForm");
        const extension = filePath.split('.').pop().toLowerCase();

        modal.style.display = "flex";
        contentArea.innerHTML = "Loading file...";
        routingForm.innerHTML = "";

        if (extension === "pdf") {
            contentArea.innerHTML = `<iframe src="${filePath}" width="100%" height="100%" style="border:none;"></iframe>`;
        } else if (extension === "docx") {
            fetch(filePath)
                .then(res => res.arrayBuffer())
                .then(buffer => mammoth.convertToHtml({ arrayBuffer: buffer }))
                .then(result => contentArea.innerHTML = `<div class="file-content">${result.value}</div>`)
                .catch(() => contentArea.innerHTML = "Error loading file.");
        } else {
            contentArea.innerHTML = "Unsupported file type.";
        }

        const today = new Date().toISOString().split('T')[0];

        routingForm.innerHTML = `
            <form method="POST">
                <input type="hidden" name="docuRoute2" value="${filePath}">
                <input type="hidden" name="student_id" value="${student_id}">
                <input type="hidden" name="route2_id" value="${route2_id}">

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
                <div style="margin-top: 1rem; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0;">ROUTING MONITORING FORM</h4>
                    <div>
                        <button type="button" onclick="addFormRow()">Add Row</button>
                        <button type="submit">Submit Routing Form</button>
                        <button type="button" onclick="showAllForms('${student_id}')">Show all Forms</button>
                    </div>
                </div>

                <!-- Header row for submitted forms -->
                <div class="form-grid-container" style="margin-top: 20px;">
                    <div><strong>Date Submitted</strong></div>
                    <div><strong>Chapter</strong></div>
                    <div><strong>Feedback</strong></div>
                    <div><strong>Paragraph No</strong></div>
                    <div><strong>Page No</strong></div>
                    <div><strong>Panel Name</strong></div>
                    <div><strong>Adviser Name</strong></div>
                    <div><strong>Date Released</strong></div>
                    <div><strong>Status</strong></div>
                    <div><strong>Action</strong></div>
                </div>

                <!-- Container for submitted form data -->
                <div id="submittedFormsContainer" class="form-grid-container"></div>
                <div id="noFormsMessage" style="margin-top: 10px; color: gray;"></div>

                <div class="form-grid-container">
                    <div><input type="text" name="dateSubmitted[]" value="${today}" readonly></div>
                    <div><input type="text" name="chapter[]" required></div>
                    <div><textarea name="feedback[]" required oninput="autoGrow(this)"></textarea></div>
                    <div><input type="number" name="paragraphNumber[]" required></div>
                    <div><input type="number" name="pageNumber[]" required></div>
                    <div><input type="text" name="panelName[]" value="${panelName}" readonly></div>
                    <div></div>
                    <div><input type="date" name="dateReleased[]" value="${today}" required></div>
                    <div></div>
                    <div></div>
                </div>
            </form>
        `;
    }

    function closeModal() {
        document.getElementById("fileModal").style.display = "none";
    }

    function addFormRow() {
        const row = `
            <div class="form-grid-container">
                <div><input type="text" name="dateSubmitted[]" value="<?php echo date('Y-m-d'); ?>" readonly></div>
                <div><input type="text" name="chapter[]" required></div>
                <div><textarea name="feedback[]" required oninput="autoGrow(this)"></textarea></div>
                <div><input type="number" name="paragraphNumber[]" required></div>
                <div><input type="number" name="pageNumber[]" required></div>
                <div><input type="text" name="panelName[]" value="<?= htmlspecialchars($fullname) ?>" readonly></div>
                <div></div>
                <div><input type="date" name="dateReleased[]" value="<?php echo date('Y-m-d'); ?>" required></div>
                <div></div>
                <div></div>
            </div>
        `;
        document.getElementById('routingRowsContainer').insertAdjacentHTML('beforeend', row);
    }

    let formsVisible = false;

    function showAllForms(student_id) {
        const formDataContainer = document.getElementById("submittedFormsContainer");
        const noFormsMessage = document.getElementById("noFormsMessage");
        const showButton = document.querySelector("button[onclick^='showAllForms']");

        if (formsVisible) {
            formDataContainer.innerHTML = "";
            noFormsMessage.innerText = "";
            showButton.textContent = "Show all Forms";
            formsVisible = false;
            return;
        }

        fetch('route2get_all_forms.php?student_id=' + student_id)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    noFormsMessage.innerText = "No routing forms submitted yet.";
                    return;
                }

                noFormsMessage.innerText = ""; // Clear message

                data.forEach(form => {
                    const formId = form.id;
                    const statusValue = (form.status || 'Pending').trim(); // <- Clean it

                    formDataContainer.innerHTML += `
                        <div>${form.date_submitted}</div>
                        <div>${form.chapter}</div>
                        <div class="feedback-cell">${form.feedback}</div>
                        <div>${form.paragraph_number}</div>
                        <div>${form.page_number}</div>
                        <div>${form.panel_name}</div>
                        <div>${form.adviser_name}</div>
                        <div>${form.date_released}</div>
                        <div>
                            <select id="statusSelect_${formId}" onchange="enableSaveButton(${formId})">
                                <option value="Pending" ${statusValue === 'Pending' ? 'selected' : ''}>Pending</option>
                                <option value="Approved" ${statusValue === 'Approved' ? 'selected' : ''}>Approved</option>
                                <option value="For Revision" ${statusValue === 'For Revision' ? 'selected' : ''}>For Revision</option>
                            </select>
                        </div>
                        <div>
                            <button id="saveButton_${formId}" onclick="saveStatus(${formId}, event)" disabled>Save</button>
                        </div>
                    `;
                });

                showButton.textContent = "Show less";
                formsVisible = true;
            })
            .catch(error => {
                console.error('Error fetching forms:', error);
            });
    }

    function autoGrow(textarea) {
        textarea.style.height = 'auto'; // Reset height
        textarea.style.height = textarea.scrollHeight + 'px'; // Set to scrollHeight
    }

    function saveStatus(formId, event) {
    event.preventDefault();
    const status = document.getElementById(`statusSelect_${formId}`).value;

    fetch('update_form_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: formId, status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Disable the save button once status is saved
            document.getElementById(`saveButton_${formId}`).disabled = true;
        } else {
            alert(data.message || 'Failed to update.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the status.');
    });
}

    function enableSaveButton(formId) {
    const saveButton = document.getElementById(`saveButton_${formId}`);
    saveButton.disabled = false;  // Enable the save button
}


</script>
