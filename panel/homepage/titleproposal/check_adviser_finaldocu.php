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
    $stmt = $conn->prepare("SELECT student_id FROM finaldocuproposal_files WHERE finaldocu_id = ?");
    $stmt->bind_param("i", $finaldocu_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_id = null;
    
    if ($result && $row = $result->fetch_assoc()) {
        $student_id = $row['student_id'];
    }
    $stmt->close();
    
    if ($student_id) {
        // First check if there's an approved finaldocu monitoring form
        $check_finaldocu = $conn->prepare("SELECT COUNT(*) as count FROM proposal_monitoring_form 
                                       WHERE finaldocu_id = ? AND adviser_id IS NOT NULL 
                                       AND (status = 'Approved' OR status = 'approved')");
        $check_finaldocu->bind_param("i", $finaldocu_id);
        $check_finaldocu->execute();
        $finaldocu_result = $check_finaldocu->get_result();
        $finaldocu_approved = ($finaldocu_result && $finaldocu_result->fetch_assoc()['count'] > 0);
        $check_finaldocu->close();
        
        if ($finaldocu_approved) {
            // If finaldocu is approved, allow viewing
            $response['hasReviewed'] = true;
            $response['message'] = 'Final document approved by adviser';
        } else {
            // If finaldocu is not approved, check if finaldocu has any adviser review (even pending)
            $check_finaldocu_review = $conn->prepare("SELECT COUNT(*) as count FROM proposal_monitoring_form 
                                            WHERE finaldocu_id = ? AND adviser_id IS NOT NULL");
            $check_finaldocu_review->bind_param("i", $finaldocu_id);
            $check_finaldocu_review->execute();
            $finaldocu_review_result = $check_finaldocu_review->get_result();
            $finaldocu_has_review = ($finaldocu_review_result && $finaldocu_review_result->fetch_assoc()['count'] > 0);
            $check_finaldocu_review->close();
            
            if ($finaldocu_has_review) {
                // If adviser has reviewed finaldocu but not approved it, panel can't view
                $response['hasReviewed'] = false;
                $response['message'] = 'Final document has been reviewed by adviser but not yet approved';
            } else {
                // If no finaldocu review exists, check if route3 was approved (not just reviewed)
                $get_route3 = $conn->prepare("SELECT route3_id FROM route3proposal_files WHERE student_id = ?");
                $get_route3->bind_param("s", $student_id);
                $get_route3->execute();
                $route3_result = $get_route3->get_result();
                $route3_id = null;
                
                if ($route3_result && $route3_row = $route3_result->fetch_assoc()) {
                    $route3_id = $route3_row['route3_id'];
                }
                $get_route3->close();
                
                if ($route3_id) {
                    $check_route3 = $conn->prepare("SELECT COUNT(*) as count FROM proposal_monitoring_form 
                                                  WHERE route3_id = ? AND adviser_id IS NOT NULL 
                                                  AND (status = 'Approved' OR status = 'approved')");
                    $check_route3->bind_param("i", $route3_id);
                    $check_route3->execute();
                    $route3_result = $check_route3->get_result();
                    $route3_approved = ($route3_result && $route3_result->fetch_assoc()['count'] > 0);
                    $check_route3->close();
                    
                    if ($route3_approved) {
                        $response['hasReviewed'] = true;
                        $response['message'] = 'Route 3 document was approved by adviser';
                    } else {
                        $response['hasReviewed'] = false;
                        $response['message'] = 'Adviser must approve Route 3 or Final Document before panel members can view this document';
                    }
                } else {
                    $response['hasReviewed'] = false;
                    $response['message'] = 'Route 3 document not found. Adviser must review Final Document first';
                }
            }
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 