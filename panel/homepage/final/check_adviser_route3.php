<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include '../../../connection.php';

// Get route3_id from request
$route3_id = isset($_GET['route3_id']) ? intval($_GET['route3_id']) : 0;

// Default response
$response = [
    'hasReviewed' => false,
    'message' => 'Document not yet approved by adviser'
];

if ($route3_id > 0) {
    // Get student_id from route3_id
    $stmt = $conn->prepare("SELECT student_id FROM route3final_files WHERE route3_id = ?");
    $stmt->bind_param("i", $route3_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_id = null;
    
    if ($result && $row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
    }
    $stmt->close();
    
    if ($student_id) {
        // Ignore route3 status entirely and only check route2
        // Get route2_id
        $get_route2 = $conn->prepare("SELECT route2_id FROM route2final_files WHERE student_id = ?");
        $get_route2->bind_param("s", $student_id);
        $get_route2->execute();
        $route2_result = $get_route2->get_result();
        $route2_id = null;
        
        if ($route2_result && $route2_row = $route2_result->fetch_assoc()) {
            $route2_id = $route2_row['route2_id'];
        }
        $get_route2->close();
        
        if ($route2_id) {
            $check_route2 = $conn->prepare("SELECT COUNT(*) as count FROM final_monitoring_form 
                                          WHERE route2_id = ? AND adviser_id IS NOT NULL 
                                          AND (status = 'Approved' OR status = 'approved')");
            $check_route2->bind_param("i", $route2_id);
            $check_route2->execute();
            $route2_result = $check_route2->get_result();
            $route2_approved = ($route2_result && $route2_result->fetch_assoc()['count'] > 0);
            $check_route2->close();
            
            if ($route2_approved) {
                $response['hasReviewed'] = true;
                $response['message'] = 'Final Route 2 document was approved by adviser';
            } else {
                $response['hasReviewed'] = false;
                $response['message'] = 'Adviser must approve Final Route 2 before panel members can view Final Route 3 documents';
            }
        } else {
            $response['hasReviewed'] = false;
            $response['message'] = 'Final Route 2 document not found. Adviser must complete Final Route 2 review first';
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 