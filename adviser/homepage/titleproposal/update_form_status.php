<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

header("Content-Type: application/json");

$conn = new mysqli($servername, $username, $password, $dbname);

// Read JSON from request body
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id']) && isset($data['status'])) {
    $id = $data['id'];
    $status = $data['status'];

    $stmt = $conn->prepare("UPDATE proposal_monitoring_form SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
}
?>
