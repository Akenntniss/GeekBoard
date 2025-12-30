<?php
// Démarrer la session
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session si il existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire tous les cookies liés à l'application
$cookies_to_delete = ['user_id', 'shop_id', 'user_role', 'pwa_mode', 'darkMode', 'PHPSESSID'];
foreach ($cookies_to_delete as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
        setcookie($cookie, '', time() - 3600);
    }
}

// Finalement, détruire la session
session_destroy();

// Rediriger vers la page de login à la racine
header('Location: /login.php');
exit();
?>