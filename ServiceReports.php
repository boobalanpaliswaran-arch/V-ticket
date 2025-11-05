<?php
// ==================================================
// Secure Session Setup
// ==================================================
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

// ==================================================
// Database Connection (SQL Server PDO)
// ==================================================
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

// Helper
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ==================================================
// Fetch Customers for Dropdown
// ==================================================
$customerOptions = [];
try {
    $stmt = $conn->query("SELECT DISTINCT customer_name FROM servicetickets ORDER BY customer_name ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $customerOptions[] = $row['customer_name'];
    }
} catch (PDOException $e) {
    $customerOptions = [];
}

// ==================================================
// Filters (Status, Customer, Month-Year Range)
// ==================================================
$where = [];
$params = [];

// Status Filter
if (!empty($_GET['status'])) {
    $where[] = "LOWER(Call_status) = LOWER(:status)";
    $params[':status'] = $_GET['status'];
}

// Customer Filter
if (!empty($_GET['customer_name'])) {
    $where[] = "customer_name LIKE :customer_name";
    $params[':customer_name'] = '%' . $_GET['customer_name'] . '%';
}

// Month-Year Filter
if (!empty($_GET['from_month']) && !empty($_GET['from_year'])) {
    $fromMonth = str_pad($_GET['from_month'], 2, "0", STR_PAD_LEFT);
    $fromYear = $_GET['from_year'];
    $fromDate = "$fromYear-$fromMonth-01";
    $where[] = "open_date >= :from_date";
    $params[':from_date'] = $fromDate;
}

if (!empty($_GET['to_month']) && !empty($_GET['to_year'])) {
    $toMonth = str_pad($_GET['to_month'], 2, "0", STR_PAD_LEFT);
    $toYear = $_GET['to_year'];
    $toDate = date("Y-m-t", strtotime("$toYear-$toMonth-01"));
    $where[] = "open_date <= :to_date";
    $params[':to_date'] = $toDate;
}

$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// ==================================================
// Final Query (Added service_type column)
// ==================================================
$sql = "SELECT 
            ticket_code,
            open_date,
            title,
            customer_name,
            service_type,
            Call_status AS status,
            assigned,
            ClosedDateTime AS closed_date,
            Action_Taken AS action_taken,
            attached_file AS attachment
        FROM servicetickets
        $whereSQL
        ORDER BY open_date DESC";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vsmart Ticket Report</title>
