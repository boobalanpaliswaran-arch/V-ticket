<?php
// ======================================================
// Secure session setup
// ======================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
session_start();
session_regenerate_id(true);

// Redirect if not logged in
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
    die("❌ Database Connection Failed: " . $e->getMessage());
}

// Helper function
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }

// ======================================================
// Fetch customer list for filter dropdown
// ======================================================
$customers = [];
try {
    $res = $conn->query("SELECT DISTINCT customer_name FROM servicetickets ORDER BY customer_name ASC");
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $customers[] = $row['customer_name'];
    }
} catch (PDOException $e) {
    $customers = [];
}

// ======================================================
// Build filters
// ======================================================
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

// ======================================================
// Fetch data from servicetickets
// ======================================================
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>VSMART Ticket Report</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
body {
  font-family: "Segoe UI", sans-serif;
  background: #f4f6f9;
  margin: 0;
}
header {
  background: #2c3e50;
  color: white;
  padding: 12px 25px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
header img { height: 45px; border-radius: 8px; margin-right: 10px; }
header strong { font-size: 18px; color: #ecf0f1; }
nav a {
  color: white; margin-left: 15px; text-decoration: none; font-weight: bold;
}
nav a:hover { text-decoration: underline; }

main { padding: 25px 40px; }
h2 { color: #2c3e50; text-align: center; margin-bottom: 25px; }

/* Filters */
.filters {
  background: white; padding: 15px; border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;
}
.filters label { font-weight: 600; }
.filters input, .filters select {
  padding: 8px; border-radius: 6px; border: 1px solid #ccc; min-width: 150px;
}
.filters button {
  padding: 8px 14px; border: none; border-radius: 6px; font-weight: 600;
}
.btn-primary { background-color: #2980b9; color: #fff; }
.btn-secondary { background-color: #bdc3c7; }
.btn-success { background-color: #27ae60; color: #fff; }
.btn-danger { background-color: #e74c3c; color: #fff; }

/* Table */
.table-wrapper {
  background: white; padding: 20px; border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}
.status-open { color: #27ae60; font-weight: bold; }
.status-closed { color: #e74c3c; font-weight: bold; }
.attachment-link {
  background: #2980b9; color: white; padding: 4px 10px; border-radius: 4px;
  text-decoration: none; font-size: 12px;
}
.attachment-link:hover { background: #1f6391; }

footer {
  text-align: center; padding: 15px; background: #2c3e50; color: #ecf0f1;
  margin-top: 30px;
}
@media print {
  header, form, footer, .btn { display: none; }
  body { background: white; }
  table { font-size: 12px; }
}
</style>
</head>
<body>
<header>
  <div style="display:flex;align-items:center;">
    <img src="VSP001.png" alt="Logo">
    <strong>VSMART TICKET REPORT</strong>
  </div>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="CreateNewTicket.php">Create Ticket</a>
    <a href="TicketClosed.php">My Tickets</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main>
  <h2>📊 Service Ticket Report</h2>

  <!-- Filter Form -->
  <form method="get" class="filters">
    <label>Status:</label>
    <select name="status">
      <option value="">-- All --</option>
      <option value="Open" <?=isset($_GET['status']) && $_GET['status']=='Open'?'selected':''?>>Open</option>
      <option value="Closed" <?=isset($_GET['status']) && $_GET['status']=='Closed'?'selected':''?>>Closed</option>
    </select>

    <label>From:</label>
    <input type="date" name="from" value="<?=e($_GET['from'] ?? '')?>">
    <label>To:</label>
    <input type="date" name="to" value="<?=e($_GET['to'] ?? '')?>">

    <label>Customer:</label>
    <input list="customerList" name="customer_name" value="<?=e($_GET['customer_name'] ?? '')?>" placeholder="Enter customer">
    <datalist id="customerList">
      <?php foreach($customers as $cust): ?>
        <option value="<?=e($cust)?>"></option>
      <?php endforeach; ?>
    </datalist>

    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="TicketReport.php" class="btn btn-secondary">Reset</a>
    <button type="button" class="btn btn-success" onclick="window.print()">🖨️ Print</button>
  </form>

  <!-- Ticket Table -->
  <div class="table-wrapper mt-4">
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
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
      </thead>
      <tbody>
        <?php if(count($tickets) > 0): ?>
          <?php foreach($tickets as $t): ?>
          <tr>
            <td><?=e($t['ticket_code'])?></td>
            <td><?=e(date("d-m-Y", strtotime($t['open_date'])))?></td>
            <td><?=e($t['title'])?></td>
            <td><?=e($t['customer_name'])?></td>
            <td class="<?=strtolower($t['status'])=='open'?'status-open':'status-closed'?>"><?=e($t['status'])?></td>
            <td><?=e($t['assigned'])?></td>
            <td><?=!empty($t['closed_date']) ? e(date("d-m-Y", strtotime($t['closed_date']))) : '-'?></td>
            <td><?=e($t['action_taken'] ?: '-')?></td>
            <td>
              <?php if(!empty($t['attachment'])): ?>
                <a href="<?=e($t['attachment'])?>" target="_blank" class="attachment-link">View</a>
              <?php else: ?> - <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9" class="text-center text-muted">No records found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="text-end mt-3">
    <form action="export_ticket_excel.php" method="post" style="display:inline;">
      <button type="submit" class="btn btn-success">Export to Excel</button>
    </form>
    <form action="export_ticket_pdf.php" method="post" style="display:inline;">
      <button type="submit" class="btn btn-danger">Export to PDF</button>
    </form>
  </div>
</main>

<footer>
  © <?=date("Y")?> Vsmart Technologies — All Rights Reserved
</footer>
</body>
</html>
