<?php
session_start();

// ----------------------
// DB Connection (MSSQL PDO)
// ----------------------
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

// ----------------------
// Handle Search
// ----------------------
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($search !== '') {
        $sql = "SELECT id, EmpCode AS emp_code, username, Email AS emp_email, Phone AS emp_phone,
                       EmergencyContact AS emp_emergency, department, emp_status, HireDate AS hire_date, Remarks AS remarks
                FROM employee
                WHERE EmpCode LIKE ? OR username LIKE ? OR Email LIKE ? OR department LIKE ?
                ORDER BY id DESC";
        $params = ["%$search%", "%$search%", "%$search%", "%$search%"];
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } else {
        $sql = "SELECT id, EmpCode AS emp_code, username, Email AS emp_email, Phone AS emp_phone,
                       EmergencyContact AS emp_emergency, department, emp_status, HireDate AS hire_date, Remarks AS remarks
                FROM employee
                ORDER BY id DESC";
        $stmt = $conn->query($sql);
    }

    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    die("❌ Query Error: " . $e->getMessage());
}

// ----------------------
// Calculate Experience
// ----------------------
function calculateExperience($hire_date) {
    if (!$hire_date) return "N/A";
    $start = new DateTime($hire_date);
    $today = new DateTime();
    $diff  = $today->diff($start);
    $years  = $diff->y;
    $months = $diff->m;

    $exp = "";
    if ($years > 0) $exp .= $years . " Yr" . ($years > 1 ? "s " : " ");
    if ($months > 0) $exp .= $months . " Mo" . ($months > 1 ? "s" : "");
    return $exp ?: "Less than a month";
}

// ----------------------
// CSRF TOKEN
// ----------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Employee Records</title>
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
    background: #eef2f7;
    color: #333;
}
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #2c3e50;
    color: white;
    padding: 6px 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
header .logo-container {
    display: flex;
    align-items: center;
    gap: 10px;
}
header img {
    height: 40px;
    border-radius: 6px;
}
header h1 {
    font-size: 18px;
    margin: 0;
}
header h2 {
    font-size: 11px;
    color: #bdc3c7;
    margin: 0;
}
nav a {
    margin-left: 15px;
    color: white;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}
nav a:hover { color: #f39c12; }

main {
    padding: 20px;
}
h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 10px;
}
.search-bar {
    text-align: center;
    margin-bottom: 15px;
}
.search-bar input[type="text"] {
    width: 280px;
    padding: 8px;
    border-radius: 5px;
    border: 1px solid #ccc;
}
.search-bar button {
    padding: 8px 15px;
    border: none;
    background: #3498db;
    color: white;
    border-radius: 5px;
    cursor: pointer;
}
.search-bar button:hover { background: #2980b9; }

table {
    border-collapse: collapse;
    width: 100%;
    font-size: 13px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}
th, td {
    border: 1px solid #e1e1e1;
    padding: 8px 10px;
    text-align: left;
}
th {
    background: #34495e;
    color: white;
    font-size: 12px;
    text-transform: uppercase;
}
tr:nth-child(even) { background: #f9f9f9; }
tr:hover { background: #f1f9ff; transition: 0.2s; }

.status-Active { color: green; font-weight: bold; }
.status-Inactive { color: red; font-weight: bold; }

.edit-btn, .delete-btn {
    padding: 5px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 12px;
}
.edit-btn { background: #3498db; color: white; }
.edit-btn:hover { background: #2980b9; }
.delete-btn { background: #e74c3c; color: white; }
.delete-btn:hover { background: #c0392b; }

@media (max-width: 992px) {
    table { display: block; overflow-x: auto; white-space: nowrap; }
}
</style>
</head>
<body>

<header>
    <div class="logo-container">
        <img src="VSP001.png" alt="Logo">
        <div>
            <h1>Vsmart Technologies Pvt Ltd</h1>
            <h2>Employee Management System</h2>
        </div>
    </div>
    <nav>
        <a href="TicketManagementDashboard.php">Dashboard</a>
        <a href="employee.php">Add Employee</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
<h2>Employee Records</h2>

<div class="search-bar">
    <form method="get">
        <input type="text" name="search" placeholder="Search by name, code, email, or department..." value="<?= e($search) ?>">
        <button type="submit">Search</button>
        <?php if($search): ?>
            <a href="EmployeeRecords.php" style="margin-left:10px; color:#e74c3c; text-decoration:none;">❌ Clear</a>
        <?php endif; ?>
    </form>
</div>

<div style="overflow-x:auto;">
<table>
<tr>
  <th>Emp Code</th>
  <th>Name</th>
  <th>Email</th>
  <th>Phone</th>
  <th>Emergency</th>
  <th>Department</th>
  <th>Status</th>
  <th>Hire Date</th>
  <th>Experience</th>
  <th>Remarks</th>
  <th>Actions</th>
</tr>

<?php if($employees): ?>
    <?php foreach($employees as $row): ?>
    <tr>
        <td><?= e($row['emp_code']) ?></td>
        <td><?= e($row['username']) ?></td>
        <td><?= e($row['emp_email']) ?></td>
        <td><?= e($row['emp_phone'] ?? 'N/A') ?></td>
        <td><?= e($row['emp_emergency'] ?? 'N/A') ?></td>
        <td><?= e($row['department'] ?? 'N/A') ?></td>
        <td class="status-<?= e($row['emp_status'] ?? 'Inactive') ?>"><?= e($row['emp_status'] ?? 'Inactive') ?></td>
        <td><?= e($row['hire_date']) ?></td>
        <td><?= calculateExperience($row['hire_date']) ?></td>
        <td><?= e($row['remarks'] ?? '') ?></td>
        <td>
            <form action="editemployee.php" method="get" style="display:inline;">
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <button type="submit" class="edit-btn">Edit</button>
            </form>
            <form action="employee_delete.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <button type="submit" class="delete-btn">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr><td colspan="11" style="text-align:center; color:#888;">No employees found.</td></tr>
<?php endif; ?>
</table>
</div>
</main>
</body>
</html>
