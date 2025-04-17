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
    $fileStmt = $conn->prepare(
        "SELECT sd.docuRoute3 AS filepath, docuRoute3 AS filename, sd.date_submitted 
         FROM route3proposal_files sd 
         INNER JOIN student s ON sd.student_id = s.student_id 
         WHERE s.department = ?"
    );
    
    $fileStmt->bind_param("s", $selectedDepartment);
    $fileStmt->execute();
    $fileResult = $fileStmt->get_result();
    while ($row = $fileResult->fetch_assoc()) {
        $files[] = [
            'filepath' => $row['filepath'],
            'filename' => $row['filename']
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
            $checkStmt = $conn->prepare("SELECT * FROM route3proposal_files WHERE docuRoute3 = ?");
            $checkStmt->bind_param("s", $fileName);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $fileExists = $result->num_rows > 0;
            $checkStmt->close();

            if ($fileExists) {
                // Update panel and adviser and set date_submitted
                $dateNow = date('Y-m-d H:i:s'); // Get the current date and time
                $updatePanelStmt = $conn->prepare("UPDATE route3proposal_files 
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
            <div class="routeNo" style="margin-right: 20px;">Proposal - Route 3</div>
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
                            <li><a href="../registeraccount/student_register.php">Student</a></li>
                        </ul>
                    </div>
                </div>
                <div class="logout">
                    <a href="../../../logout.php">Logout</a>
                </div>
            </nav>

            <div class="content" id="content-area">
            <form id="submission-form" action="route1.php" method="POST">
    <ul id="file-list">
        <?php if (!empty($files)): ?>
            <?php foreach ($files as $file): ?>
                <li class="file-preview" onclick="toggleFileSelection(this, '<?= htmlspecialchars($file['filepath']) ?>')">
                    
                    <input type="checkbox" name="selected_files[]" value="<?= htmlspecialchars($file['filepath']) ?>" style="display: none;">
                    <span class="file-name"><?= htmlspecialchars(basename($file['filename'])) ?></span>
                    <a href="<?= htmlspecialchars($file['filepath']) ?>" target="_blank" class="view-button">View</a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>

    <!-- Hidden Inputs -->
    <input type="hidden" name="panel1" id="hidden-panel1">
    <input type="hidden" name="panel2" id="hidden-panel2">
    <input type="hidden" name="panel3" id="hidden-panel3">
    <input type="hidden" name="panel4" id="hidden-panel4">
    <input type="hidden" name="selected_adviser" id="hidden-adviser">
</form>

<!-- Button Outside the Form -->


            </div>

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
</script>

        </div>
    </div>
</body>

</html>