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

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die("❌ Invalid CSRF token. Operation not allowed.");
}

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
// DELETE EMPLOYEE SAFELY
// =========================
if (!empty($_POST['id']) && is_numeric($_POST['id'])) {
    $id = (int)$_POST['id'];
    try {
        $stmt = $conn->prepare("DELETE FROM employee WHERE id = :id");
        $stmt->execute([':id' => $id]);

        // Redirect back with success message
        header("Location: EmployeeRecords.php?msg=deleted");
        exit;
    } catch (PDOException $e) {
        // Redirect back with error message
        header("Location: EmployeeRecords.php?msg=error");
        exit;
    }
} else {
    // Redirect back with invalid ID error
    header("Location: EmployeeRecords.php?msg=invalid");
    exit;
}
?>
