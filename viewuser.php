<?php
// -----------------
// Database connection (MSSQL)
// -----------------
$serverName = "BOOBALAN\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "vsmart",
    "Uid"      => "sa",
    "PWD"      => "admin@123",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die("❌ DB Connection failed: " . print_r(sqlsrv_errors(), true));
}

// ✅ Get filter values
$filter_role   = isset($_GET['role']) ? $_GET['role'] : "";
$filter_status = isset($_GET['status']) ? $_GET['status'] : "";

// ✅ Base query with filters
$sql = "SELECT * FROM logindb WHERE 1=1";
$params = [];

if ($filter_role !== "") {
    $sql .= " AND user_role = ?";
    $params[] = $filter_role;
}
if ($filter_status !== "") {
    $sql .= " AND user_status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY username ASC";

// Execute query
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die("❌ Error in query: " . print_r(sqlsrv_errors(), true));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User List - Ticket Management</title>
<style>
/* ==== Reset & Base ==== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Inter", "Segoe UI", sans-serif;
}

body {
    background: linear-gradient(135deg, #f4f6f9, #ffffff);
    color: #2c3e50;
    min-height: 100vh;
}

/* ==== Header ==== */
header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #2c3e50;
    padding: 15px 25px;
    color: #fff;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
header img {
    height: 50px;
    border-radius: 10px;
}
header .company-info {
    text-align: center;
    flex: 1;
}
header .company-info h1 {
    font-size: 22px;
    letter-spacing: 0.5px;
}
header .company-info h2 {
    font-size: 13px;
    color: #bdc3c7;
}
nav a {
    margin: 0 12px;
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}
nav a:hover {
    color: #f1c40f;
}

/* ==== Title ==== */
h2 {
    text-align: center;
    margin: 30px 0 15px;
    font-size: 24px;
    color: #2c3e50;
    font-weight: 600;
}

/* ==== Table Container ==== */
.table-container {
    background: #fff;
    margin: 0 auto;
    max-width: 1000px;
    padding: 20px;
    border-radius: 16px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    overflow-x: auto;
}

/* ==== Table ==== */
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 15px;
}
th, td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}
th {
    background: #2c3e50;
    color: #fff;
    font-weight: 600;
    font-size: 14px;
    position: sticky;
    top: 0;
}
tr:hover {
    background: #ecf0f1;
    transition: background 0.3s ease;
}

/* ==== Filters ==== */
select {
    padding: 6px 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    background: #fff;
    font-size: 13px;
    transition: 0.2s;
}
select:hover, select:focus {
    border-color: #2c3e50;
    outline: none;
}

/* ==== Buttons ==== */
.edit-btn {
    display: inline-block;
    padding: 7px 14px;
    background: #ecf0f1;
    color: #2c3e50;
    border: 1px solid #2c3e50;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}
.edit-btn:hover {
    background: #2c3e50;
    color: #fff;
}

/* ==== Status Badges ==== */
.status-active {
    color: #27ae60;
    background: #eafaf1;
    padding: 5px 10px;
    border-radius: 12px;
    font-weight: 600;
    text-align: center;
    display: inline-block;
}
.status-inactive {
    color: #c0392b;
    background: #fdecea;
    padding: 5px 10px;
    border-radius: 12px;
    font-weight: 600;
    text-align: center;
    display: inline-block;
}

/* ==== Empty Row ==== */
td[colspan="5"] {
    text-align: center;
    color: #777;
    padding: 20px;
}

/* ==== Responsive ==== */
@media (max-width: 768px) {
    header {
        flex-direction: column;
        align-items: flex-start;
    }
    header .company-info {
        text-align: left;
        margin: 10px 0;
    }
    th, td {
        padding: 10px;
        font-size: 13px;
    }
    .table-container {
        padding: 10px;
        margin: 10px;
    }
}
</style>
</head>

<body>

<header>
    <img src="VSP001.png" alt="Ticket Logo">
    <div class="company-info">
        <h1>Vsmart Technologies Pvt Ltd</h1>
        <h2>Ticket Management System</h2>
    </div>
    <nav>
        <a href="TicketManagementDashboard.php">Dashboard</a>
        <a href="user.php">Add User</a>
        <a href="login.php">Logout</a>
    </nav>
</header>

<main>
<h2>👥 User List</h2>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>
                Role <br>
                <form method="GET" style="margin:0;">
                    <select name="role" onchange="this.form.submit()">
                        <option value="">-- All --</option>
                        <option value="admin" <?= $filter_role=="admin"?'selected':'' ?>>Admin</option>
                        <option value="support" <?= $filter_role=="support"?'selected':'' ?>>Support</option>
                        <option value="customer" <?= $filter_role=="customer"?'selected':'' ?>>Customer</option>
                    </select>
                    <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
                </form>
            </th>
            <th>
                Status <br>
                <form method="GET" style="margin:0;">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">-- All --</option>
                        <option value="Active" <?= $filter_status=="Active"?'selected':'' ?>>Active</option>
                        <option value="Inactive" <?= $filter_status=="Inactive"?'selected':'' ?>>Inactive</option>
                    </select>
                    <input type="hidden" name="role" value="<?= htmlspecialchars($filter_role) ?>">
                </form>
            </th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $hasRows = false;
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $hasRows = true;
            $statusClass = strtolower($row['user_status']) === 'active' ? 'status-active' : 'status-inactive';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_role']) . "</td>";
            echo "<td><span class='$statusClass'>" . htmlspecialchars($row['user_status']) . "</span></td>";
            echo "<td><a class='edit-btn' href='edituser.php?id=" . $row['id'] . "'>Edit</a></td>";
            echo "</tr>";
        }
        if (!$hasRows) {
            echo "<tr><td colspan='5'>No users found</td></tr>";
        }
        sqlsrv_close($conn);
        ?>
    </tbody>
</table>
</div>

</main>
</body>
</html>
