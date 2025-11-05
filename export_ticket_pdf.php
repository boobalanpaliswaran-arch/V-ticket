<?php
// ======================================================
// Secure Session
// ======================================================
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ======================================================
// Database Connection (SQL Server PDO)
// ======================================================
$serverName = "BOOBALAN\\SQLEXPRESS";
$dbName     = "vsmart";
$dbUser     = "sa";
$dbPass     = "admin@123";

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// ======================================================
// Fetch tickets (same filters as report)
$where = [];
$params = [];

if (!empty($_GET['status'])) {
    $where[] = "LOWER(Call_status) = LOWER(:status)";
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['from'])) {
    $where[] = "open_date >= :from";
    $params[':from'] = $_GET['from'];
}
if (!empty($_GET['to'])) {
    $where[] = "open_date <= :to";
    $params[':to'] = $_GET['to'];
}
if (!empty($_GET['customer_name'])) {
    $where[] = "customer_name LIKE :customer";
    $params[':customer'] = "%" . $_GET['customer_name'] . "%";
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT 
            ticket_code, open_date, title, customer_name,
            Call_status AS status, assigned, ClosedDateTime AS closed_date,
            Action_Taken AS action_taken, attached_file AS attachment
        FROM servicetickets
        $whereSQL
        ORDER BY open_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================================================
// Load TCPDF library
// ======================================================
require_once 'vendor/autoload.php';
use TCPDF;

// Create new PDF
$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Vsmart');
$pdf->SetAuthor('Vsmart');
$pdf->SetTitle('Service Ticket Report');
$pdf->SetHeaderData('', 0, 'Service Ticket Report', '');
$pdf->setHeaderFont(Array('helvetica', '', 12));
$pdf->setFooterFont(Array('helvetica', '', 10));
$pdf->SetMargins(10, 20, 10);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();

// ======================================================
// Table HTML
// ======================================================
$html = '<h2 style="text-align:center;">Service Ticket Report</h2>';
$html .= '<table border="1" cellpadding="4">';
$html .= '<thead>
<tr style="background-color:#2c3e50;color:white;">
<th>Ticket Code</th>
<th>Open Date</th>
<th>Title</th>
<th>Customer</th>
<th>Status</th>
<th>Assigned</th>
<th>Closed Date</th>
<th>Action Taken</th>
<th>Attachment</th>
</tr>
</thead><tbody>';

if(count($tickets) > 0){
    foreach($tickets as $t){
        $statusColor = strtolower($t['status'])=='open' ? 'green' : 'red';
        $html .= '<tr>
            <td>'.htmlspecialchars($t['ticket_code']).'</td>
            <td>'.htmlspecialchars(date("d-m-Y", strtotime($t['open_date']))).'</td>
            <td>'.htmlspecialchars($t['title']).'</td>
            <td>'.htmlspecialchars($t['customer_name']).'</td>
            <td style="color:'.$statusColor.'">'.htmlspecialchars($t['status']).'</td>
            <td>'.htmlspecialchars($t['assigned']).'</td>
            <td>'.(!empty($t['closed_date']) ? htmlspecialchars(date("d-m-Y", strtotime($t['closed_date']))) : '-').'</td>
            <td>'.htmlspecialchars($t['action_taken'] ?? '-').'</td>
            <td>'.(!empty($t['attachment']) ? 'Yes' : '-').'</td>
        </tr>';
    }
}else{
    $html .= '<tr><td colspan="9" align="center">No records found</td></tr>';
}

$html .= '</tbody></table>';

// Write HTML to PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Output PDF
$pdf->Output('Service_Ticket_Report.pdf', 'I');
