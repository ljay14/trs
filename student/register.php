<?php
// Enable error reporting and log errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_error.log');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve and sanitize inputs
    $school_id = mysqli_real_escape_string($conn, $_POST['school_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $school_year = mysqli_real_escape_string($conn, $_POST['school_year']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $course = isset($_POST['other_course']) && !empty($_POST['other_course'])
        ? mysqli_real_escape_string($conn, $_POST['other_course'])
        : mysqli_real_escape_string($conn, $_POST['course']);

    $adviser = mysqli_real_escape_string($conn, $_POST['adviser']);
    $group_number = mysqli_real_escape_string($conn, $_POST['group_number']);
    $members = isset($_POST['member_fullname']) ? $_POST['member_fullname'] : [];
    $group_members_json = json_encode($members); // Now properly defined    
    $controlNo = mysqli_real_escape_string($conn, $_POST['controlNo']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);

    // Validate passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.history.back();</script>";
        exit;
    }

    // Insert data into the database (no need for student_id since it's auto-incremented)
    $sql = "INSERT INTO student (title, controlNo, school_id, password, confirm_password, fullname, school_year, department, course, adviser, group_number, group_members) 
            VALUES ('$title', '$controlNo','$school_id', '$password','$confirm_password', '$fullname', '$school_year', '$department', '$course', '$adviser', '$group_number', '$group_members_json')";

    if ($conn->query($sql) === TRUE) {
        // Get the auto-generated student_id
        $student_id = $conn->insert_id;
        echo "<script>alert('Registration successful!'); window.location.href = 'register.php';</script>";
    } else {
        echo "<script>alert('Error: " . addslashes($conn->error) . "'); window.history.back();</script>";
    }
}

