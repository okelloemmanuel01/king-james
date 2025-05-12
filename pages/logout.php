<?php
// pages/logout.php
session_start();

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
    (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    die('Invalid CSRF token');
}

// Unset all session variables
$_SESSION = array();

// Destroy the session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to login page (index.php is outside pages folder)
header('Location: ../index.php?logout=success');
exit;
?>