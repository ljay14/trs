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
    $stmt = $conn->prepare("SELECT student_id FROM route3proposal_files WHERE route3_id = ?");
    $stmt->bind_param("i", $route3_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_id = null;
    
    if ($result && $row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
    }
    $stmt->close();
    
    if ($student_id) {
        // First check if there's an approved route3 monitoring form
        $check_route3 = $conn->prepare("SELECT COUNT(*) as count FROM proposal_monitoring_form 
                                       WHERE route3_id = ? AND adviser_id IS NOT NULL 
                                       AND (status = 'Approved' OR status = 'approved')");
        $check_route3->bind_param("i", $route3_id);
        $check_route3->execute();
        $route3_result = $check_route3->get_result();
        $route3_approved = ($route3_result && $route3_result->fetch_assoc()['count'] > 0);
        $check_route3->close();
        
        if ($route3_approved) {
            // If route3 is approved, allow viewing
            $response['hasReviewed'] = true;
            $response['message'] = 'Route 3 document approved by adviser';
        } else {
            // If route3 is not approved, check if route3 has any adviser review (even pending)
            $check_route3_review = $conn->prepare("SELECT COUNT(*) as count FROM proposal_monitoring_form 
                                            WHERE route3_id = ? AND adviser_id IS NOT NULL");
            $check_route3_review->bind_param("i", $route3_id);
            $check_route3_review->execute();
            $route3_review_result = $check_route3_review->get_result();
            $route3_has_review = ($route3_review_result && $route3_review_result->fetch_assoc()['count'] > 0);
            $check_route3_review->close();
            
            if ($route3_has_review) {
                // If adviser has reviewed route3 but not approved it, panel can't view
                $response['hasReviewed'] = false;
                $response['message'] = 'Route 3 document has been reviewed by adviser but not yet approved';
            } else {
                // If no route3 review exists, check if route2 was approved (not just reviewed)
                $get_route2 = $conn->prepare("SELECT route2_id FROM route2proposal_files WHERE student_id = ?");
                $get_route2->bind_param("s", $student_id);
                $get_route2->execute();
                $route2_result = $get_route2->get_result();
                $route2_id = null;
                
                if ($route2_result && $route2_row = $route2_result->fetch_assoc()) {
                    $route2_id = $route2_row['route2_id'];
                }
                $get_route2->close();
                
                if ($route2_id) {
                    $check_route2 = $conn->prepare("SELECT COUNT(*) as count FROM proposal_monitoring_form 
                                                  WHERE route2_id = ? AND adviser_id IS NOT NULL 
                                                  AND (status = 'Approved' OR status = 'approved')");
                    $check_route2->bind_param("i", $route2_id);
                    $check_route2->execute();
                    $route2_result = $check_route2->get_result();
                    $route2_approved = ($route2_result && $route2_result->fetch_assoc()['count'] > 0);
                    $check_route2->close();
                    
                    if ($route2_approved) {
                        $response['hasReviewed'] = true;
                        $response['message'] = 'Route 2 document was approved by adviser';
                    } else {
                        $response['hasReviewed'] = false;
                        $response['message'] = 'Adviser must approve Route 2 or Route 3 before panel members can view this document';
                    }
                } else {
                    $response['hasReviewed'] = false;
                    $response['message'] = 'Route 2 document not found. Adviser must review Route 3 first';
                }
            }
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 