<?php
session_start();

// Accept POST (from the dashboard form). For other methods, still destroy session.
// Destroy session and remove session cookie
$_SESSION = array();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

session_destroy();

// Optionally clear any application-specific cookies
setcookie('remember_user', '', time() - 3600, '/');

// Redirect to index page (at root, two levels up from actions/)
header('Location: ../../index.php');
exit();

?>
