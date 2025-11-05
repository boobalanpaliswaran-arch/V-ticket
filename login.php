<?php 
session_start();

// -----------------------------
// Database connection (PDO with SQL Server)
// -----------------------------
$serverName = "BOOBALAN\\SQLEXPRESS";   // your SQL Server instance
$dbName     = "vsmart";                 // your database name
$username   = "sa";                     // SQL login
$password   = "admin@123";              // SQL password

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
// Handle login
// -----------------------------
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    validate_csrf(); // ✅ protect against CSRF

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // ✅ SQL Server syntax (use TOP 1 instead of OFFSET/LIMIT)
    $stmt = $pdo->prepare("SELECT TOP 1 id, username, email, userpassword, user_status, user_role 
                           FROM logindb 
                           WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    if ($row) {
        // ✅ Use password_verify if your DB stores hashed passwords
        if (password_verify($password, $row['userpassword'])) {
            if (strtolower($row['user_status']) !== 'active') {
                $error = "❌ Your account is inactive. Contact admin.";
            } else {
                // Secure session
                session_regenerate_id(true);
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email']    = $row['email'];
                $_SESSION['role']     = $row['user_role'];

                // ✅ Role-based redirect
                switch (strtolower($row['user_role'])) {
                    case 'admin':
                        header("Location: TicketManagementDashboard.php"); exit;
                    case 'support':
                        header("Location: support_dashboard.php"); exit;
                    case 'customer':
                        header("Location: customer_dashboard.php"); exit;
                    case 'welcome':
                        header("Location: welcome.html"); exit;
                    default:
                        $error = "❌ Unknown role assigned. Contact admin.";
                }
            }
        } else {
            $error = "❌ Invalid email or password!";
        }
    } else {
        $error = "❌ Invalid email or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Ticket Management</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
               background: linear-gradient(135deg, #3498db, #8e44ad);
               margin: 0; padding: 0; height: 100vh;
               display: flex; align-items: center; justify-content: center; }
        .login-card { background: #fff; padding: 30px 25px;
                      border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.15);
                      width: 100%; max-width: 380px; text-align: center; }
        .login-card h2 { margin-bottom: 20px; color: #333; }
        .login-card label { float: left; margin: 10px 0 5px;
                            font-weight: 600; color: #555; }
        .login-card input { width: 90%; padding: 12px; margin-bottom: 15px;
                            border: 1px solid #ccc; border-radius: 8px; font-size: 14px; }
        .login-card button { width: 100%; padding: 12px; background: #3498db;
                             color: #fff; border: none; border-radius: 8px;
                             font-size: 16px; cursor: pointer; transition: 0.3s; }
        .login-card button:hover { background: #2980b9; }
        .msg { margin: 10px 0; font-weight: bold; color: red; }
    </style>
</head>
<body>
<div class="login-card">
    <h2>🔐 Vsmart  Ticket Management System</h2>
    <?php if($error) echo "<p class='msg'>".clean($error)."</p>"; ?>

    <form method="POST">
        <?= csrf_field() ?>
        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>

    <p>Don’t have an account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>
