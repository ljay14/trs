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
$adviser_id = $_SESSION['adviser_id'];
$fullname = $_SESSION['fullname'] ?? 'Adviser';

// Query to get student id and finaldocu_id based on adviser_id
$stmt = $conn->prepare("SELECT student_id, finaldocu_id FROM finaldocuproposal_files WHERE adviser_id = ?");
if ($stmt === false) {
    die("Error preparing the query: " . $conn->error); // This will show the MySQL error
}

$stmt->bind_param("s", $adviser_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the student id and finaldocu_id
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_id = $row['student_id'];
    $finaldocu_id = $row['finaldocu_id']; // Now you have the finaldocu_id
} else {
    // Handle case if no student is found (optional)
    $student_id = null;
    $finaldocu_id = null;
}

// Handle form submission
$showModal = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dateSubmitted'])) {
    $dateSubmittedArr = $_POST['dateSubmitted'];
    $chapterArr = $_POST['chapter'];
    $feedbackArr = $_POST['feedback'];
    $paragraphNumberArr = $_POST['paragraphNumber'];
    $pageNumberArr = $_POST['pageNumber'];
    $adviserNameArr = $_POST['adviserName'];
    $dateReleasedArr = $_POST['dateReleased'];
    $finaldocu = $_POST['finaldocu'];
    $finaldocu_id = $_POST['finaldocu_id'];
    $student_id = $_POST['student_id'];

    // Prepare SQL for inserting form data
    $stmt = $conn->prepare("INSERT INTO proposal_monitoring_form 
    (adviser_id, adviser_name, student_id, date_submitted, chapter, feedback, paragraph_number, page_number, date_released, finaldocu, finaldocu_id) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Error preparing the insert query: " . $conn->error);
    }

    // Loop through the form data and insert each row
    for ($i = 0; $i < count($chapterArr); $i++) {
        $dateSubmitted = $dateSubmittedArr[$i];
        $chapter = $chapterArr[$i];
        $feedback = $feedbackArr[$i];
        $paragraphNumber = $paragraphNumberArr[$i];
        $pageNumber = $pageNumberArr[$i];
        $adviserName = $adviserNameArr[$i];
        $dateReleased = $dateReleasedArr[$i];

        // Bind parameters including the finaldocu_id
        $stmt->bind_param(
            "ssssssissss",  // 11 specifiers
            $adviser_id, 
            $adviserName, 
            $student_id, 
            $dateSubmitted, 
            $chapter, 
            $feedback, 
            $paragraphNumber, 
            $pageNumber, 
            $dateReleased, 
            $finaldocu,
            $finaldocu_id
        );
        
        // Execute the statement
        if (!$stmt->execute()) {
            echo "<script>alert('Error on row $i: " . addslashes($stmt->error) . "');</script>";
            break;
        }
    }

    echo "<script>alert('Form submitted successfully.'); window.location.href=window.location.href;</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Thesis Routing System</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.2/mammoth.browser.min.js"></script>
    
    <style>
        :root {
            --primary: #002366;
            --primary-light: #0a3885;
            --accent: #4a6fd1;
            --light: #f5f7fd;
            --dark: #333;
            --success: #28a745;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --border: #e0e0e0;
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

        .homepage a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .homepage a:hover {
            color: var(--primary);
        }

        .dropdown-container select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid var(--border);
            background-color: white;
            font-family: inherit;
            cursor: pointer;
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
            width: 95%;
            height: 90%;
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
        }

        .form-grid-container > div:first-child(1),
        .form-grid-container > div:nth-child(2),
        .form-grid-container > div:nth-child(3),
        .form-grid-container > div:nth-child(4),
        .form-grid-container > div:nth-child(5),
        .form-grid-container > div:nth-child(6),
        .form-grid-container > div:nth-child(7),
        .form-grid-container > div:nth-child(8),
        .form-grid-container > div:nth-child(9) {
            background-color: var(--primary-light);
            color: white;
            font-weight: 600;
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
            color: var(--dark);
            transition: all 0.3s;
            z-index: 1000;
        }

        .close-button:hover {
            color: var(--accent);
        }

        .welcome-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 2rem;
            text-align: center;
        }

        .welcome-card h1 {
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .welcome-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .feedback-cell {
            max-height: 120px;
            overflow-y: auto;
        }

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

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border-left-color: var(--accent);
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        /* Additional button styling */
        .routing-form-section button {
            background-color: var(--accent);
            color: white;
            margin-right: 0.5rem;
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }

        .routing-form-section button:hover {
            background-color: var(--primary);
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="header">
            <div class="logo-container">
                <img src="../../../assets/logo.png" alt="SMCC Logo">
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
                <div class="routeNo" style="margin-right: 20px;">Proposal - Route 3</div>
                <div class="vl"></div>
                <span class="role">Adviser:</span>
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
                    SELECT 
                        finaldocu, 
                        student_id, 
                        finaldocu_id, 
                        department, 
                        group_number, 
                        controlNo, 
                        fullname, 
                        title 
                    FROM finaldocuproposal_files 
                    WHERE adviser_id = ?
                    " . ($selectedDepartment ? " AND department = ?" : "");

                $stmt = $conn->prepare($query);

                if ($selectedDepartment) {
                    $stmt->bind_param("ss", $adviser_id, $selectedDepartment);
                } else {
                    $stmt->bind_param("s", $adviser_id);
                }

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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                    ";

                    while ($row = $result->fetch_assoc()) {
                        $filePath = htmlspecialchars($row['finaldocu'], ENT_QUOTES);
                        $student_id = htmlspecialchars($row['student_id'], ENT_QUOTES);
                        $finaldocu_id = htmlspecialchars($row['finaldocu_id'], ENT_QUOTES);
                        $groupNo = htmlspecialchars($row['group_number'], ENT_QUOTES);
                        $controlNo = htmlspecialchars($row['controlNo'], ENT_QUOTES);
                        $fullName = htmlspecialchars($row['fullname'], ENT_QUOTES);
                        $title = htmlspecialchars($row['title'], ENT_QUOTES);

                        echo "
                            <tr>
                                <td>$controlNo</td>
                                <td>$fullName</td>
                                <td>$groupNo</td>
                                <td>$title</td>
                                <td style='text-align: center;'>
                                    <button class='view-button' onclick=\"viewFile('$filePath', '$student_id', '$finaldocu_id')\">View</button>
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

</html>
<script>
function viewFile(filePath, student_id, finaldocu_id) {
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

    const adviserName = <?= json_encode($fullname) ?>;

    routingForm.innerHTML = `
        <form method="POST">
            <input type="hidden" name="finaldocu" value="${filePath}">
            <input type="hidden" name="student_id" value="${student_id}">
            <input type="hidden" name="finaldocu_id" value="${finaldocu_id}">
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
    <div><strong>Submitted By</strong></div>
    <div><strong>Date Released</strong></div>
    <div><strong>Status</strong></div>
    <div><strong>Action</strong></div>
</div>

<!-- Container for submitted form data -->
<div id="submittedFormsContainer" class="form-grid-container"></div>
<div id="noFormsMessage" style="margin-top: 10px; color: gray;"></div>

        <div id="routingRowsContainer">
            <div class="form-grid-container">
                <div><input type="text" name="dateSubmitted[]" value="<?= date('Y-m-d'); ?>" readonly></div>
                <div><input type="text" name="chapter[]" required></div>
                <div><textarea name="feedback[]" required oninput="autoGrow(this)"></textarea></div>
                <div><input type="number" name="paragraphNumber[]" required></div>
                <div><input type="number" name="pageNumber[]" required></div>
                <div><input type="text" name="adviserName[]" value="${adviserName}" readonly></div>
                <div><input type="date" name="dateReleased[]" value="<?= date('Y-m-d'); ?>" required></div>
            </div>
        </div>
    </form>
`;}
        

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
    <div><input type="text" name="adviserName[]" value="<?= htmlspecialchars($fullname) ?>" readonly></div>
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

    fetch('route3get_all_forms.php?student_id=' + student_id)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                noFormsMessage.innerText = "No routing forms submitted yet.";
                return;
            }

            noFormsMessage.innerText = ""; // Clear message

            data.forEach(form => {
    const formId = form.id;
    const statusValue = (form.status || 'Pending').trim();

    let submittedBy = 'N/A';
    if (form.adviser_name) {
        submittedBy = `${form.adviser_name} - Adviser`;
    } else if (form.panel_name) {
        submittedBy = `${form.panel_name} - Panel`;
    }

    formDataContainer.innerHTML += `
        <div>${form.date_submitted}</div>
        <div>${form.chapter}</div>
        <div class="feedback-cell">${form.feedback}</div>
        <div>${form.paragraph_number}</div>
        <div>${form.page_number}</div>
        <div>${submittedBy}</div>
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
    event.preventDefault();  // Prevent any form submission

    const statusSelect = document.getElementById(`statusSelect_${formId}`);
    const newStatus = statusSelect.value;

    if (!newStatus) {
        alert("Please select a status.");
        return;
    }

    fetch('update_form_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            id: formId,
            status: newStatus  // Update to status (changed from adviser_status)
        })
    })
        .then(response => response.json())
        .then(data => {
            console.log("Response from update_form_status.php:", data);
            if (data.success) {
                const saveButton = document.getElementById(`saveButton_${formId}`);
                saveButton.disabled = true;
                saveButton.textContent = "Saved ✔";
                saveButton.style.backgroundColor = "green";
                saveButton.style.color = "white";
            } else {
                alert("Failed to save status: " + data.message);
            }
        })
        .catch(error => {
            alert("Error saving status.");
            console.error(error);
        });
}


    function enableSaveButton(formId) {
        const saveButton = document.getElementById(`saveButton_${formId}`);
        saveButton.disabled = false;  // Enable the save button
    }


        <?php if ($showModal): ?>
            window.addEventListener('load', () => {
                viewFile("<?= addslashes($lastFilePath) ?>");
            });
        <?php endif; ?>
    </script>
