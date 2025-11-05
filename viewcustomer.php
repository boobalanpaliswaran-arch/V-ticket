<?php
session_start();

// =========================
// SQL Server Connection
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
// Fetch Customers (adjust column names if needed)
// =========================
try {
    $sql = "SELECT 
                customer_id, 
                customer_name, 
                email, 
                Phone AS phone, 
                customer_Address, 
                customer_Location
            FROM customers
            ORDER BY customer_id DESC";
    $stmt = $conn->query($sql);
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("❌ SQL Error: " . $e->getMessage());
}

// =========================
// Helper
// =========================
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer List - Ticket Management</title>
<style>
/* ===== GLOBAL STYLES ===== */
* { margin:0; padding:0; box-sizing:border-box; }
body {
  font-family: 'Segoe UI', Tahoma, sans-serif;
  background: #eef2f7;
  color: #2c3e50;
  line-height: 1.5;
}

/* ===== HEADER ===== */
header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(90deg, #2c3e50, #1f2d3a);
  color: #fff;
  padding: 10px 30px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.25);
  position: sticky;
  top: 0;
  z-index: 100;
}
header .logo-area {
  display: flex;
  align-items: center;
  gap: 14px;
}
header img {
  height: 50px;
  width: auto;
  border-radius: 10px;
  transition: transform 0.3s ease;
}
header img:hover {
  transform: scale(1.05);
}
header h1 {
  font-size: 20px;
  margin-bottom: 4px;
}
header h2 {
  font-size: 13px;
  color: #dfe6e9;
  font-weight: 400;
}
nav a {
  margin-left: 20px;
  text-decoration: none;
  color: #ecf0f1;
  font-weight: 500;
  transition: color 0.3s, transform 0.2s;
}
nav a:hover {
  color: #1abc9c;
  transform: translateY(-2px);
}

/* ===== PAGE TITLE ===== */
.page-title {
  text-align: center;
  font-size: 24px;
  font-weight: 600;
  color: #2c3e50;
  margin: 30px 0 20px;
}

/* ===== TABLE CARD ===== */
.table-card {
  background: #fff;
  border-radius: 15px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
  max-width: 1200px;
  margin: 0 auto 50px;
  overflow-x: auto;
  padding: 20px 25px;
  animation: fadeIn 0.6s ease;
  transition: all 0.3s;
}
.table-card:hover {
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

/* ===== TABLE ===== */
table {
  width: 100%;
  border-collapse: collapse;
  border-radius: 10px;
  overflow: hidden;
}
th, td {
  padding: 14px 16px;
  text-align: left;
  font-size: 15px;
}
th {
  background: #2c3e50;
  color: #fff;
  font-weight: 600;
  letter-spacing: 0.3px;
}
tr:nth-child(even) {
  background: #f8f9fb;
}
tr:hover {
  background: #e9f3ff;
  transition: background 0.2s;
}

/* ===== ACTION BUTTONS ===== */
.action { display: flex; gap: 10px; }
.btn {
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: 0.3s;
  display: inline-block;
}
.btn-edit {
  background: #ecf6ff;
  color: #2980b9;
  border: 1px solid #2980b9;
}
.btn-edit:hover {
  background: #2980b9;
  color: #fff;
  box-shadow: 0 0 8px rgba(41, 128, 185, 0.5);
}
.btn-delete {
  background: #fff3f3;
  color: #e74c3c;
  border: 1px solid #e74c3c;
}
.btn-delete:hover {
  background: #e74c3c;
  color: #fff;
  box-shadow: 0 0 8px rgba(231, 76, 60, 0.5);
}

/* ===== ANIMATION ===== */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
</head>
<body>

<header>
  <div class="logo-area">
    <img src="VSP001.png" alt="Ticket Logo">
    <div>
      <h1>Vsmart Technologies Pvt Ltd</h1>
      <h2>Ticket Management System</h2>
    </div>
  </div>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="Newcustomer.php">Add Customers</a>
    <a href="login.php" id="logoutBtn">Logout</a>
  </nav>
</header>

<h2 class="page-title">📋 Customer List</h2>

<div class="table-card">
<?php if (count($customers) === 0): ?>
  <p style="text-align:center; color:#777;">No customers found.</p>
<?php else: ?>
  <table>
    <tr>
      <th>ID</th>
      <th>Name</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Address</th>
      <th>Location</th>
      <th>Action</th>
    </tr>
    <?php foreach ($customers as $row): ?>
      <tr>
        <td><?= e($row['customer_id']) ?></td>
        <td><?= e($row['customer_name']) ?></td>
        <td><?= e($row['email']) ?></td>
        <td><?= e($row['phone']) ?></td>
        <td><?= nl2br(e($row['customer_Address'])) ?></td>
        <td><?= e($row['customer_Location']) ?></td>
        <td class="action">
          <a href="editcustomer.php?id=<?= urlencode($row['customer_id']) ?>" class="btn btn-edit">✏️ Edit</a>
          <a href="deletecustomer.php?id=<?= urlencode($row['customer_id']) ?>" class="btn btn-delete">🗑️ Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
</div>

</body>
</html>
