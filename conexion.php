<?php
/**
 * Conexión centralizada a la Base de Datos MySQL usando PDO
 * XioNox Store
 */

// Iniciar sesión global si aún no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Parámetros de conexión (Variables de entorno con valores por defecto para Docker)
$db_host = getenv('DB_HOST') ?: 'db';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'xionox_store';
$db_user = getenv('DB_USER') ?: 'xionox_user';
$db_pass = getenv('DB_PASS') ?: 'xionox_password';
$charset = 'utf8mb4';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanzar excepciones en caso de error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devolver arreglos asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Consultas preparadas reales nativas
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}'"
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Manejo amigable y seguro del error sin filtrar credenciales sensibles
    $error_msg = "Error crítico de conexión a la base de datos: " . $e->getMessage();
    
    // Si estamos en un contexto web, mostramos un aviso estilizado
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        die("
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Error de Conexión - XioNox Store</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body class='bg-dark text-light d-flex align-items-center min-vh-100'>
            <div class='container text-center'>
                <div class='card bg-secondary text-white border-danger mx-auto shadow-lg' style='max-width: 600px;'>
                    <div class='card-header bg-danger text-white py-3'>
                        <h4 class='mb-0'>⚠️ Error de Conexión con el Servidor</h4>
                    </div>
                    <div class='card-body p-4'>
                        <p class='lead'>No se pudo establecer conexión con MySQL.</p>
                        <p class='text-warning small'>Asegúrate de que el contenedor de base de datos esté activo ejecutando <code>docker compose up -d</code> y que haya terminado de inicializarse.</p>
                        <div class='alert alert-dark text-start font-monospace small'>
                            " . htmlspecialchars($e->getMessage()) . "
                        </div>
                        <a href='' class='btn btn-outline-light mt-3' onclick='window.location.reload();'>Reintentar Conexión</a>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ");
    } else {
        die($error_msg . PHP_EOL);
    }
}

/**
 * Funciones auxiliares para Mensajes Flash (Notificaciones entre redirecciones)
 */
function setFlashMessage($tipo, $mensaje) {
    $_SESSION['flash'] = [
        'tipo' => $tipo, // success, danger, warning, info
        'mensaje' => $mensaje
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
