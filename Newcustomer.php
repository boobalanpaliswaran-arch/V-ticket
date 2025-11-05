<?php
session_start();

// =========================
// DB Connection (MSSQL PDO)
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
// CSRF TOKEN
// =========================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$successMsg = "";
$errorMsg   = "";

// =========================
// Handle Form Submission
// =========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username  = trim($_POST['customer_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['customer_Address'] ?? '');
    $location  = trim($_POST['customer_Location'] ?? '');
    $csrf_post = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf_post)) {
        die("⚠️ Invalid CSRF token");
    }

    if (empty($username) || empty($email)) {
        $errorMsg = "❌ Please fill in all required fields.";
    } else {
        try {
            // Check if email already exists in login table
            $stmt = $conn->prepare("SELECT email FROM logindb WHERE email = :email");
            $stmt->execute([':email' => $email]);

            if ($stmt->rowCount() > 0) {
                $errorMsg = "⚠️ This email is already registered!";
            } else {
                // Start transaction
                $conn->beginTransaction();

                // Insert into customers table
                $stmt1 = $conn->prepare("
                    INSERT INTO customers (customer_name, email, Phone, customer_Address, customer_Location, created_at)
                    VALUES (:name, :email, :phone, :addr, :loc, GETDATE())
                ");
                $stmt1->execute([
                    ':name'  => $username,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':addr'  => $address,
                    ':loc'   => $location
                ]);

                // Insert into logindb table
                $defaultPassword = password_hash("Welcome@123", PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("
                    INSERT INTO logindb (username, email, userpassword, user_status, user_role, created_at)
                    VALUES (:username, :email, :pass, 'active', 'customer', GETDATE())
                ");
                $stmt2->execute([
                    ':username' => $username,
                    ':email'    => $email,
                    ':pass'     => $defaultPassword
                ]);

                $conn->commit();
                $successMsg = "✅ Customer added successfully and login created!";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $errorMsg = "❌ Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create New Customer</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:'Segoe UI', Tahoma, sans-serif;
    background:#eef1f6;
    color:#2c3e50;
}

/* ===== HEADER ===== */
header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#2c3e50;
    color:#fff;
    padding:10px 25px;
    box-shadow:0 2px 8px rgba(0,0,0,0.25);
}
header .left {
    display:flex;
    align-items:center;
}
header img {
    height:45px;
    margin-right:15px;
}
header h1 {
    font-size:18px;
    font-weight:600;
    margin-bottom:2px;
}
header h2 {
    font-size:12px;
    color:#bdc3c7;
}
nav a {
    margin-left:18px;
    text-decoration:none;
    color:#ecf0f1;
    font-weight:500;
}
nav a:hover { color:#1abc9c; }

/* ===== MAIN CONTENT ===== */
main {
    max-width:600px;
    margin:40px auto;
    padding:20px;
}
h2 {
    text-align:center;
    margin-bottom:25px;
}
form {
    background:#fff;
    padding:30px;
    border-radius:12px;
    box-shadow:0 4px 16px rgba(0,0,0,0.1);
}
label {
    display:block;
    margin-top:15px;
    font-weight:600;
}
input, textarea {
    width:100%;
    padding:12px;
    margin-top:8px;
    border:1px solid #ccc;
    border-radius:8px;
    font-size:15px;
}
button {
    margin-top:25px;
    padding:14px;
    width:100%;
    background:linear-gradient(135deg,#2c3e50,#1f2d3a);
    color:#fff;
    border:none;
    border-radius:8px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
}
button:hover { background:#1a252f; }

.msg {
    text-align:center;
    font-weight:bold;
    margin:18px 0;
    padding:10px;
    border-radius:6px;
}
.success { color:#27ae60; background:#eafaf1; border:1px solid #2ecc71; }
.error { color:#e74c3c; background:#fdecea; border:1px solid #e57373; }
</style>
</head>

<body>
<header>
  <div class="left">
    <img src="VSP001.png" alt="Logo">
    <div>
      <h1>Vsmart Technologies Pvt Ltd</h1>
      <h2>Ticket Management System</h2>
    </div>
  </div>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="viewcustomer.php">View Customers</a>
    <a href="login.php">Logout</a>
  </nav>
</header>

<main>
  <h2>➕ Add New Customer</h2>

  <?php if ($successMsg): ?>
    <p class="msg success"><?= e($successMsg) ?></p>
  <?php elseif ($errorMsg): ?>
    <p class="msg error"><?= e($errorMsg) ?></p>
  <?php endif; ?>

  <form method="POST" action="">
    <label>Customer Name</label>
    <input type="text" name="customer_name" placeholder="Enter full name" required>

    <label>Email</label>
    <input type="email" name="email" placeholder="example@email.com" required>

    <label>Phone</label>
    <input type="text" name="phone" placeholder="+91-XXXXXXXXXX">

    <label>Address</label>
    <textarea name="customer_Address" placeholder="Enter address"></textarea>

    <label>Location</label>
    <input type="text" name="customer_Location" placeholder="Enter city/location">

    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
    <button type="submit">💾 Save Customer</button>
  </form>
</main>
</body>
</html>
