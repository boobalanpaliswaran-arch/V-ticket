<?php
session_start();

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
    die("❌ DB Connection failed: " . $e->getMessage());
}

// =========================
// Handle status update + file upload
// =========================
$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ticket_id'], $_POST['status'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $status = $_POST['status'];
    $closedDateTime = ($status === 'Closed') ? date('Y-m-d H:i:s') : null;

    // Handle file upload
    $uploadedFileName = null;
    if (isset($_FILES['ticket_file']) && $_FILES['ticket_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = basename($_FILES['ticket_file']['name']);
        $targetFile = $uploadDir . time() . "_" . $filename;
        if (move_uploaded_file($_FILES['ticket_file']['tmp_name'], $targetFile)) {
            $uploadedFileName = time() . "_" . $filename;
        } else {
            $msg = "❌ Failed to upload file.";
        }
    }

    // Update ticket in DB
    $sql = "UPDATE servicetickets SET Call_status = :status, ClosedDateTime = :closed";
    if ($uploadedFileName) $sql .= ", attached_file = :file";
    $sql .= " WHERE id = :id";

    $params = [
        ':status' => $status,
        ':closed' => $closedDateTime,
        ':id'     => $ticket_id
    ];
    if ($uploadedFileName) $params[':file'] = $uploadedFileName;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    $msg = "✅ Ticket updated successfully to '$status'.";
}

// =========================
// Handle search
// =========================
$search = trim($_GET['search'] ?? '');
$whereClause = "WHERE 1=1";
$paramsSearch = [];

if ($search !== '') {
    $whereClause .= " AND (ticket_code LIKE :s1 OR title LIKE :s2 OR customer_name LIKE :s3 OR Call_status LIKE :s4)";
    $paramsSearch = [
        ':s1' => "%$search%",
        ':s2' => "%$search%",
        ':s3' => "%$search%",
        ':s4' => "%$search%"
    ];
}

// =========================
// Fetch tickets
// =========================
$sql = "SELECT TOP 100 
        id, ticket_code, open_date, ClosedDateTime, title, customer_name, customer_priority AS priority, 
        assigned, servicecall, Call_status AS status, attached_file
        FROM servicetickets
        $whereClause
        ORDER BY open_date DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($paramsSearch);
$tickets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ticket Dashboard - Modern Style</title>
<style>
/* ===== Reset & Body ===== */
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Poppins', sans-serif;
    background: #f0f3f7;
    color: #333;
}

/* ===== Header ===== */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #1f2937;
    color: #fff;
    padding: 15px 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.header-logo { display: flex; align-items: center; gap: 15px; }
