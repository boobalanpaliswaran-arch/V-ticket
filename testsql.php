<?php
$serverName = "BOOBALAN\SQLEXPRESS"; 
$connectionOptions = [
    "Database" => "vsmart",   // or your database name
    "Uid" => "sa",
    "PWD" => "admin@123",
    "TrustServerCertificate" => true // avoid SSL trust errors
];

// Try to connect
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn) {
    echo "✅ Connection successful!";
} else {
    echo "❌ Connection failed:<br>";
    die(print_r(sqlsrv_errors(), true));
}
?>
