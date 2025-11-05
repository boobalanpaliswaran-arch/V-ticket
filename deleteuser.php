<?php
// deleteuser.php

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "vsmart"; // your DB name

$conn = new mysqli($servername, $username, $password, $dbname);

// ✅ Check connection
if ($conn->connect_error) {
    die("❌ DB Connection failed: " . $conn->connect_error);
}

// ✅ Get user ID safely
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("❌ Invalid User ID.");
}

// ✅ Delete query
$sql = "DELETE FROM logindb WHERE id=$id";
if ($conn->query($sql) === TRUE) {
    // Redirect back to user list with success message
    header("Location: viewuser.php?msg=User+deleted+successfully");
    exit();
} else {
    die("❌ Error deleting user: " . $conn->error);
}

$conn->close();
?>
