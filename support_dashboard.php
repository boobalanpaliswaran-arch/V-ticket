<?php
// =====================================================
// Secure Session Start
// =====================================================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if HTTPS
ini_set('session.use_strict_mode', 1);

session_start();
session_regenerate_id(true);

// =====================================================
// Redirect if not logged in
// =====================================================
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// =====================================================
// Database Connection (SQL Server)
// =====================================================
$serverName = "BOOBALAN\\SQLEXPRESS";
$dbName     = "vsmart";
$dbUser     = "sa";
$dbPass     = "admin@123";

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$dbName", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("❌ DB Connection Failed: " . htmlspecialchars($e->getMessage()));
}

$username = $_SESSION['username'];

// =====================================================
// CSRF Token
// =====================================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =====================================================
// Close Ticket
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_ticket_code'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("⚠️ Invalid CSRF token. Please try again.");
    }

    $ticketCode = trim($_POST['close_ticket_code']);
    $closedAt   = date('Y-m-d H:i:s');

    try {
        $stmtClose = $conn->prepare("
            UPDATE servicetickets
            SET [Call_status] = 'Closed', [ClosedDateTime] = :closedAt
            WHERE [ticket_code] = :ticketCode
        ");
        $stmtClose->execute([
            ':closedAt'   => $closedAt,
            ':ticketCode' => $ticketCode
        ]);
        echo "<script>alert('✅ Ticket closed successfully'); window.location='" . $_SERVER['PHP_SELF'] . "';</script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>alert('❌ Update failed: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// =====================================================
// Dashboard Stats
// =====================================================
try {
    $stmtStats = $conn->prepare("
        SELECT 
            COUNT(*) AS total_tickets,
            SUM(CASE WHEN [Call_status] = 'Open' THEN 1 ELSE 0 END) AS open_tickets,
            SUM(CASE WHEN [Call_status] = 'Closed' THEN 1 ELSE 0 END) AS closed_tickets
        FROM servicetickets
        WHERE [assigned] = ?
    ");
    $stmtStats->execute([$username]);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Stats query failed: " . htmlspecialchars($e->getMessage()));
}

// =====================================================
// Ticket Search + Status Filter
// =====================================================
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$query = "
    SELECT 
        [ticket_code],
        [open_date],
        [ClosedDateTime],
        [title],
        [customer_Description],
        [customer_name],
        [customer_priority],
        [assigned],
        [servicecall],
        [Call_status],
        [service_type],
        [Action_Taken]
    FROM servicetickets
    WHERE [assigned] = :username
";

$params = [':username' => $username];

// Add status filter if selected
if ($statusFilter !== '' && in_array($statusFilter, ['Open', 'Closed'], true)) {
    $query .= " AND [Call_status] = :status";
    $params[':status'] = $statusFilter;
}

// Add search filter
if ($search !== '') {
    $query .= " AND (
        [ticket_code] LIKE :search OR
        [title] LIKE :search OR
        [customer_name] LIKE :search OR
        [Call_status] LIKE :search
    )";
    $params[':search'] = '%' . $search . '%';
}

$query .= " ORDER BY [open_date] DESC";

try {
    $stmtTickets = $conn->prepare($query);
    $stmtTickets->execute($params);
    $tickets = $stmtTickets->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("❌ Ticket Query Failed: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Support Dashboard - Vsmart</title>
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
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
header img {
  height: 45px;
  border-radius: 8px;
  margin-right: 10px;
}
header strong { font-size: 18px; color: #ecf0f1; }
nav a { color: white; margin-left: 15px; text-decoration: none; font-weight: bold; }
nav a:hover { text-decoration: underline; }
main { padding: 20px; }
h2 { color: #2c3e50; margin-bottom: 10px; }
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}
.card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  text-align: center;
  padding: 20px;
  transition: transform 0.2s;
}
.card:hover { transform: scale(1.03); }
.card h3 { margin: 5px 0; font-size: 28px; color: #2c3e50; }
.card p { margin: 0; font-size: 15px; color: #555; }
form.search-bar {
  margin-bottom: 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
form.search-bar input,
form.search-bar select {
  padding: 8px 10px;
  border-radius: 5px;
  border: 1px solid #ccc;
  font-size: 14px;
}
form.search-bar button,
form.search-bar a {
  padding: 8px 15px;
  border-radius: 5px;
  text-decoration: none;
  color: white;
  font-size: 14px;
}
form.search-bar button { background: #2980b9; border: none; cursor: pointer; }
form.search-bar a { background: #7f8c8d; }
table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  border-radius: 8px;
  overflow: hidden;
}
th, td {
  padding: 12px;
  border: 1px solid #ddd;
  text-align: left;
  font-size: 14px;
}
th {
  background: #2c3e50;
  color: white;
  text-transform: uppercase;
}
tr:nth-child(even) { background: #f9f9f9; }
.btn-close {
  background: #e74c3c;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 5px;
  cursor: pointer;
}
.btn-report {
  background: #2980b9;
  color: white;
  padding: 6px 12px;
  border-radius: 5px;
  text-decoration: none;
}
.status-open { color: #27ae60; font-weight: bold; }
.status-closed { color: #e74c3c; font-weight: bold; }
footer {
  text-align: center;
  padding: 15px;
  background: #2c3e50;
  color: #ecf0f1;
  margin-top: 30px;
}
</style>
</head>
<body>
<header>
  <div style="display:flex;align-items:center;">
    <img src="VSP001.png" alt="Logo">
    <strong>Support Dashboard</strong>
  </div>
  <nav>
    <span>Welcome, <?= htmlspecialchars($username) ?></span>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main>
  <h2>📊 Dashboard Overview</h2>
  <div class="stats">
    <div class="card"><h3><?= (int)($stats['total_tickets'] ?? 0) ?></h3><p>Total</p></div>
    <div class="card"><h3><?= (int)($stats['open_tickets'] ?? 0) ?></h3><p>Open</p></div>
    <div class="card"><h3><?= (int)($stats['closed_tickets'] ?? 0) ?></h3><p>Closed</p></div>
  </div>

  <h2>📌 My Tickets</h2>
  <form method="get" class="search-bar">
    
         

    <select name="status">
      <option value="">All</option>
      <option value="Open" <?= $statusFilter === 'Open' ? 'selected' : '' ?>>Open</option>
      <option value="Closed" <?= $statusFilter === 'Closed' ? 'selected' : '' ?>>Closed</option>
    </select>

    <button type="submit">Filter</button>
    <a href="<?= $_SERVER['PHP_SELF'] ?>">Reset</a>
  </form>

  <?php if (!empty($tickets)): ?>
  <table>
    <thead>
      <tr>
        <th>Ticket Code</th>
        <th>Open Date</th>
        <th>Closed Date</th>
        <th>Title</th>
        <th>Description</th>
        <th>Customer</th>
        <th>Priority</th>
        <th>Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tickets as $t): ?>
      <tr>
        <td><a class="btn-report" href="service_call_report.php?code=<?= urlencode($t['ticket_code']) ?>"><?= htmlspecialchars($t['ticket_code']) ?></a></td>
        <td><?= htmlspecialchars($t['open_date']) ?></td>
        <td><?= htmlspecialchars($t['ClosedDateTime'] ?? '-') ?></td>
        <td><?= htmlspecialchars($t['title']) ?></td>
        <td><?= htmlspecialchars($t['customer_Description']) ?></td>
        <td><?= htmlspecialchars($t['customer_name']) ?></td>
        <td><?= htmlspecialchars($t['customer_priority']) ?></td>
        <td class="<?= strtolower($t['Call_status']) === 'open' ? 'status-open' : 'status-closed' ?>">
          <?= htmlspecialchars($t['Call_status']) ?>
        </td>
        <td>
          <?php if (strtolower($t['Call_status']) === 'open'): ?>
          <form method="post" onsubmit="return confirm('Close this ticket?');" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="close_ticket_code" value="<?= htmlspecialchars($t['ticket_code']) ?>">
            <button type="submit" class="btn-close">Close</button>
          </form>
          <?php else: ?>
            ✅ Closed
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p>No tickets found <?= $search ? "for search: <strong>" . htmlspecialchars($search) . "</strong>" : '' ?>.</p>
  <?php endif; ?>
</main>

<footer>
  © <?= date('Y') ?> Vsmart Technologies Pvt Ltd — Ticket Management System
</footer>
</body>
</html>
