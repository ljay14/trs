<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "trs";

$conn = new mysqli($servername, $username, $password, $dbname);

// Get route1_id, route2_id, and route3_id from query parameters
$route1_id = $_GET['route1_id'] ?? '';
$route2_id = $_GET['route2_id'] ?? '';
$route3_id = $_GET['route3_id'] ?? '';

$query = "SELECT * FROM final_monitoring_form";
$params = [];
$types = "";
$conditions = [];

if (!empty($route1_id)) {
    $conditions[] = "route1_id = ?";
    $params[] = $route1_id;
    $types .= "s";
}

if (!empty($route2_id)) {
    $conditions[] = "route2_id = ?";
    $params[] = $route2_id;
    $types .= "s";
}

if (!empty($route3_id)) {
    $conditions[] = "route3_id = ?";
    $params[] = $route3_id;
    $types .= "s";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" OR ", $conditions); // Use OR to match any route ID
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $forms = [];
    while ($row = $result->fetch_assoc()) {
        $forms[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($forms);
} else {
    echo json_encode([]); // No route ID provided
}
?>
