<?php
// ----------------------
// SECURITY HEADERS
// ----------------------
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// ----------------------
// DESTROY SESSION
// ----------------------
session_start();
$_SESSION = []; // Clear session data

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Fully destroy

// ----------------------
// REDIRECT TO LOGIN
// ----------------------
header("Location: login.php");
exit;
?>
