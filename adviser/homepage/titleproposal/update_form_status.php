<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

header("Content-Type: application/json");
$conn = new mysqli($servername, $username, $password, $dbname);

// Read JSON from request body
$data = json_decode(file_get_contents("php://input"), true);

session_start(); // Make sure session is started

if (isset($data['id']) && isset($data['status']) && isset($_SESSION['adviser_id'])) {
    $id = $data['id']; // form id
    $status = $data['status'];
    $adviser_id = $_SESSION['adviser_id'];

    // Check if the adviser is authorized for this form
    $checkQuery = "SELECT adviser_id FROM proposal_monitoring_form WHERE id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['adviser_id'] != $adviser_id) {
            echo json_encode(["success" => false, "message" => "You are not allowed to approve the status because you are not the assigned adviser."]);
        } else {
            // Authorized - proceed with update
            $query = "UPDATE proposal_monitoring_form SET status = ? WHERE id = ? AND adviser_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $status, $id, $adviser_id);
            if ($stmt->execute()) {
                echo json_encode(["success" => true]);
            } else {
                echo json_encode(["success" => false, "message" => "Failed to update adviser status."]);
            }
            $stmt->close();
        }
    } else {
        echo json_encode(["success" => false, "message" => "Form not found."]);
    }

    $checkStmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Missing required data."]);
}
?>