.header-logo img { height: 50px; }
header h1 { font-weight: 600; font-size: 22px; }
nav a {
    color: #fff;
    margin-left: 20px;
    text-decoration: none;
    font-weight: 500;
    transition: 0.3s;
}
nav a:hover { color: #10b981; }

/* ===== Main ===== */
main { max-width: 1200px; margin: 30px auto; padding: 0 15px; }

h2 { text-align: center; margin-bottom: 25px; font-weight: 600; color: #1f2937; }

/* ===== Search ===== */
.search-bar { text-align: center; margin-bottom: 30px; }
.search-bar input[type="text"] {
    width: 250px;
    padding: 10px 15px;
    border-radius: 30px;
    border: 1px solid #ccc;
    outline: none;
    transition: 0.3s;
}
.search-bar input[type="text"]:focus { border-color: #10b981; box-shadow: 0 0 6px rgba(16,185,129,0.3); }
.search-bar button, .search-bar a {
    padding: 10px 18px;
    margin-left: 10px;
    border-radius: 30px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: 0.3s;
}
.search-bar button { background: #10b981; color: #fff; }
.search-bar button:hover { background: #059669; }
.search-bar a { background: #ef4444; color: #fff; }
.search-bar a:hover { background: #b91c1c; }

/* ===== Ticket Cards ===== */
.ticket-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }

.ticket-card {
    background: #fff;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    transition: transform 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.ticket-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); }

.ticket-card h3 { font-size: 18px; margin-bottom: 10px; color: #111827; }
.ticket-card p { font-size: 14px; margin-bottom: 6px; color: #4b5563; }
.ticket-card span { font-weight: 600; }
.ticket-card .status {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 10px;
}
.status-Open { background: #d1fae5; color: #059669; }
.status-Closed { background: #fee2e2; color: #b91c1c; }

/* ===== Update Form inside Card ===== */
.update-form { display: flex; flex-direction: column; gap: 8px; margin-top: 10px; }
.update-form select, .update-form input[type="file"], .update-form button {
    padding: 6px 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 13px;
    outline: none;
    transition: 0.3s;
}
.update-form select:focus, .update-form input[type="file"]:focus { border-color: #10b981; box-shadow: 0 0 5px rgba(16,185,129,0.2); }
.update-form button { background: #10b981; color: #fff; border: none; cursor: pointer; }
.update-form button:hover { background: #059669; }

/* ===== Messages ===== */
.msg { text-align: center; font-weight: bold; color: #10b981; margin-bottom: 20px; }

/* ===== Attachment Link ===== */
a.view-file { color: #3b82f6; text-decoration: none; font-weight: 500; }
a.view-file:hover { text-decoration: underline; }

/* ===== Responsive ===== */
@media (max-width: 768px) { .ticket-card { padding: 15px; } }
</style>
</head>
<body>

<header>
    <div class="header-logo">
        <img src="VSP001.png" alt="Logo">
        <h1>Vsmart Tickets</h1>
    </div>
    <nav>
        <a href="TicketManagementDashboard.php">Dashboard</a>
        <a href="CreateNewTicket.php">Create Ticket</a>
        <a href="ServiceReports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
<h2>Tickets</h2>

<?php if ($msg): ?><p class="msg"><?=$msg?></p><?php endif; ?>

<div class="search-bar">
  <form method="GET">
    <input type="text" name="search" placeholder="🔍 Search tickets..." value="<?=htmlspecialchars($search)?>">
    <button type="submit">Search</button>
    <a href="TicketClosed.php">Reset</a>
  </form>
</div>

<div class="ticket-container">
<?php if($tickets): foreach($tickets as $row): ?>
<div class="ticket-card">
    <h3><?=htmlspecialchars($row['ticket_code'])?> - <?=htmlspecialchars($row['title'])?></h3>
    <p><span>Customer:</span> <?=htmlspecialchars($row['customer_name'])?></p>
    <p><span>Priority:</span> <?=htmlspecialchars($row['priority'])?></p>
    <p><span>Assigned:</span> <?=htmlspecialchars($row['assigned'])?></p>
    <p><span>Service Call:</span> <?=htmlspecialchars($row['servicecall'])?></p>
    <p><span>Open Date:</span> <?=htmlspecialchars($row['open_date'])?></p>
    <p><span>Closed Date:</span> <?=htmlspecialchars($row['ClosedDateTime'])?></p>
    <span class="status status-<?=htmlspecialchars($row['status'])?>"><?=htmlspecialchars($row['status'])?></span>
    <p>
        <?php if($row['attached_file']): ?>
            <a class="view-file" href="uploads/<?=htmlspecialchars($row['attached_file'])?>" target="_blank">View Attachment</a>
        <?php else: ?>
            No File
        <?php endif; ?>
    </p>
    <form method="POST" class="update-form" enctype="multipart/form-data">
        <input type="hidden" name="ticket_id" value="<?= $row['id'] ?>">
        <select name="status">
            <option value="Open" <?=$row['status']=='Open'?'selected':''?>>Open</option>
            <option value="Closed" <?=$row['status']=='Closed'?'selected':''?>>Closed</option>
        </select>
        <input type="file" name="ticket_file" accept=".pdf,.jpg,.png,.doc,.docx">
        <button type="submit">Update</button>
    </form>
</div>
<?php endforeach; else: ?>
<p style="text-align:center; font-weight:bold; color:#6b7280;">No tickets found</p>
<?php endif; ?>
</div>

</main>
</body>
</html>
