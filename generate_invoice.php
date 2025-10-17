<?php
require('fpdf/fpdf.php');

$conn = new mysqli('localhost', 'root', '', 'g14');
if ($conn->connect_error) die("DB Connection Failed");

$sale_id = $_GET['sale_id'] ?? 0;
if (!$sale_id) die("Invalid sale ID.");

// Fetch sale
$sale = $conn->query("SELECT * FROM sales_tbl WHERE sale_id = $sale_id")->fetch_assoc();
$items = $conn->query("
    SELECT si.*, p.product_name 
    FROM sales_items_tbl si 
    JOIN products_tbl p ON si.product_id = p.product_id 
    WHERE si.sale_id = $sale_id
");

// Custom thermal receipt width (58mm)
class ThermalPDF extends FPDF {
    function Header() {}
    function Footer() {}
}

$pdf = new ThermalPDF('P', 'mm', array(58, 200)); // 58mm width, variable height
$pdf->AddPage();
$pdf->SetMargins(4, 4, 4);
$pdf->SetAutoPageBreak(true, 4);

// --- HEADER ---
$pdf->Image('Black_logo.png', 20, 2, 18);
$pdf->Ln(20);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, 'G14 GYM STORE', 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 4, 'Official Sales Receipt', 0, 1, 'C');
$pdf->Cell(0, 4, 'Contact: 0912-345-6789', 0, 1, 'C');
$pdf->Cell(0, 4, 'Address: Brgy. XYZ, City, PH', 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 4, 'Sale ID: ' . $sale['sale_id'], 0, 1, 'L');
$pdf->Cell(0, 4, 'Date: ' . $sale['sale_date'], 0, 1, 'L');
$pdf->Ln(2);
$pdf->Cell(0, 0, str_repeat('-', 32), 0, 1, 'C');
$pdf->Ln(1);

// --- ITEMS ---
$pdf->SetFont('Courier', '', 8);
$totalQty = 0;

while ($row = $items->fetch_assoc()) {
    $name = mb_strimwidth($row['product_name'], 0, 22, '…');
    $qty = $row['quantity'];
    $price = number_format($row['price'], 2);
    $lineTotal = number_format($row['quantity'] * $row['price'], 2);
    $totalQty += $qty;

    // Item line
    $pdf->Cell(0, 4, "$name", 0, 1, 'L');
    $pdf->Cell(25, 4, "  {$qty} x {$price}", 0, 0, 'L');
    $pdf->Cell(0, 4, $lineTotal, 0, 1, 'R');
}
$pdf->Ln(1);
$pdf->Cell(0, 0, str_repeat('-', 32), 0, 1, 'C');
$pdf->Ln(2);

// --- TOTALS ---
$pdf->SetFont('Courier', 'B', 9);
$pdf->Cell(28, 5, 'Subtotal:', 0, 0, 'R');
$pdf->Cell(0, 5, number_format($sale['subtotal'], 2), 0, 1, 'R');
$pdf->Cell(28, 5, 'Tax (10%):', 0, 0, 'R');
$pdf->Cell(0, 5, number_format($sale['tax'], 2), 0, 1, 'R');
$pdf->Cell(28, 5, 'TOTAL:', 0, 0, 'R');
$pdf->Cell(0, 5, '₱' . number_format($sale['total'], 2), 0, 1, 'R');
$pdf->Ln(3);

$pdf->SetFont('Courier', '', 8);
$pdf->Cell(0, 4, 'Items Sold: ' . $totalQty, 0, 1, 'L');

// --- FOOTER ---
$pdf->Ln(4);
$pdf->Cell(0, 0, str_repeat('-', 32), 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetFont('Arial', '', 8);
$pdf->MultiCell(0, 4, "Thank you for shopping with G14 Gym Store!\nNo returns without receipt.\nVisit us again!", 0, 'C');
$pdf->Ln(2);
$pdf->Cell(0, 4, '--- END OF RECEIPT ---', 0, 1, 'C');

// --- SAVE & DISPLAY ---
$filePath = __DIR__ . "/invoices/Receipt_{$sale_id}.pdf";
if (!file_exists(__DIR__ . "/invoices")) mkdir(__DIR__ . "/invoices", 0777, true);
$pdf->Output('F', $filePath);
$pdf->Output('I', 'Receipt_' . $sale_id . '.pdf');
?>
