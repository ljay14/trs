<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Security check
if (!isset($_SESSION['student_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access', 'exists' => false]);
    exit;
}

include '../../../connection.php';

// Get student_id from GET parameter or from session
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : $_SESSION['student_id'];

// Check if this is the student's own record
if ($student_id !== $_SESSION['student_id']) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied', 'exists' => false]);
    exit;
}

// Check if the student has a file in route1proposal_files
$stmt = $conn->prepare("SELECT route1_id FROM route1proposal_files WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $route1_id = $row['route1_id'];
    header('Content-Type: application/json');
    echo json_encode(['exists' => true, 'route1_id' => $route1_id]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['exists' => false]);
}

$stmt->close();
$conn->close();
?> 