<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../../../connection.php';

$student_id = $_GET['student_id'] ?? '';

if ($student_id) {
    $stmt = $conn->prepare("
        SELECT 
            f.*,
            COALESCE(a.full_name, a.name, a.adviser_name) as adviser_name,
            COALESCE(p.full_name, p.name, p.panel_name) as panel_name 
        FROM 
            proposal_monitoring_form f
        LEFT JOIN 
            adviser a ON f.adviser_id = a.adviser_id
        LEFT JOIN 
            panel p ON f.panel_id = p.panel_id
        WHERE 
            f.student_id = ?
        ORDER BY 
            f.date_submitted ASC
    ");
    
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database query preparation failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("s", $student_id);
    
    if (!$stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Query execution failed: ' . $stmt->error]);
        exit;
    }
    
    $result = $stmt->get_result();
    
    $forms = [];
    while ($row = $result->fetch_assoc()) {
        $forms[] = $row;
    }

    // Output the forms array as JSON
    header('Content-Type: application/json');
    echo json_encode($forms);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No student ID provided']);
}
?>