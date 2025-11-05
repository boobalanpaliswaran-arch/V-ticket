<?php
// ======================================
// DB Connection (SQL Server)
// ======================================
$serverName = "BOOBALAN\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "vsmart",
    "Uid" => "sa",
    "PWD" => "admin@123",
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die("❌ DB Connection failed: " . print_r(sqlsrv_errors(), true));
}

// ======================================
// Check if ID is provided
// ======================================
if (!isset($_GET['id'])) {
    echo "<script>alert('❌ No customer ID provided.'); window.location='viewcustomer.php';</script>";
    exit;
}

$customer_id = intval($_GET['id']);

// ======================================
// Check if the customer exists
// ======================================
$checkSql = "SELECT customer_id FROM dbo.Customers WHERE customer_id = ?";
$checkStmt = sqlsrv_query($conn, $checkSql, [$customer_id]);

if ($checkStmt === false) {
    die("❌ Query Error: " . print_r(sqlsrv_errors(), true));
}

$customer = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
if (!$customer) {
    echo "<script>alert('⚠️ Customer not found or already deleted.'); window.location='viewcustomer.php';</script>";
    exit;
}

// ======================================
// Delete the customer
// ======================================
$deleteSql = "DELETE FROM dbo.Customers WHERE customer_id = ?";
$deleteStmt = sqlsrv_query($conn, $deleteSql, [$customer_id]);

if ($deleteStmt === false) {
    die("❌ Delete Error: " . print_r(sqlsrv_errors(), true));
} else {
    echo "<script>alert('✅ Customer deleted successfully!'); window.location='viewcustomer.php';</script>";
    exit;
}
?>
