<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

$conn = new mysqli($servername, $username, $password, $dbname);

$route1_id = $_GET['route1_id'] ?? '';
$route2_id = $_GET['route2_id'] ?? '';

if ($route1_id && $route2_id) {
    // BOTH route1_id and route2_id are provided
    $stmt = $conn->prepare("
        SELECT * FROM proposal_monitoring_form 
        WHERE route1_id = ? OR route2_id = ?
    ");
    $stmt->bind_param("ss", $route1_id, $route2_id);
} elseif ($route1_id) {
    // ONLY route1_id provided
    $stmt = $conn->prepare("
        SELECT * FROM proposal_monitoring_form 
        WHERE route1_id = ?
    ");
    $stmt->bind_param("s", $route1_id);
} elseif ($route2_id) {
    // ONLY route2_id provided
    $stmt = $conn->prepare("
        SELECT * FROM proposal_monitoring_form 
        WHERE route2_id = ?
    ");
    $stmt->bind_param("s", $route2_id);
} else {
    echo json_encode([]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

$forms = [];
while ($row = $result->fetch_assoc()) {
    $forms[] = $row;
}

header('Content-Type: application/json');
echo json_encode($forms);
?>
