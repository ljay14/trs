<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
include '../../../connection.php';

// Get finaldocu_id from request
$finaldocu_id = isset($_GET['finaldocu_id']) ? intval($_GET['finaldocu_id']) : 0;

// Default response
$response = [
    'hasReviewed' => false,
    'message' => 'Document not yet approved by adviser'
];

if ($finaldocu_id > 0) {
    // Get student_id from finaldocu_id
    $stmt = $conn->prepare("SELECT student_id FROM finaldocufinal_files WHERE finaldocu_id = ?");
    $stmt->bind_param("i", $finaldocu_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_id = null;
    
    if ($result && $row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
    }
    $stmt->close();
    
    if ($student_id) {
        // Ignore finaldocu status entirely and only check route3
        // Get route3_id
        $get_route3 = $conn->prepare("SELECT route3_id FROM route3final_files WHERE student_id = ?");
        $get_route3->bind_param("s", $student_id);
        $get_route3->execute();
        $route3_result = $get_route3->get_result();
        $route3_id = null;
        
        if ($route3_result && $route3_row = $route3_result->fetch_assoc()) {
            $route3_id = $route3_row['route3_id'];
        }
        $get_route3->close();
        
        if ($route3_id) {
            $check_route3 = $conn->prepare("SELECT COUNT(*) as count FROM final_monitoring_form 
                                          WHERE route3_id = ? AND adviser_id IS NOT NULL 
                                          AND (status = 'Approved' OR status = 'approved')");
            $check_route3->bind_param("i", $route3_id);
            $check_route3->execute();
            $route3_result = $check_route3->get_result();
            $route3_approved = ($route3_result && $route3_result->fetch_assoc()['count'] > 0);
            $check_route3->close();
            
            if ($route3_approved) {
                $response['hasReviewed'] = true;
                $response['message'] = 'Final Route 3 document was approved by adviser';
            } else {
                $response['hasReviewed'] = false;
                $response['message'] = 'Adviser must approve Final Route 3 before panel members can view Final Document';
            }
        } else {
            $response['hasReviewed'] = false;
            $response['message'] = 'Final Route 3 document not found. Adviser must complete Final Route 3 review first';
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 