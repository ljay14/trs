<?php
include '../../../connection.php';

$student_id = $_GET['student_id'];

// Get all forms for this student ID regardless of who submitted them
$query = "
    SELECT 
        id,
        date_submitted,
        chapter,
        feedback,
        paragraph_number,
        page_number,
        adviser_name,
        panel_name,
        date_released,
        status,
        routeNumber,
        adviser_id,
        panel_id
    FROM 
        proposal_monitoring_form
    WHERE 
        student_id = ?
    ORDER BY 
        date_submitted DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$forms = [];
while ($row = $result->fetch_assoc()) {
    $forms[] = $row;
}

// Debugging: Check the result array
if (empty($forms)) {
    echo "No forms found for this student.";
} else {
    // Output the forms array as JSON
    header('Content-Type: application/json');
    echo json_encode($forms);
}
?>
