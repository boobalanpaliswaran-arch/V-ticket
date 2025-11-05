<?php
// =======================================
// Secure Session + DB Connection
// =======================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();
session_regenerate_id(true);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection (SQL Server PDO)
$serverName = "BOOBALAN\\SQLEXPRESS";
$dbName     = "vsmart";
$dbUser     = "sa";
$dbPass     = "admin@123";

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ Database Connection Failed: " . $e->getMessage());
}

// =======================================
// FILTERS
// =======================================
$where = [];
$params = [];

// Normalize date formats to prevent display errors
$fromDateDisplay = '';
$toDateDisplay   = '';

if (!empty($_GET['status'])) {
    $where[] = "LOWER(Call_status) = LOWER(:status)";
    $params[':status'] = $_GET['status'];
}

if (!empty($_GET['customer_name'])) {
    $where[] = "customer_name LIKE :customer_name";
    $params[':customer_name'] = '%' . $_GET['customer_name'] . '%';
}

if (!empty($_GET['service_type'])) {
    $where[] = "service_type LIKE :service_type";
    $params[':service_type'] = '%' . $_GET['service_type'] . '%';
}

if (!empty($_GET['from_date'])) {
    $from = date('Y-m-d', strtotime($_GET['from_date']));
    $where[] = "CAST(open_date AS DATE) >= :from_date";
    $params[':from_date'] = $from;
    $fromDateDisplay = date('d-m-Y', strtotime($_GET['from_date']));
}

if (!empty($_GET['to_date'])) {
    $to = date('Y-m-d', strtotime($_GET['to_date']));
    $where[] = "CAST(open_date AS DATE) <= :to_date";
    $params[':to_date'] = $to;
    $toDateDisplay = date('d-m-Y', strtotime($_GET['to_date']));
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// =======================================
// FETCH TICKETS
// =======================================
$sql = "SELECT 
            ticket_code, open_date, title, customer_name, 
            service_type, Call_status AS status, assigned, 
            ClosedDateTime AS closed_date, Action_Taken AS action_taken
        FROM servicetickets
        $whereSQL
        ORDER BY open_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vsmart - Printable Ticket Report</title>
<style>
@page { size: A4 landscape; margin: 15mm; }
body {
  font-family: 'Segoe UI', Arial, sans-serif;
  background: white; color: #222;
  margin: 0; padding: 20px;
}
header {
  display: flex; align-items: center; justify-content: space-between;
  border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px;
}
header img { height: 60px; }
header .company { text-align: right; }
header .company h1 { margin: 0; font-size: 22px; color: #2c3e50; }
header .company p { margin: 0; font-size: 13px; color: #555; }

.report-title {
  text-align: center; font-size: 20px; font-weight: bold;
  margin: 10px 0 5px 0; color: #2c3e50; text-transform: uppercase;
}
.filters {
  font-size: 13px; margin-bottom: 10px;
  background: #f8f9fa; padding: 10px; border-radius: 5px; border: 1px solid #ddd;
}
.filters span { margin-right: 15px; }

table {
  width: 100%; border-collapse: collapse;
  margin-top: 10px; table-layout: fixed;
}
th, td {
  border: 1px solid #ccc; padding: 6px;
  font-size: 12px; word-wrap: break-word;
}
th {
  background: #2c3e50; color: white; text-align: left;
}
tr:nth-child(even) { background: #f9f9f9; }
.status-open { color: #27ae60; font-weight: bold; }
.status-closed { color: #e74c3c; font-weight: bold; }

footer {
  position: fixed; bottom: 10px; left: 0; right: 0;
  text-align: center; font-size: 12px; color: #888;
}
.print-btn { text-align: right; margin-bottom: 10px; }
.print-btn button {
  background: #2980b9; color: white; border: none;
  padding: 8px 15px; border-radius: 6px; cursor: pointer;
}
.print-btn button:hover { background: #1f6391; }
@media print { .print-btn { display: none; } }
</style>
</head>
<body>
<div class="print-btn">
  <button onclick="window.print()">🖨️ Print Landscape Report</button>
</div>

<header>
  <div><img src="VSP001.png" alt="Logo"></div>
  <div class="company">
    <h1>VSMART TICKET MANAGEMENT</h1>
    <p>Service Ticket Report</p>
    <p><?= date('d M Y, h:i A'); ?></p>
  </div>
</header>

<div class="report-title">Customer Service Report</div>

<div class="filters">
  <strong>Filters:</strong>
  <span>Status: <?= !empty($_GET['status']) ? htmlspecialchars($_GET['status']) : 'All' ?></span>
  <span>Customer: <?= !empty($_GET['customer_name']) ? htmlspecialchars($_GET['customer_name']) : 'All' ?></span>
  <span>Service Type: <?= !empty($_GET['service_type']) ? htmlspecialchars($_GET['service_type']) : 'All' ?></span>
  <span>From: <?= !empty($fromDateDisplay) ? $fromDateDisplay : '—' ?></span>
  <span>To: <?= !empty($toDateDisplay) ? $toDateDisplay : '—' ?></span>
</div>

<table>
  <thead>
    <tr>
      <th style="width:3%;">#</th>
      <th style="width:8%;">Ticket Code</th>
      <th style="width:10%;">Open Date</th>
      <th style="width:15%;">Title</th>
      <th style="width:12%;">Customer</th>
      <th style="width:10%;">Service Type</th>
      <th style="width:8%;">Status</th>
      <th style="width:10%;">Assigned</th>
      <th style="width:10%;">Closed Date</th>
      <th style="width:14%;">Action Taken</th>
    </tr>
  </thead>
  <tbody>
    <?php if (count($tickets) > 0): $i=1; ?>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><?= htmlspecialchars($t['ticket_code']) ?></td>
          <td><?= date('d-m-Y', strtotime($t['open_date'])) ?></td>
          <td><?= htmlspecialchars($t['title']) ?></td>
          <td><?= htmlspecialchars($t['customer_name']) ?></td>
          <td><?= htmlspecialchars($t['service_type']) ?></td>
          <td class="<?= strtolower($t['status'])=='open'?'status-open':'status-closed' ?>">
            <?= htmlspecialchars($t['status']) ?>
          </td>
          <td><?= htmlspecialchars($t['assigned']) ?></td>
          <td><?= !empty($t['closed_date']) ? date('d-m-Y', strtotime($t['closed_date'])) : '-' ?></td>
          <td><?= htmlspecialchars($t['action_taken']) ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="10" style="text-align:center; color:#888;">No records found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<footer>
  © <?= date('Y'); ?> Vsmart Service Report | Auto-generated
</footer>
</body>
</html>
