<?php
// user_registration.php

// =========================
// DB connection (MSSQL)
// =========================
$serverName = "BOOBALAN\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "vsmart",
    "Uid"      => "sa",
    "PWD"      => "admin@123",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die("❌ Connection failed: " . print_r(sqlsrv_errors(), true));
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role'];

    // Validate inputs
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $msg = "<p class='msg error'>❌ All fields are required.</p>";
    } else {
        // Check if email already exists
        $checkSql = "SELECT id FROM logindb WHERE email = ?";
        $params = [$email];
        $stmtCheck = sqlsrv_query($conn, $checkSql, $params);

        if ($stmtCheck === false) die(print_r(sqlsrv_errors(), true));

        if (sqlsrv_has_rows($stmtCheck)) {
            $msg = "<p class='msg error'>❌ Email already exists. Please use another email.</p>";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $sql = "INSERT INTO logindb (username, email, userpassword, user_role, user_status, created_at) 
                    VALUES (?, ?, ?, ?, ?, GETDATE())";
            $paramsInsert = [$name, $email, $hashedPassword, $role, 'active'];
            $stmtInsert = sqlsrv_query($conn, $sql, $paramsInsert);

            if ($stmtInsert) {
                $msg = "<p class='msg success'>✅ User Created. <a href='login.php'>Login Here</a></p>";
            } else {
                $msg = "<p class='msg error'>❌ Error inserting user: " . print_r(sqlsrv_errors(), true) . "</p>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Registration - Ticket Management</title>
<style>
/* ==== Global Reset ==== */
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', sans-serif; }
body { background:#f4f6f9; color:#333; line-height:1.6; }

/* ==== Header ==== */
header { display:flex; align-items:center; justify-content:space-between; background:#2c3e50; padding:12px 20px; color:#fff; }
header img { height:50px; border-radius:8px; }
header .company-info { flex:1; text-align:center; }
header .company-info h1 { font-size:20px; margin:0; }
header .company-info h2 { font-size:13px; font-weight:400; color:#ddd; margin:2px 0 0; }
nav a { margin:0 10px; color:#fff; text-decoration:none; font-weight:500; transition:0.3s; }
nav a:hover { text-decoration:underline; }

/* ==== Page Title ==== */
h2 { text-align:center; margin:20px 0; font-size:22px; color:#2c3e50; }

/* ==== Form Container ==== */
.form-container { max-width:450px; margin:0 auto 40px; background:#fff; padding:25px 30px; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.1); }
label { display:block; margin-top:12px; font-weight:600; color:#2c3e50; }
input, select { width:100%; padding:12px 10px; margin-top:6px; border:1px solid #ccc; border-radius:8px; font-size:14px; transition:0.3s; }
input:focus, select:focus { border-color:#1abc9c; outline:none; }

/* ==== Button ==== */
button { margin-top:20px; padding:12px; width:100%; background:#1abc9c; color:#fff; border:none; border-radius:8px; font-size:16px; font-weight:600; cursor:pointer; transition:0.3s; }
button:hover { background:#16a085; }

/* ==== Messages ==== */
.msg { text-align:center; font-weight:bold; margin:15px 0; }
.success { color:#27ae60; }
.error { color:#e74c3c; }

/* ==== Responsive ==== */
@media(max-width:480px) {
    header { flex-direction:column; align-items:flex-start; }
    header .company-info { text-align:left; margin:10px 0; }
    .form-container { padding:20px; }
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
        <a href="viewuser.php">View Users</a>
        <a href="login.php">Logout</a>
    </nav>
</header>

<main>
<h2>👤 Create New User</h2>
<?= $msg ?>
<div class="form-container">
<form method="POST">
  <label>Name</label>
  <input type="text" name="name" placeholder="Enter full name" required>

  <label>Email</label>
  <input type="email" name="email" placeholder="Enter email address" required>

  <label>Password</label>
  <input type="password" name="password" placeholder="Enter password" required>

  <label>Role</label>
  <select name="role" required>
    <option value="">-- Select Role --</option>
    <option value="admin">Admin</option>
    <option value="support">Support</option>
    <option value="customer">Customer</option>
  </select>

  <button type="submit">➕ Register User</button>
</form>
</div>
</main>
</body>
</html>
