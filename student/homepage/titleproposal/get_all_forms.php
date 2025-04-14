<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

$conn = new mysqli($servername, $username, $password, $dbname);

$route1_id = $_GET['route1_id'] ?? '';

if ($route1_id) {
    $stmt = $conn->prepare("SELECT * FROM proposal_monitoring_form WHERE route1_id = ?");
    $stmt->bind_param("s", $route1_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $forms = [];
    while ($row = $result->fetch_assoc()) {
        $forms[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($forms);
} else {
    echo json_encode([]);
}
?>
