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

function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_token = $_POST['csrf_token'] ?? '';
        if (!$user_token || !hash_equals($_SESSION['csrf_token'], $user_token)) {
            die('⚠️ Invalid CSRF token. Operation blocked.');
        }
    }
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
    die("❌ DB Connection failed: " . $e->getMessage());
}

// =========================
// Handle POST form submission
// =========================
$successMsg = "";
$errorMsg   = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validate_csrf(); // ✅ Validate CSRF

    $title            = trim($_POST['title'] ?? '');
    $description      = trim($_POST['description'] ?? '');
    $customer_name    = trim($_POST['customer'] ?? '');
    $priority         = trim($_POST['priority'] ?? '');
    $assigned_id      = (int)($_POST['assigned'] ?? 0);
    $service_call     = trim($_POST['call'] ?? '');
    $service_type     = trim($_POST['service_type'] ?? '');
    $status           = "Open";

    try {
        // Get assigned username
        $stmtEmp = $conn->prepare("SELECT username FROM employee WHERE id = :id");
        $stmtEmp->execute([':id' => $assigned_id]);
        $assigned_username = $stmtEmp->fetchColumn() ?: '';

        // Get next ID for ticket_code
        $stmtMax = $conn->query("SELECT ISNULL(MAX(id),0) AS max_id FROM servicetickets");
        $lastId = (int)$stmtMax->fetch()['max_id'] + 1;

        // Generate ticket code
        $ticket_code = "VSP-" . date("Y") . "ID" . str_pad($lastId, 4, "0", STR_PAD_LEFT);

        // Insert ticket into servicetickets table
        $stmt = $conn->prepare("INSERT INTO servicetickets 
            (ticket_code, title, customer_Description, customer_name, customer_priority, assigned, servicecall, Call_status, service_type, open_date)
            VALUES (:ticket_code, :title, :description, :customer, :priority, :assigned, :servicecall, :status, :service_type, GETDATE())");

        $stmt->execute([
            ':ticket_code'   => $ticket_code,
            ':title'         => $title,
            ':description'   => $description,
            ':customer'      => $customer_name,
            ':priority'      => $priority,
            ':assigned'      => $assigned_username,
            ':servicecall'   => $service_call,
            ':status'        => $status,
            ':service_type'  => $service_type
        ]);

        $successMsg = "✅ Ticket Created Successfully! (Ticket ID: $ticket_code)";
    } catch (PDOException $e) {
        $errorMsg = "❌ Error: " . $e->getMessage();
    }
}

// =========================
// Fetch customers
// =========================
try {
    $customers = $conn->query("SELECT customer_name FROM dbo.Customers ORDER BY customer_name ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("❌ Error fetching customers: " . $e->getMessage());
}

// =========================
// Fetch active employees
// =========================
try {
    $employee = $conn->query("SELECT id, username FROM employee WHERE emp_status='Active' ORDER BY username ASC")->fetchAll();
} catch (PDOException $e) {
    die("❌ Error fetching employees: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Service Ticket - Vsmart</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background:#f0f2f5; margin:0; padding:0; }
header { background:#2c3e50; color:#fff; padding:20px; text-align:center; }
header h1 { margin:0; font-size:26px; }
header h2 { margin:5px 0 10px; font-weight:400; font-size:16px; }
nav a { color:#ecf0f1; margin:0 12px; text-decoration:none; font-weight:500; transition:0.2s; }
nav a:hover { color:#1abc9c; }
main { display:flex; justify-content:center; align-items:flex-start; padding:30px; }
.form-card { background:#fff; border-radius:12px; padding:25px 30px; box-shadow:0 6px 18px rgba(0,0,0,0.1); width:100%; max-width:600px; animation: fadeIn 0.4s ease-in-out; }
.form-card h2 { margin:0 0 20px; text-align:center; color:#2c3e50; }
label { display:block; margin-bottom:6px; font-weight:600; color:#333; }
input[type="text"], textarea, select { width:100%; padding:10px; margin-bottom:18px; border:1px solid #ccc; border-radius:8px; font-size:14px; transition:border 0.2s; }
input:focus, textarea:focus, select:focus { border-color:#2980b9; outline:none; }
textarea { height:100px; resize:vertical; }
button { background:#2980b9; color:#fff; padding:12px 20px; border:none; border-radius:8px; cursor:pointer; font-size:16px; width:100%; transition:0.3s; }
button:hover { background:#1f6391; }
.msg { text-align:center; font-weight:600; margin-bottom:15px; }
.msg.success { color:green; }
.msg.error { color:red; }
@keyframes fadeIn { from {opacity:0; transform:translateY(15px);} to {opacity:1; transform:translateY(0);} }
</style>
</head>
<body>
<header>
  <h1>Vsmart Technologies Pvt Ltd</h1>
  <h2>Ticket Management System</h2>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="TicketClosed.php">Ticket Closed</a>
    <a href="ServiceReports.php">Reports</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main>
<div class="form-card">
  <h2>🎫 Create New Service Ticket</h2>

  <?php if ($successMsg): ?><p class="msg success"><?= htmlspecialchars($successMsg) ?></p><?php endif; ?>
  <?php if ($errorMsg): ?><p class="msg error"><?= htmlspecialchars($errorMsg) ?></p><?php endif; ?>

  <form method="POST" action="">
    <?= csrf_field(); ?>
    
    <label>Title</label>
    <input type="text" name="title" required>

    <label>Description</label>
    <textarea name="description" required></textarea>

    <label>Customer Name</label>
    <select name="customer" required>
      <option value="">-- Select Customer --</option>
      <?php foreach ($customers as $cust): ?>
        <option value="<?= htmlspecialchars($cust) ?>"><?= htmlspecialchars($cust) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Priority</label>
    <select name="priority" required>
      <option value="High">High</option>
      <option value="Medium" selected>Medium</option>
      <option value="Low">Low</option>
    </select>

    <label>Assigned To</label>
    <select name="assigned" required>
      <option value="">-- Select Employee --</option>
      <?php foreach ($employee as $emp): ?>
        <option value="<?= htmlspecialchars($emp['id']) ?>"><?= htmlspecialchars($emp['username']) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Service Call</label>
    <select name="call" required>
      <option value="">-- Select Service Call --</option>
      <option value="AMC">AMC</option>
      <option value="Private Maintenance">Private Maintenance</option>
      <option value="Warranty">Warranty</option>
      <option value="Other/Service Charges">Other/Service Charges</option>
    </select>

    <label>Service Type</label>
    <select name="service_type" required>
      <option value="">-- Select Service Type --</option>
      <option value="On-Site Support">On-Site Support</option>
      <option value="Remote Support">Remote Support</option>
    </select>

    <button type="submit">✅ Create Service Ticket</button>
  </form>
</div>
</main>
</body>
</html>
