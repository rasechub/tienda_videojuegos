<?php
require_once __DIR__ . '/conexion.php';

// Limpiar todas las variables de sesión
$_SESSION = [];

// Si se usan cookies de sesión, destruirla
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Iniciar una nueva sesión temporal para pasar el mensaje de despedida
session_start();
setFlashMessage('info', "Has cerrado sesión correctamente. ¡Esperamos verte pronto!");

header("Location: login.php");
exit;
