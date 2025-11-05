<?php 
session_start();
if (!isset($_SESSION['username']) || strtolower($_SESSION['role']) !== 'customer') {
    header("Location: login.php");
    exit();
}

// ----------------------
// Database Connection
// ----------------------
$serverName = "BOOBALAN\\SQLEXPRESS";
$dbName     = "vsmart";
$dbUser     = "sa";
$dbPass     = "admin@123";

try {
    $pdo = new PDO("sqlsrv:Server=$serverName;Database=$dbName", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// ----------------------
// Detect existing columns dynamically
// ----------------------
$columns = [];
try {
    $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'servicetickets'";
    $cols  = $pdo->query($query)->fetchAll(PDO::FETCH_COLUMN);
    $columns = array_map('strtolower', $cols);
} catch (PDOException $e) {
    die("Failed to fetch columns: " . $e->getMessage());
}

$possible = [
    'ticket_code', 'open_date', 'title', 'description', 'customer_name',
    'priority', 'assigned', 'servicecall', 'status', 'call_status',
    'closeddatetime', 'info_type', 'info_text', 'action_taken'
];
$selectCols = array_values(array_intersect($columns, $possible));
if (empty($selectCols)) die("No valid columns found in servicetickets table.");

// ----------------------
// Detect which column holds the status
// (choose first match from this list)
// ----------------------
$statusCandidates = ['status', 'call_status', 'callstatus', 'call_status', 'call-status'];
$statusField = null;
foreach ($statusCandidates as $cand) {
    if (in_array($cand, $selectCols)) {
        $statusField = $cand;
        break;
    }
}
// If still null, try to guess from actual columns (common names)
if ($statusField === null) {
    foreach ($columns as $col) {
        if (strpos($col, 'status') !== false) {
            // use first column that contains 'status'
            $statusField = $col;
            break;
        }
    }
}
// If still null, we will just set to null and handle gracefully later

// ----------------------
// Fetch Customer Tickets
// ----------------------
$customer_name = $_SESSION['username'];
$sql = "SELECT " . implode(", ", $selectCols) . " 
        FROM servicetickets 
        WHERE customer_name = :customer_name 
        ORDER BY open_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['customer_name' => $customer_name]);
$tickets = $stmt->fetchAll();

// ----------------------
// Ticket Status Counts (robust detection)
// ----------------------
$statusCounts = ['open' => 0, 'closed' => 0, 'Total Tickets' => 0];
foreach ($tickets as $t) {
    $raw = '';
    if ($statusField !== null) {
        $raw = $t[$statusField] ?? '';
    } else {
        // fallback: try the commonly used 'status' key
        $raw = $t['status'] ?? $t['call_status'] ?? '';
    }

    $status = strtolower(trim((string)$raw));
    // map a few variants to open/closed
    if ($status === '') {
        // unknown — don't increment open/closed, but count total
    } elseif (strpos($status, 'open') !== false) {
        $statusCounts['open']++;
    } elseif (strpos($status, 'close') !== false || strpos($status, 'resolved') !== false || strpos($status, 'resolve') !== false || strpos($status, 'completed') !== false) {
        $statusCounts['closed']++;
    } else {
        // anything else - if you want to treat as open or closed, adjust here
        // for now treat as neither but still count in total
    }
    $statusCounts['Total Tickets']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Dashboard - Vsmart</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #2980b9, #6dd5fa, #ffffff);
    min-height: 100vh;
    color: #333;
}
header {
    background: rgba(44, 62, 80, 0.95);
    color: #fff;
    padding: 18px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    border-bottom: 3px solid #1abc9c;
}
header .logo {
    display: flex;
    align-items: center;
}
header .logo img {
    height: 55px;
    margin-right: 15px;
    border-radius: 8px;
    border: 2px solid #1abc9c;
}
header h2 {
    font-weight: 500;
    font-size: 22px;
}
header a {
    background: #e74c3c;
    color: #fff;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}
header a:hover {
    background: #c0392b;
    transform: scale(1.05);
}

.container {
    margin: 40px auto;
    width: 92%;
    max-width: 1250px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(8px);
    border-radius: 18px;
    padding: 30px 40px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    animation: fadeIn 0.5s ease-in-out;
}
h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    border-left: 6px solid #1abc9c;
    padding-left: 12px;
    font-size: 20px;
}

.stats {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}
.stat-box {
    flex: 1;
    min-width: 220px;
    padding: 25px;
    border-radius: 14px;
    text-align: center;
    color: #fff;
    cursor: pointer;
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.stat-box:hover {
    transform: translateY(-6px);
    box-shadow: 0 8px 18px rgba(0,0,0,0.25);
}
.stat-box h4 { margin: 0; font-size: 18px; }
.stat-box p { margin: 10px 0 0; font-size: 28px; font-weight: bold; }

.open { background: linear-gradient(135deg, #1abc9c, #16a085); }
.closed { background: linear-gradient(135deg, #e74c3c, #c0392b); }
.total { background: linear-gradient(135deg, #8e44ad, #6c3483); }

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 12px;
    overflow: hidden;
    font-size: 14px;
}
thead {
    background: #2c3e50;
    color: #fff;
}
th, td {
    padding: 12px 10px;
    text-align: center;
    border-bottom: 1px solid #ddd;
}
tr:nth-child(even) { background: #f9f9f9; }
tr:hover { background: #ecf0f1; }
.status-open { color: #27ae60; font-weight: 600; }
.status-closed { color: #e74c3c; font-weight: 600; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (max-width: 768px) {
    header { flex-direction: column; text-align: center; }
    .stats { flex-direction: column; align-items: center; }
    .stat-box { width: 80%; }
    table { font-size: 12px; }
}
</style>
</head>
<body>

<header>
    <div class="logo">
        <img src="VSP001.png" alt="Vsmart Logo">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h2>
    </div>
    <a href="logout.php">Logout</a>
</header>

<div class="container">
    <h3>📊 Ticket Overview</h3>

    <div class="stats">
        <div class="stat-box open" onclick="filterTickets('open')">
            <h4>Open Tickets</h4>
            <p id="open-count"><?= $statusCounts['open'] ?></p>
        </div>
        <div class="stat-box closed" onclick="filterTickets('closed')">
            <h4>Closed Tickets</h4>
            <p id="closed-count"><?= $statusCounts['closed'] ?></p>
        </div>
        <div class="stat-box total" onclick="filterTickets('all')">
            <h4>Total Tickets</h4>
            <p id="total-count"><?= $statusCounts['Total Tickets'] ?></p>
        </div>
    </div>

    <h3>🎟️ Your Ticket History</h3>
    <?php if (count($tickets) === 0): ?>
        <p>No tickets found for your account.</p>
    <?php else: ?>
        <table id="ticketTable">
            <thead>
                <tr>
                    <?php foreach ($selectCols as $col): ?>
                        <th><?= htmlspecialchars(ucwords(str_replace('_', ' ', $col))) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $row): 
                    // determine row status for data attribute using detected status field
                    $rawRowStatus = '';
                    if ($statusField !== null) {
                        $rawRowStatus = $row[$statusField] ?? '';
                    } else {
                        $rawRowStatus = $row['status'] ?? $row['call_status'] ?? '';
                    }
                    $rowStatusNorm = strtolower(trim((string)$rawRowStatus));
                    // normalize to 'open' or 'closed' where possible for easier client-side filtering
                    if (strpos($rowStatusNorm, 'open') !== false) $dataStatus = 'open';
                    elseif (strpos($rowStatusNorm, 'close') !== false || strpos($rowStatusNorm, 'resolve') !== false || strpos($rowStatusNorm, 'completed') !== false) $dataStatus = 'closed';
                    else $dataStatus = $rowStatusNorm !== '' ? $rowStatusNorm : 'unknown';
                ?>
                <tr data-status="<?= htmlspecialchars($dataStatus) ?>">
                    <?php foreach ($selectCols as $col): 
                        $value = $row[$col] ?? '';
                        $class = '';
                        $valLower = strtolower(trim((string)$value));
                        if (strpos($valLower, 'open') !== false) $class = 'status-open';
                        elseif (strpos($valLower, 'close') !== false || strpos($valLower, 'resolve') !== false || strpos($valLower, 'completed') !== false) $class = 'status-closed';
                    ?>
                        <td class="<?= $class ?>"><?= htmlspecialchars($value) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function filterTickets(type) {
    const rows = document.querySelectorAll("#ticketTable tbody tr");
    let openCount = 0, closedCount = 0, total = 0;
    rows.forEach(row => {
        const status = row.getAttribute("data-status");
        total++;
        if (type === "all") {
            row.style.display = "";
            // count client-side for correctness
            if (status === "open") openCount++;
            else if (status === "closed") closedCount++;
        } else if (type === "open") {
            if (status === "open") {
                row.style.display = "";
                openCount++;
            } else {
                row.style.display = "none";
            }
        } else if (type === "closed") {
            if (status === "closed") {
                row.style.display = "";
                closedCount++;
            } else {
                row.style.display = "none";
            }
        }
    });

    // Update stat boxes with live counts (keeps UI consistent)
    if (type === 'all') {
        document.getElementById('open-count').textContent = openCount;
        document.getElementById('closed-count').textContent = closedCount;
        document.getElementById('total-count').textContent = total;
    } else if (type === 'open') {
        document.getElementById('open-count').textContent = openCount;
        // keep closed/total as original server counts (optional)
    } else if (type === 'closed') {
        document.getElementById('closed-count').textContent = closedCount;
    }
}
</script>

</body>
</html>
