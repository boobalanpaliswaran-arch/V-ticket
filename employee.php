<?php
// ==========================
// Secure Session Start
// ==========================
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ini_set('session.cookie_secure', 1);
session_start();
session_regenerate_id(true);

// CSRF Token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf_token = $_SESSION['csrf_token'];

// ==========================
// DB Connection
// ==========================
$serverName = "BOOBALAN\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "vsmart",
    "Uid" => "sa",
    "PWD" => "admin@123",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) die("⚠️ DB Connection failed: " . print_r(sqlsrv_errors(), true));

$successMsg = "";
$errorMsg = "";

// ==========================
// Handle POST Request
// ==========================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])
        die("⚠️ CSRF validation failed!");

    $id = (int)($_POST['id'] ?? 0);
    $emp_code = trim($_POST['emp_code'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $emp_phone = trim($_POST['emp_phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $emp_status = trim($_POST['emp_status'] ?? 'Active');
    $hire_date = trim($_POST['hire_date'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($emp_code === '' || $username === '' || $email === '') {
        $errorMsg = "⚠️ Employee Code, Name, and Email are required.";
    } else {
        if ($id > 0) {
            // Update
            $sql = "UPDATE employee SET EmpCode=?, username=?, Email=?, Phone=?, department=?, emp_status=?, HireDate=?, Remarks=?, EmergencyContact=? WHERE id=?";
            $params = [$emp_code, $username, $email, $emp_phone, $department, $emp_status, $hire_date, $remarks, $emergency_contact, $id];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt) {
                $successMsg = "✅ Employee updated successfully!";
                $sqlLogin = "UPDATE logindb SET username=?, email=?, user_status=? WHERE email=?";
                sqlsrv_query($conn, $sqlLogin, [$username, $email, $emp_status, $email]);
            } else {
                $errorMsg = "⚠️ Error updating employee.";
            }
        } else {
            // Insert new
            $sql = "INSERT INTO employee (EmpCode, username, Email, Phone, department, emp_status, HireDate, Remarks, EmergencyContact)
                    VALUES (?,?,?,?,?,?,?,?,?)";
            $params = [$emp_code, $username, $email, $emp_phone, $department, $emp_status, $hire_date, $remarks, $emergency_contact];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt) {
                $successMsg = "✅ Employee added successfully!";
                $hashedPassword = password_hash($password ?: "Welcome@123", PASSWORD_DEFAULT);
                $sqlLogin = "INSERT INTO logindb (username, email, userpassword, user_status) VALUES (?,?,?,?)";
                sqlsrv_query($conn, $sqlLogin, [$username, $email, $hashedPassword, $emp_status]);
            } else {
                $errorMsg = "⚠️ Error adding employee.";
            }
        }
    }
}

// Fetch record for edit
$editEmployee = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $sql = "SELECT * FROM employee WHERE id=?";
    $stmt = sqlsrv_query($conn, $sql, [$edit_id]);
    if ($stmt && sqlsrv_has_rows($stmt))
        $editEmployee = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
}

function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $editEmployee ? 'Edit Employee' : 'Add Employee' ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:'Segoe UI',sans-serif;
    background:#f4f6f9;
    color:#2c3e50;
}

/* ===== HEADER ===== */
header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#2c3e50;
    color:#fff;
    padding:10px 20px;
    box-shadow:0 3px 8px rgba(0,0,0,0.2);
}
header img{
    height:45px;
    width:auto;
    margin-right:12px;
}
header h1{
    font-size:18px;
    margin-bottom:2px;
}
header h2{
    font-size:12px;
    color:#bdc3c7;
    font-weight:normal;
}
nav a{
    color:#ecf0f1;
    text-decoration:none;
    margin-left:18px;
    font-weight:600;
}
nav a:hover{color:#f1c40f;}

/* ===== MAIN ===== */
main{
    max-width:700px;
    margin:40px auto;
    padding:20px;
}
h2{text-align:center;margin-bottom:20px;color:#2c3e50;}
form{
    background:#fff;
    padding:30px;
    border-radius:10px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}
label{
    display:block;
    margin-top:15px;
    font-weight:600;
}
input,textarea,select{
    width:100%;
    padding:10px;
    margin-top:6px;
    border:1px solid #ccc;
    border-radius:6px;
    font-size:14px;
}
input:focus,textarea:focus,select:focus{
    border-color:#2c3e50;
    box-shadow:0 0 4px rgba(44,62,80,0.3);
    outline:none;
}
button{
    width:100%;
    padding:12px;
    margin-top:25px;
    background:#2c3e50;
    color:#fff;
    font-size:16px;
    font-weight:bold;
    border:none;
    border-radius:6px;
    cursor:pointer;
    transition:0.3s;
}
button:hover{background:#1a252f;}

.msg{
    text-align:center;
    font-weight:bold;
    margin:15px 0;
    padding:10px;
    border-radius:6px;
}
.success{
    background:#dff0d8;
    color:#2e7d32;
    border:1px solid #c8e6c9;
}
.error{
    background:#f2dede;
    color:#a94442;
    border:1px solid #ebccd1;
}
</style>
</head>
<body>
<header>
  <div style="display:flex;align-items:center;">
    <img src="VSP001.png" alt="Logo">
    <div>
      <h1>Vsmart Technologies Pvt Ltd</h1>
      <h2>Employee Management System</h2>
    </div>
  </div>
  <nav>
    <a href="TicketManagementDashboard.php">Dashboard</a>
    <a href="EmployeeRecords.php">Employee Records</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main>
<h2><?= $editEmployee ? 'Edit Employee' : 'Add New Employee' ?></h2>

<?php if($successMsg) echo "<p class='msg success'>".e($successMsg)."</p>"; ?>
<?php if($errorMsg) echo "<p class='msg error'>".e($errorMsg)."</p>"; ?>

<form method="POST">
<input type="hidden" name="id" value="<?= e($editEmployee['id'] ?? '') ?>">
<input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">

<label>Employee Code</label>
<input type="text" name="emp_code" value="<?= e($editEmployee['EmpCode'] ?? '') ?>" required>

<label>Name</label>
<input type="text" name="username" value="<?= e($editEmployee['username'] ?? '') ?>" required>

<label>Email</label>
<input type="email" name="email" value="<?= e($editEmployee['Email'] ?? '') ?>" required>

<label>Phone</label>
<input type="text" name="emp_phone" value="<?= e($editEmployee['Phone'] ?? '') ?>">

<label>Department</label>
<input type="text" name="department" value="<?= e($editEmployee['department'] ?? '') ?>">

<label>Status</label>
<select name="emp_status">
    <option value="Active" <?= (isset($editEmployee['emp_status']) && $editEmployee['emp_status']=='Active')?"selected":"" ?>>Active</option>
    <option value="Inactive" <?= (isset($editEmployee['emp_status']) && $editEmployee['emp_status']=='Inactive')?"selected":"" ?>>Inactive</option>
</select>

<label>Hire Date</label>
<input type="date" name="hire_date" value="<?= e(isset($editEmployee['HireDate']) ? date('Y-m-d', strtotime($editEmployee['HireDate'])) : '') ?>">

<label>Emergency Contact</label>
<input type="text" name="emergency_contact" value="<?= e($editEmployee['EmergencyContact'] ?? '') ?>">

<label>Remarks</label>
<textarea name="remarks"><?= e($editEmployee['Remarks'] ?? '') ?></textarea>

<label>Password (optional)</label>
<input type="password" name="password" placeholder="<?= $editEmployee ? 'Leave blank to keep existing password' : 'Default: Welcome@123' ?>">

<button type="submit"><?= $editEmployee ? 'Update Employee' : 'Add Employee' ?></button>
</form>
</main>
</body>
</html>
