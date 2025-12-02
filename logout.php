<?php
// logout.php
session_start();

// Store username before destroying session
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie
if (isset($_COOKIE['user_remember'])) {
    setcookie('user_remember', '', time() - 3600, "/");
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>