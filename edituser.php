<?php
// ----------------------
// Session + CSRF
// ----------------------
session_start();

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars(csrf_token()).'">';
}
function validate_csrf() {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        exit("⚠️ CSRF validation failed");
    }
}
function clean($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ----------------------
// Database connection (MSSQL)
// ----------------------
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

// ----------------------
// Get user ID
// ----------------------
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("❌ Invalid User ID.");

// ----------------------
// Fetch user
// ----------------------
$sql = "SELECT * FROM logindb WHERE id = ?";
$params = [$id];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false || !sqlsrv_has_rows($stmt)) {
    die("❌ User not found.");
}
$user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

$successMsg = "";
$errorMsg   = "";

// ----------------------
// Handle POST actions
// ----------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validate_csrf();

    // ✅ Handle Delete
    if (isset($_POST['delete_user'])) {
        $del_sql = "DELETE FROM logindb WHERE id = ?";
        sqlsrv_query($conn, $del_sql, [$id]);
        header("Location: viewuser.php?msg=User+Deleted+Successfully");
        exit();
    }

    // ✅ Handle Update
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['user_role'] ?? 'customer';
    $status   = $_POST['user_status'] ?? 'Inactive';
    $password = $_POST['password'] ?? '';

    if (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) {
        $errorMsg = "❌ Username must be 3–20 chars, letters/numbers/underscore only.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "❌ Invalid email format.";
    } else {
        $update_sql = "UPDATE logindb SET username=?, email=?, user_role=?, user_status=?";
        $params = [$username, $email, $role, $status];

        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_sql .= ", userpassword=?";
            $params[] = $hashed_password;
        }

        $update_sql .= " WHERE id=?";
        $params[] = $id;

        $update_stmt = sqlsrv_query($conn, $update_sql, $params);
        if ($update_stmt) {
            $successMsg = "✅ User updated successfully!";
            // reload user data
            $stmt = sqlsrv_query($conn, "SELECT * FROM logindb WHERE id=?", [$id]);
            $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $errorMsg = "❌ Error updating user: " . print_r(sqlsrv_errors(), true);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:"Segoe UI", Arial, sans-serif; background:#eef2f7; color:#333; }
header { display:flex; align-items:center; justify-content:space-between; background:#2c3e50; color:#fff; padding:15px 25px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
header img.logo { height:45px; }
.company-info { flex:1; text-align:center; }
.company-info h1 { font-size:20px; }
.company-info h2 { font-size:13px; font-weight:normal; color:#ddd; }
nav a { margin-left:15px; color:#fff; font-weight:bold; text-decoration:none; }
nav a:hover { color:#f1c40f; }
h2 { text-align:center; margin:25px 0 15px; font-size:26px; color:#2c3e50; }
.form-container { max-width:500px; margin:0 auto; background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
label { display:block; margin-top:15px; font-weight:600; color:#2c3e50; }
input, select { width:100%; padding:10px; margin-top:6px; border:1px solid #ccc; border-radius:6px; font-size:14px; transition:border 0.3s; }
input:focus, select:focus { border-color:#3498db; outline:none; }
button { width:100%; padding:12px; margin-top:18px; font-size:15px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; transition:0.3s; }
.update-btn { background:#3498db; color:#fff; }
.update-btn:hover { background:#217dbb; }
.delete-btn { background:#e74c3c; color:#fff; }
.delete-btn:hover { background:#c0392b; }
.msg { text-align:center; margin:15px auto; padding:12px; border-radius:6px; width:90%; max-width:500px; font-weight:bold; }
.success { background:#dff0d8; color:#3c763d; border:1px solid #d6e9c6; }
.error { background:#f2dede; color:#a94442; border:1px solid #ebccd1; }
</style>
</head>
<body>

<header>
  <img src="VSP001.png" alt="Ticket Logo" class="logo">
  <div class="company-info">
    <h1>Vsmart Technologies Pvt Ltd</h1>
    <h2>Ticket Management System</h2>
  </div>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="viewuser.php">View Users</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<h2>Edit User</h2>

<?php if($successMsg) echo "<p class='msg success'>".clean($successMsg)."</p>"; ?>
<?php if($errorMsg) echo "<p class='msg error'>".clean($errorMsg)."</p>"; ?>

<div class="form-container">
  <form method="POST">
      <?= csrf_field() ?>
      <label>Username</label>
      <input type="text" name="username" value="<?= clean($user['username']) ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= clean($user['email']) ?>" required>

      <label>Role</label>
      <select name="user_role" required>
          <option value="admin" <?= $user['user_role']=='admin'?'selected':'' ?>>Admin</option>
          <option value="support" <?= $user['user_role']=='support'?'selected':'' ?>>Support</option>
          <option value="customer" <?= $user['user_role']=='customer'?'selected':'' ?>>Customer</option>
      </select>

      <label>Status</label>
      <select name="user_status" required>
          <option value="Active" <?= $user['user_status']=='Active'?'selected':'' ?>>Active</option>
          <option value="Inactive" <?= $user['user_status']=='Inactive'?'selected':'' ?>>Inactive</option>
      </select>

      <label>New Password (leave blank to keep current)</label>
      <input type="password" name="password" placeholder="Enter new password">

      <button type="submit" class="update-btn">Update User</button>
  </form>

  <!-- Delete User Button -->
  <form method="POST" onsubmit="return confirm('⚠️ Are you sure you want to delete this user?');">
      <?= csrf_field() ?>
      <input type="hidden" name="delete_user" value="1">
      <button type="submit" class="delete-btn">Delete User</button>
  </form>
</div>

</body>
</html>
