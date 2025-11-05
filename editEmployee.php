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
// CSRF TOKEN
// ----------------------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$successMsg = $errorMsg = "";

// ----------------------
// Handle POST Add/Edit
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("⚠️ CSRF validation failed!");
    }

    $id             = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $emp_code       = $_POST['emp_code'] ?? '';
    $username       = $_POST['username'] ?? '';
    $emp_email      = $_POST['emp_email'] ?? '';
    $emp_phone      = $_POST['emp_phone'] ?? '';
    $emp_emergency  = $_POST['emp_emergency'] ?? '';
    $department     = $_POST['department'] ?? '';
    $emp_status     = $_POST['emp_status'] ?? 'Active';
    $hire_date      = $_POST['hire_date'] ?? null;
    $remarks        = $_POST['remarks'] ?? '';

    try {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE employee SET 
                EmpCode = ?, username = ?, Email = ?, Phone = ?, EmergencyContact = ?, department = ?, 
                emp_status = ?, HireDate = ?, Remarks = ? WHERE id = ?");
            $stmt->execute([$emp_code,$username,$emp_email,$emp_phone,$emp_emergency,$department,$emp_status,$hire_date,$remarks,$id]);
            $successMsg = "✅ Employee updated successfully!";
        } else {
            $stmt = $conn->prepare("INSERT INTO employee
                (EmpCode, username, Email, Phone, EmergencyContact, department, emp_status, HireDate, Remarks)
                VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$emp_code,$username,$emp_email,$emp_phone,$emp_emergency,$department,$emp_status,$hire_date,$remarks]);
            $successMsg = "✅ Employee added successfully!";
        }
    } catch (PDOException $e) {
        $errorMsg = "❌ Something went wrong: " . $e->getMessage();
    }
}

// ----------------------
// Fetch record for edit
// ----------------------
$editEmployee = null;
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM employee WHERE id = ?");
    $stmt->execute([$id]);
    $editEmployee = $stmt->fetch();
}

// ----------------------
// Helper
// ----------------------
function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $editEmployee ? "Edit Employee" : "Add Employee" ?></title>
<style>
/* ===== Global Styles ===== */
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
    background: #eef2f7;
    color: #333;
}

/* ===== Header ===== */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #2c3e50;
    color: white;
    padding: 8px 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
header .logo-container {
    display: flex;
    align-items: center;
    gap: 10px;
}
header img {
    height: 40px;
    width: auto;
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

/* ===== Main Form ===== */
main {
    max-width: 650px;
    margin: 40px auto;
    padding: 20px;
}
h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 15px;
}
form {
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
label {
    display: block;
    margin-top: 15px;
    font-weight: 600;
}
input, select, textarea {
    width: 100%;
    padding: 10px;
    margin-top: 6px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
}
button {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 6px;
    background: #2c3e50;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
}
button:hover {
    background: #1a242f;
}
.msg {
    text-align: center;
    font-weight: bold;
    margin: 15px 0;
}
.success { color: #27ae60; }
.error { color: #e74c3c; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
    header h1 { font-size: 16px; }
    header img { height: 32px; }
}
</style>
</head>
<body>

<header>
    <div class="logo-container">
        <img src="VSP001.png" alt="Company Logo">
        <div>
            <h1>Vsmart Technologies Pvt Ltd</h1>
            <h2>Employee Management System</h2>
        </div>
    </div>
    <nav>
        <a href="EmployeeRecords.php">Employee Records</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
<h2><?= $editEmployee ? "Edit Employee" : "Add Employee" ?></h2>

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
    <input type="email" name="emp_email" value="<?= e($editEmployee['Email'] ?? '') ?>" required>

    <label>Phone</label>
    <input type="text" name="emp_phone" value="<?= e($editEmployee['Phone'] ?? '') ?>">

    <label>Emergency Contact</label>
    <input type="text" name="emp_emergency" value="<?= e($editEmployee['EmergencyContact'] ?? '') ?>">

    <label>Department</label>
    <input type="text" name="department" value="<?= e($editEmployee['department'] ?? '') ?>">

    <label>Status</label>
    <select name="emp_status">
        <option value="Active" <?= (isset($editEmployee['emp_status']) && $editEmployee['emp_status']=='Active')?"selected":"" ?>>Active</option>
        <option value="Inactive" <?= (isset($editEmployee['emp_status']) && $editEmployee['emp_status']=='Inactive')?"selected":"" ?>>Inactive</option>
    </select>

    <label>Hire Date</label>
    <input type="date" name="hire_date" value="<?= e(isset($editEmployee['HireDate']) ? date('Y-m-d', strtotime($editEmployee['HireDate'])) : '') ?>">

    <label>Remarks</label>
    <textarea name="remarks"><?= e($editEmployee['Remarks'] ?? '') ?></textarea>

    <button type="submit"><?= $editEmployee ? "Update Employee" : "Add Employee" ?></button>
</form>
</main>

</body>
</html>
