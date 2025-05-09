<?php
session_start();
include '../../../connection.php';

// Check if user is logged in as panel
if (!isset($_SESSION['panel_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['route1_id'])) {
    echo json_encode(['error' => 'Missing route1_id parameter']);
    exit;
}

$route1_id = $_GET['route1_id'];

// Get all forms for this route1_id
$query = " SELECT id, date_submitted, chapter, feedback, paragraph_number, page_number, adviser_name, panel_name, date_released, status, routeNumber FROM proposal_monitoring_form WHERE route1_id = ? ORDER BY date_submitted DESC
";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("s", $route1_id);
$stmt->execute();
$result = $stmt->get_result();

$forms = [];
while ($row = $result->fetch_assoc()) {
    $forms[] = $row;
}

$stmt->close();
echo json_encode($forms);
?>
