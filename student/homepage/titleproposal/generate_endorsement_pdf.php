<?php
require '../../../vendor/autoload.php';

use Fpdf\Fpdf;

// Adjust the path to where you have FPDF stored
$adviserName = $_GET['adviserName'] ?? 'Unknown Adviser';
$studentNames = isset($_GET['student']) ? explode(',', $_GET['student']) : [];
$studentNames = array_map('trim', $studentNames); // remove extra spaces



  // Default empty array if not provided

$date = date('F j, Y'); // Current date

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();

// Set Font (you must set a font before using any text or cells)
$pdf->SetFont('Arial', '', 10); // Set Arial font, normal weight, size 12

// Logos

// Adjust position to avoid overlap with logos
$pdf->SetXY(10, 1); // Start text below the logos

// Header Text
$pdf->Cell(190, 10, 'Saint Michael College of Caraga', 0, 1, 'C');
$pdf->Cell(190, 7, 'Brgy. 4, Nasipit, Agusan del Norte, Philippines', 0, 1, 'C');
$pdf->Cell(190, 7, 'Tel. Nos. +63 085 343-3251 / +63 085 283-3113', 0, 1, 'C');
$pdf->Cell(190, 7, 'Fax No. +63 085 808-0892', 0, 1, 'C');
$pdf->Cell(190, 7, 'www.smccnasipit.edu.ph', 0, 1, 'C');
$pdf->Ln(20); // Space after header

// Title
$pdf->SetFont('Arial', 'B', 16); // Set bold Arial font for title
$pdf->Cell(0, 10, 'CERTIFICATE OF ENDORSEMENT', 0, 1, 'C');
$pdf->Ln(10); // Add space after title

// Body Text
$pdf->SetFont('Arial', '', 12); // Reset to normal Arial font
$body = "This is to certify that the following researchers have successfully completed a thorough checking and assessment of their software system and manuscript under my supervision. Therefore, I, $adviserName, as their Capstone/Thesis Adviser, hereby endorse them to proceed with their Final Oral Defense for the completion of their Capstone Project/Thesis in the degree of Bachelor of Science in Information Technology.";
$pdf->MultiCell(0, 8, $body);
$pdf->Ln(5); // Add space after body text

// Student List
$pdf->SetFont('Arial', 'B', 12); // Set bold Arial font for student list
$pdf->Cell(0, 8, 'Researchers:', 0, 1);
$pdf->SetFont('Arial', '', 12); // Reset to normal Arial font
foreach ($studentNames as $index => $name) {
    $pdf->Cell(0, 8, ($index + 1) . ". " . $name, 0, 1);
}
$pdf->Ln(5); // Add space after student list

// Additional Body Text
$body2 = "Their project/thesis has met the required standards and criteria set forth by the College of Computing and Information Sciences, and I am confident in the quality and academic rigor of their work.";
$pdf->MultiCell(0, 8, $body2);
$pdf->Ln(20); // Add space after second body text

// Adviser Signature
$pdf->SetFont('Arial', 'B', 12); // Set bold Arial font for signature
$pdf->Cell(0, 8, 'Endorsed by:', 0, 1);
$pdf->Cell(80, 8, $adviserName, 'B', 1); // Signature line
$pdf->SetFont('Arial', '', 12); // Reset to normal Arial font
$pdf->Cell(80, 8, 'Capstone Adviser', 0, 1);
$pdf->Cell(80, 8, $date, 0, 1);
$pdf->Ln(20); // Add space after adviser signature

// Instructor Approval
$pdf->SetFont('Arial', 'B', 12); // Set bold Arial font for instructor approval
$pdf->Cell(0, 8, 'Approved by:', 0, 1);
$pdf->Cell(0, 8, 'MARLON JUHN M. TIMOGAN, MIT', 0, 1); // Instructor name
$pdf->SetFont('Arial', '', 12); // Reset to normal Arial font
$pdf->Cell(0, 8, 'Capstone Project/Thesis Instructor', 0, 1);
$pdf->Cell(0, 8, $date, 0, 1);

// Output PDF for download
$pdf->Output('D', 'Certificate_of_Endorsement.pdf'); // D = force download
?>