<style>
body { font-family: "Segoe UI", Arial, sans-serif; background: #f4f6f9; margin: 0; }
header { background: #2c3e50; color: white; padding: 12px 25px; display: flex; justify-content: space-between; align-items: center; }
header img { height: 45px; border-radius: 8px; margin-right: 10px; vertical-align: middle; }
header strong { font-size: 18px; color: #ecf0f1; }
nav a { color: white; margin-left: 15px; text-decoration: none; font-weight: bold; }
main { padding: 25px 40px; }
form.filters { background: white; padding: 15px 20px; border-radius: 10px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: center; margin-bottom: 25px; }
form input, form select { padding: 8px 10px; border-radius: 6px; border: 1px solid #ccc; min-width: 140px; }
form button { padding: 8px 14px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
.print-btn { background-color: #27ae60; color: white; }
.table-wrapper { background: white; padding: 15px; border-radius: 10px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px; border: 1px solid #ddd; text-align: left; font-size: 14px; }
th { background: #2c3e50; color: white; text-transform: uppercase; }
.status-open { color: #27ae60; font-weight: bold; }
.status-closed { color: #e74c3c; font-weight: bold; }
footer { text-align: center; padding: 15px; background: #2c3e50; color: #ecf0f1; margin-top: 30px; }
</style>
</head>
<body>
<header>
  <div style="display:flex; align-items:center;">
    <img src="VSP001.png" alt="Logo">
    <strong>VSMART TICKET MANAGEMENT</strong>
  </div>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="CreateNewTicket.php">Create Ticket</a>
    <a href="TicketClosed.php">My Tickets</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main>
  <h2>📊 Ticket Report</h2>
  <form method="get" class="filters">
    <label>Status:</label>
    <select name="status">
      <option value="">All</option>
      <option value="Open" <?=isset($_GET['status']) && $_GET['status']=='Open'?'selected':''?>>Open</option>
      <option value="Closed" <?=isset($_GET['status']) && $_GET['status']=='Closed'?'selected':''?>>Closed</option>
    </select>
    <label>From:</label>
    <select name="from_month">
      <option value="">Month</option>
      <?php for($m=1;$m<=12;$m++): ?>
        <option value="<?=$m?>" <?=isset($_GET['from_month']) && $_GET['from_month']==$m?'selected':''?>><?=date("F", mktime(0,0,0,$m,1))?></option>
      <?php endfor; ?>
    </select>
    <select name="from_year">
      <option value="">Year</option>
      <?php for($y=2023;$y<=date("Y");$y++): ?>
        <option value="<?=$y?>" <?=isset($_GET['from_year']) && $_GET['from_year']==$y?'selected':''?>><?=$y?></option>
      <?php endfor; ?>
    </select>
    <label>To:</label>
    <select name="to_month">
      <option value="">Month</option>
      <?php for($m=1;$m<=12;$m++): ?>
        <option value="<?=$m?>" <?=isset($_GET['to_month']) && $_GET['to_month']==$m?'selected':''?>><?=date("F", mktime(0,0,0,$m,1))?></option>
      <?php endfor; ?>
    </select>
    <select name="to_year">
      <option value="">Year</option>
      <?php for($y=2023;$y<=date("Y");$y++): ?>
        <option value="<?=$y?>" <?=isset($_GET['to_year']) && $_GET['to_year']==$y?'selected':''?>><?=$y?></option>
      <?php endfor; ?>
    </select>
    <label>Customer:</label>
    <input list="customerList" name="customer_name" placeholder="Enter name" value="<?=e($_GET['customer_name'] ?? '')?>">
    <datalist id="customerList">
      <?php foreach($customerOptions as $cust): ?>
        <option value="<?=e($cust)?>"></option>
      <?php endforeach; ?>
    </datalist>

    <button type="submit">Filter</button>
    <a href="ServiceReports.php" class="reset-btn">Reset</a>
    <button type="button" class="print-btn" onclick="openPrint()">🖨️ Print Report</button>
  </form>

  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Ticket Code</th>
          <th>Open Date</th>
          <th>Title</th>
          <th>Customer</th>
          <th>Service Type</th>
          <th>Status</th>
          <th>Assigned</th>
          <th>Closed Date</th>
          <th>Action Taken</th>
        </tr>
      </thead>
      <tbody>
        <?php if(count($rows) > 0): ?>
          <?php foreach($rows as $row): ?>
            <tr>
              <td><?=e($row['ticket_code'])?></td>
              <td><?=e(date("d-m-Y", strtotime($row['open_date'])))?></td>
              <td><?=e($row['title'])?></td>
              <td><?=e($row['customer_name'])?></td>
              <td><?=e($row['service_type'] ?? '-')?></td>
              <td class="<?=strtolower($row['status'])=='open'?'status-open':'status-closed'?>"><?=e($row['status'])?></td>
              <td><?=e($row['assigned'])?></td>
              <td><?=!empty($row['closed_date']) ? e(date("d-m-Y", strtotime($row['closed_date']))) : '-'?></td>
              <td><?=e($row['action_taken'] ?? '-')?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9" style="text-align:center;">No records found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
<footer>© <?=date("Y")?> Vsmart Technologies — All Rights Reserved</footer>

<script>
function openPrint() {
  const params = new URLSearchParams(window.location.search);
  window.open('ServiceReports_Print.php?' + params.toString(), '_blank');
}
</script>
</body>
</html>
