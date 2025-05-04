<?php
include '../../../connection.php';

$student_id = $_GET['student_id'] ?? '';

if ($student_id) {
    $stmt = $conn->prepare("
        SELECT * FROM proposal_monitoring_form 
        WHERE student_id = ?
        AND (finaldocu_id IS NOT NULL)
        ORDER BY date_submitted ASC
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $forms = [];
    while ($row = $result->fetch_assoc()) {
        $forms[] = $row;
    }

    // Output as JSON
    header('Content-Type: application/json');
    echo json_encode($forms);
} else {
    echo json_encode([]);
}
?> 