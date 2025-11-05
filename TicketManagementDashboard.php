<?php
// =========================
// Secure session + CSRF
// =========================
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

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}

// =========================
// DB connection (MSSQL PDO)
// =========================
$serverName = "BOOBALAN\\SQLEXPRESS";
$dbName     = "vsmart";
$dbUser     = "sa";
$dbPass     = "admin@123";

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$dbName", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("⚠️ DB Connection failed: " . $e->getMessage());
}

// =========================
// Columns mapping
// =========================
$pk          = 'ticket_code';
$dateCol     = 'open_date';
$titleCol    = 'title';
$descCol     = 'customer_Description';
$customerCol = 'customer_name';
$statusCol   = 'Call_status';
$assignedCol = 'assigned';
$closedCol   = 'ClosedDateTime';
$actionCol   = 'action_taken';

// =========================
// Filters
// =========================
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$whereClause = 'WHERE 1=1';

if ($filter === 'open') {
    $whereClause .= " AND [$statusCol] = 'Open'";
} elseif ($filter === 'closed') {
    $whereClause .= " AND [$statusCol] = 'Closed'";
}

// =========================
// Build SQL
// =========================
$sqlTickets = "SELECT TOP 100
    [$pk] AS [ticket_code],
    [$dateCol] AS [date],
    [$titleCol] AS [title],
    [$descCol] AS [description],
    [$customerCol] AS [customer_Name],
    [$statusCol] AS [status],
    [$assignedCol] AS [assigned],
    [$closedCol] AS [ClosedDateTime],
    CAST(NULL AS NVARCHAR(MAX)) AS [action_taken]
FROM servicetickets
$whereClause
";

// Add search
$params = [];
if ($search !== '') {
    $sqlTickets .= " AND (
        [$pk] LIKE :search1 OR
        [$titleCol] LIKE :search2 OR
        [$customerCol] LIKE :search3 OR
        [$statusCol] LIKE :search4
    )";
    $params = [
        ':search1' => "%$search%",
        ':search2' => "%$search%",
        ':search3' => "%$search%",
        ':search4' => "%$search%",
    ];
}

$sqlTickets .= " ORDER BY [$pk] DESC";

// =========================
// Execute
// =========================
$stmt = $conn->prepare($sqlTickets);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// =========================
// Stats
// =========================
$stats=[];
$resStats=$conn->query("SELECT Call_status AS status, COUNT(*) AS c FROM servicetickets GROUP BY Call_status");
while($r=$resStats->fetch()) {
    $stats[$r['status']??'Unknown']=(int)$r['c'];
}
$totalTickets=$conn->query("SELECT COUNT(*) AS c FROM servicetickets")->fetch()['c']??0;

// Escaper
function e($s){ return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - Ticket Management</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; color: #2c3e50; margin: 0; }
header { display: flex; align-items: center; justify-content: space-between; background: #2c3e50; padding: 15px 25px; color: #ecf0f1; box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
header h1 { font-size: 20px; font-weight: 600; margin: 0; }
nav a, nav form button { color: #ecf0f1; text-decoration: none; margin: 0 10px; font-weight: 500; transition: all 0.3s; border: none; background: none; cursor: pointer; }
nav a:hover, nav form button:hover { color: #f1c40f; }
main { padding: 25px; }
h2 { text-align: center; color: #2c3e50; font-size: 22px; }
.cards { display: flex; flex-wrap: wrap; gap: 15px; margin: 20px 0; }
.card { flex: 1; min-width: 150px; background: #fff; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease; }
.card:hover { transform: translateY(-4px); }
.card h3 { margin: 0; color: #7f8c8d; font-size: 14px; }
.card p { margin: 8px 0 0; font-size: 20px; font-weight: bold; color: #2c3e50; }
.table-container { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 3px 10px rgba(0,0,0,0.1); overflow-x: auto; }
.filter-bar { text-align: right; margin-bottom: 15px; }
.filter-bar input, .filter-bar select { padding: 6px 10px; border-radius: 6px; border: 1px solid #ccc; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
th { background: #34495e; color: #fff; position: sticky; top: 0; }
tr:hover { background: #f1f7ff; }
.badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
.badge.Open { background: #d4efdf; color: #1e8449; }
.badge.Closed { background: #f9e0e0; color: #c0392b; }
.badge.Unknown { background: #ecf0f1; color: #555; }
</style>
</head>
<body>
<header>
  <h1>Vsmart Dashboard</h1>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="CreateNewTicket.php">Ticket</a>
    <a href="Newcustomer.php">Customer</a>
    <?php if($_SESSION['role']==='admin'): ?><a href="User.php">User</a><?php endif; ?>
    <a href="employee.php">Employee</a>
    <a href="Warranty.php">Warranty</a>
    <form action="logout.php" method="POST" style="display:inline;">
      <?= csrf_field(); ?>
      <button type="submit">Logout</button>
    </form>
  </nav>
</header>

<main>
<h2>Welcome, <?=e($_SESSION['username']??'User')?> (<?=e($_SESSION['role']??'')?>)</h2>

<div class="cards">
  <div class="card"><h3>Open Tickets</h3><p><?=$stats['Open']??0?></p></div>
  <div class="card"><h3>Closed Tickets</h3><p><?=$stats['Closed']??0?></p></div>
  <div class="card"><h3>Total Tickets</h3><p><?=$totalTickets?></p></div>
</div>

<div class="table-container">
  <div class="filter-bar">
    <form method="get">
      <label>Search: </label>
      <input type="text" name="search" value="<?=e($search)?>" placeholder="Search tickets...">
      <label>Show: </label>
      <select name="filter">
        <option value="all" <?= $filter==='all'?'selected':'' ?>>All</option>
        <option value="open" <?= $filter==='open'?'selected':'' ?>>Open</option>
        <option value="closed" <?= $filter==='closed'?'selected':'' ?>>Closed</option>
      </select>
      <button type="submit">🔍</button>
    </form>
  </div>

  <h3>Recent Tickets (<?=ucfirst($filter)?>)</h3>
  <table>
    <thead>
      <tr>
        <th>Ticket Code</th>
        <th>Open Date</th>
        <th>Title</th>
        <th>Description</th>
        <th>Customer</th>
        <th>Status</th>
        <th>Assigned</th>
        <th>Closed Date</th>
        <th>Action Taken</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($tickets as $row): ?>
        <tr>
          <td><?=e($row['ticket_code'])?></td>
          <td><?= $row['date'] ? e(date("d-m-Y",strtotime($row['date']))) : '' ?></td>
          <td><?=e(mb_strimwidth($row['title']??'',0,60,'...'))?></td>
          <td><?=e(mb_strimwidth($row['description']??'',0,100,'...'))?></td>
          <td><?=e($row['customer_Name']??'')?></td>
          <td><span class="badge <?=e($row['status']??'Unknown')?>"><?=e($row['status']??'Unknown')?></span></td>
          <td><?=e($row['assigned']??'')?></td>
          <td><?= $row['ClosedDateTime'] ? e(date("d-m-Y",strtotime($row['ClosedDateTime']))) : '' ?></td>
          <td><?=e(mb_strimwidth($row['action_taken']??'',0,100,'...'))?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
</main>
</body>
</html>
