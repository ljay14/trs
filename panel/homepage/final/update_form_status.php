<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

header("Content-Type: application/json");
$conn = new mysqli($servername, $username, $password, $dbname);

// Read JSON from request body
$data = json_decode(file_get_contents("php://input"), true);

session_start(); // Make sure session is started to get panel_id

if (isset($data['id']) && isset($data['status']) && isset($_SESSION['panel_id'])) {
    $id = $data['id']; // form id
    $status = $data['status'];
    
    // Update only the adviser_status in proposal_monitoring_form
    $query = "UPDATE proposal_monitoring_form SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update adviser status."]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Missing required data."]);
}
?>