// Close the connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Routing System</title>
    <link rel="stylesheet" href="styleregister.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            display: flex;
            height: auto;
        }

        /* Left panel */
        .left-panel {
            background-color: #002366;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            width: 40%;
        }

        .left-panel img {
            max-width: 80px;
            margin-bottom: 20px;
        }

        .left-panel h1 {
            margin: 0;
            font-size: 30px;
            text-align: center;
        }

        .left-panel p {
            margin: 5px 0;
            text-align: center;
            font-size: 20px;
        }

        /* Right panel for the form */
        .right-panel {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
            width: 70%;
            height: 100%;
            padding: 20px;
        }

        .form-container {
            background-color: #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        form input,
        form select {
            padding: 10px;
            width: 100%;
            margin: 8px 0;
            border: none;
            /* No borders */
            border-radius: 5px;
        }

        form button {
            background-color: #4caf50;
            color: white;
            padding: 10px;
            border: none;
            /* No borders */
            border-radius: 5px;
            width: 100%;
            margin: 10px 0;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        form button:hover {
            background-color: #45a049;
        }

        .researchers-section {
            margin: 10px 0;
            text-align: center;
            font-weight: bold;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .left-panel,
            .right-panel {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="left-panel">
        <img src="../assets/logo.png" alt="Logo">
        <h1>Saint Michael College of Caraga</h1>
        <p>Brgy 4, Atupan St., Nasipit, Agusan del Norte</p>
    </div>

    <div class="right-panel">
        <div class="form-container">
            <h2>Student Registration</h2>
            <form action="register.php" method="POST">
                <input type="text" name="title" placeholder="Title" required>
                <input type="text" name="controlNo" placeholder="Control Number" required>

                <input type="text" name="school_id" placeholder="School ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <div class="researchers-section">Researchers</div>
                <input type="text" name="fullname" placeholder="Complete Name" required>
                <div id="members-container">
                    <input type="text" name="member_fullname[]" placeholder="Name of Member">
                </div>
                <button type="button" onclick="addMemberField()">Add Member</button>
                <select name="school_year" required>
                    <option value="">Select School Year</option>
                    <option value="2024-2025">2024-2025</option>
                    <option value="2025-2026">2025-2026</option>
                    <option value="2026-2027">2026-2027</option>
                    <option value="2027-2028">2027-2028</option>
                </select>


                <!-- Department Dropdown Menu -->
                <select id="department" name="department" required onchange="filterCourses()">
                    <option value="">Select Department</option>
                    <option value="CBM">College of Business and Management</option>
                    <option value="CTE">College of Teacher Education</option>
                    <option value="CAS">College of Arts and Sciences</option>
                    <option value="CCIS">College of Computing and Information Science</option>
                    <option value="CTHM">College of Tourism and Hospitality Management</option>
                    <option value="CCJE">College of Criminal Justice Education</option>
                </select>

                <!-- Course Dropdown -->
                <select id="course" name="course" required>
                    <option value="">Select Course</option>
                </select>
                <div id="otherCourseDiv" style="margin-top: 10px; display: none;">
                    <input type="text" id="otherCourseInput" name="other_course" placeholder="Enter your course"
                        oninput="toggleCourseRequirement()" />
                </div>

                <!-- Button to add Other Course -->
                <button type="button" onclick="showOtherCourseInput()" style="margin-left: 10px;">Other Course</button>


                <input type="text" name="adviser" placeholder="Adviser" required>
                <input type="text" name="group_number" placeholder="Group Number" required>

                <button type="submit">Register</button>
                <button type="button" onclick="window.location.href='login.php'">Back</button>
            </form>
            <script>
                function addMemberField() {
                    const container = document.getElementById('members-container');
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.name = 'member_fullname[]';
                    input.placeholder = 'Name of Member';
                    container.appendChild(input);
                }
                const courseOptions = {
                    "CBM": [
                        { value: "BSBA-FM", text: "BSBA - Financial Management" },
                        { value: "BSBA-HRM", text: "BSBA - Human Resource Management" },
                        { value: "BSBA-MM", text: "BSBA - Marketing Management" },
                        { value: "BPA", text: "Bachelor of Public Administration" },
                        { value: "BSE", text: "Bachelor of Science in Entrepreneurship" }
                    ],
                    "CTE": [
                        { value: "ElemEd", text: "Bachelor of Elementary Education" },
                        { value: "SecEd", text: "Bachelor of Secondary Education" }
                    ],
                    "CAS": [
                        { value: "AB-English", text: "Bachelor of Arts major in English Language" }
                    ],
                    "CCIS": [
                        { value: "BSIT", text: "Bachelor of Science in Information Technology" },
                        { value: "BSCS", text: "Bachelor of Science in Computer Science" },
                        { value: "BSIS", text: "Bachelor of Science in Information System" }
                    ],
                    "CTHM": [
                        { value: "BSTM", text: "Bachelor of Science in Tourism Management" },
                        { value: "BSHM", text: "Bachelor of Science in Hospitality Management" }
                    ],
                    "CCJE": [
                        { value: "Crim", text: "Criminology" }
                    ]
                };

                function filterCourses() {
                    const department = document.getElementById('department').value;
                    const courseSelect = document.getElementById('course');
                    const otherCourseDiv = document.getElementById('otherCourseDiv');
                    const otherCourseInput = document.getElementById('otherCourseInput');

                    // Reset
                    courseSelect.innerHTML = '<option value="">Select Course</option>';
                    otherCourseDiv.style.display = 'none';
                    otherCourseInput.value = '';

                    courseSelect.required = true;
                    otherCourseInput.required = false;

                    if (courseOptions[department]) {
                        courseOptions[department].forEach(course => {
                            const option = document.createElement('option');
                            option.value = course.value;
                            option.textContent = course.text;
                            courseSelect.appendChild(option);
                        });
                    }
                }

                function showOtherCourseInput() {
                    document.getElementById('otherCourseDiv').style.display = 'block';
                    document.getElementById('course').value = '';
                    document.getElementById('course').required = false;
                    document.getElementById('otherCourseInput').required = true;
                    document.getElementById('otherCourseInput').focus();
                }

                function toggleCourseRequirement() {
                    const otherCourseInput = document.getElementById('otherCourseInput');
                    const courseSelect = document.getElementById('course');

                    if (otherCourseInput.value.trim() !== '') {
                        courseSelect.required = false;
                        otherCourseInput.required = true;
                    } else {
                        courseSelect.required = true;
                        otherCourseInput.required = false;
                    }
                }

                // Also when selecting from course dropdown, hide otherCourseDiv if not using "Other Course"
                document.getElementById('course').addEventListener('change', function () {
                    const otherCourseDiv = document.getElementById('otherCourseDiv');
                    const otherCourseInput = document.getElementById('otherCourseInput');

                    if (this.value !== '') {
                        otherCourseDiv.style.display = 'none';
                        otherCourseInput.value = '';
                        otherCourseInput.required = false;
                        this.required = true;
                    }
                });

            </script>
        </div>
    </div>
</body>

</html>