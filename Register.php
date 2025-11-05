<?php
session_start();

// -----------------------------
// Database connection (PDO with SQL Server)
// -----------------------------
$serverName = "BOOBALAN\\SQLEXPRESS";   // change if needed
$dbName     = "vsmart";                 // your database name
$username   = "sa";                     // SQL Server login
$password   = "admin@123";              // SQL Server password

$dsn = "sqlsrv:Server=$serverName;Database=$dbName";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// -----------------------------
// Security helpers
// -----------------------------
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

// -----------------------------
// Handle registration
// -----------------------------
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validate_csrf();

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'customer'; // allow role selection

    // Basic validation
    if (!preg_match("/^[a-zA-Z0-9_]{3,20}$/", $username)) {
        $error = "❌ Username must be 3–20 chars, letters/numbers/underscore only.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Invalid email format.";
    } elseif (strlen($password) < 8) {
        $error = "❌ Password must be at least 8 characters.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM logindb WHERE email = :email");
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $error = "❌ Email already registered!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $insert = $pdo->prepare("INSERT INTO logindb 
                (username, email, userpassword, user_status, user_role, created_at) 
                VALUES (:username, :email, :pwd, 'active', :role, GETDATE())");

            $ok = $insert->execute([
                'username' => $username,
                'email'    => $email,
                'pwd'      => $hashed_password,
                'role'     => $role
            ]);

            if ($ok) {
                // ✅ clickable success link
                $success = "✅ Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $error = "❌ Registration failed. Please try again later.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Ticket Management</title>
<style>
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: linear-gradient(135deg, #2980b9, #2c3e50);
    margin: 0; padding: 0; color: #333;
}
header {
    background: #1a252f; color: white; padding: 15px;
    text-align: center; box-shadow: 0 3px 6px rgba(0,0,0,0.2);
}
header h1 { margin: 5px 0 0; font-size: 24px; }
header h2 { margin: 5px 0 0; font-size: 16px; font-weight: normal; }
.logo { height: 50px; display: block; margin: 0 auto 10px auto; }
form {
    max-width: 400px; margin: 40px auto;
    background: white; padding: 30px;
    border-radius: 12px; box-shadow: 0 6px 12px rgba(0,0,0,0.2);
}
label { display: block; margin-top: 15px; font-weight: bold; color: #2c3e50; }
input, select { width: 100%; padding: 10px; margin-top: 5px;
        border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
button {
    margin-top: 20px; padding: 12px; width: 100%;
    background: #2980b9; color: white; border: none; border-radius: 8px;
    font-size: 16px; font-weight: bold; cursor: pointer;
    transition: background 0.3s ease-in-out;
}
button:hover { background: #1f5f85; }
.msg { text-align: center; font-weight: bold;
       margin: 15px; padding: 10px; border-radius: 6px; }
.success { background: #d4edda; color: #155724; }
.error { background: #f8d7da; color: #721c24; }
p { text-align: center; margin-top: 20px; color: white; }
p a { color: #f1c40f; font-weight: bold; text-decoration: none; }
p a:hover { text-decoration: underline; }
</style>
</head>
<body>

<header>
    <img src="VSP001.png" alt="Ticket Logo" class="logo">
    <h1>VSMART Technologies Pvt Ltd</h1>
    <h2>Ticket Management System - Registration</h2>
</header>

<?php 
if ($error)   echo "<p class='msg error'>".clean($error)."</p>";
if ($success) echo "<p class='msg success'>$success</p>"; // no clean()
?>

<form method="POST">
    <?= csrf_field() ?>
    <label>Username</label>
    <input type="text" name="username" required>

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <label>Role</label>
    <select name="role">
        <option value="customer" selected>Customer</option>
        <option value="support">Support</option>
        <option value="admin">Admin</option>
        <option value="welcome">Welcome</option>
    </select>

    <button type="submit">Register</button>
</form>

<p>Already have an account? <a href="login.php">Login here</a></p>

</body>
</html>
