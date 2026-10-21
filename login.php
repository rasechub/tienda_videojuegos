<?php
require_once __DIR__ . '/conexion.php';

// Si ya tiene sesión activa, redirigir al panel
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$email_input = '';
$redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_input = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password_input = isset($_POST['password']) ? $_POST['password'] : '';
    $redirect_post = isset($_POST['redirect']) ? trim($_POST['redirect']) : '';

    if (empty($email_input) || empty($password_input)) {
        $error = "Por favor completa todos los campos requeridos.";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato de correo electrónico no es válido.";
    } else {
        // Consulta segura con PDO y Prepared Statement para prevenir Inyecciones SQL
        $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email_input]);
        $usuario = $stmt->fetch();

        // Validación segura con password_verify()
        if ($usuario && password_verify($password_input, $usuario['password'])) {
            // Regenerar ID de sesión para prevenir Session Fixation
            session_regenerate_id(true);

            $_SESSION['usuario_id'] = (int)$usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_rol'] = $usuario['rol'];

            setFlashMessage('success', "¡Bienvenido de nuevo, " . htmlspecialchars($usuario['nombre']) . "!");

            // Redirección segura
            if (!empty($redirect_post) && in_array($redirect_post, ['carrito.php', 'index.php', 'dashboard.php'])) {
                header("Location: " . $redirect_post);
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = "Credenciales incorrectas. Verifica tu correo electrónico y contraseña.";
        }
    }
}

$page_title = "Iniciar Sesión";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container my-5 flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="w-100" style="max-width: 480px;">
        <div class="card card-gaming p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-controller text-info" style="font-size: 3rem;"></i>
                <h1 class="h3 text-white fw-bold mt-2">Iniciar Sesión</h1>
                <p class="text-secondary small">Ingresa a tu cuenta de <span class="text-info fw-bold">XioNox Store</span></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center py-2 px-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label text-secondary small fw-semibold">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">
                            <i class="bi bi-envelope-at"></i>
                        </span>
                        <input type="email" class="form-control form-gaming-input" id="email" name="email" value="<?= htmlspecialchars($email_input) ?>" placeholder="nombre@correo.com" required autofocus>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="form-label text-secondary small fw-semibold">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <input type="password" class="form-control form-gaming-input" id="password" name="password" placeholder="Tu contraseña" required>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-neon w-100 py-3 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-in-right fs-5"></i> Acceder a la Plataforma
                </button>
            </form>

            <hr class="border-secondary border-opacity-25 my-4">

            <div class="text-center">
                <p class="text-secondary small mb-0">
                    ¿Aún no tienes una cuenta? <a href="register.php" class="text-info text-decoration-none fw-bold">Regístrate aquí</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
