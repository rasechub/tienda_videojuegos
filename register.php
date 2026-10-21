<?php
require_once __DIR__ . '/conexion.php';

// Si ya tiene sesión activa, redirigir al panel
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$nombre_input = '';
$email_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_input = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $email_input = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($nombre_input) || empty($email_input) || empty($password) || empty($confirm_password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato de correo electrónico ingresado no es válido.";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Verificar si el correo ya está registrado usando Prepared Statement
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email_input]);
        if ($stmt->fetch()) {
            $error = "El correo electrónico ya se encuentra registrado. Intenta iniciar sesión.";
        } else {
            // Encriptación obligatoria con password_hash()
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Inserción segura con rol 'cliente'
            $stmt_insert = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (:nombre, :email, :password, 'cliente')");
            $stmt_insert->execute([
                ':nombre'   => $nombre_input,
                ':email'    => $email_input,
                ':password' => $password_hash
            ]);

            setFlashMessage('success', "¡Registro exitoso! Ya puedes iniciar sesión con tu cuenta de cliente.");
            header("Location: login.php");
            exit;
        }
    }
}

$page_title = "Registro de Cliente";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container my-5 flex-grow-1 d-flex align-items-center justify-content-center">
    <div class="w-100" style="max-width: 500px;">
        <div class="card card-gaming p-4 p-md-5">
            <div class="text-center mb-4">
                <i class="bi bi-person-plus text-info" style="font-size: 3rem;"></i>
                <h1 class="h3 text-white fw-bold mt-2">Crear Cuenta</h1>
                <p class="text-secondary small">Únete a la comunidad de <span class="text-info fw-bold">XioNox Store</span></p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center py-2 px-3 mb-4" role="alert">
                    <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <!-- Nombre Completo -->
                <div class="mb-3">
                    <label for="nombre" class="form-label text-secondary small fw-semibold">Nombre Completo</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" class="form-control form-gaming-input" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre_input) ?>" placeholder="Ej: John Doe" required autofocus>
                    </div>
                </div>

                <!-- Correo Electrónico -->
                <div class="mb-3">
                    <label for="email" class="form-label text-secondary small fw-semibold">Correo Electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">
                            <i class="bi bi-envelope-at"></i>
                        </span>
                        <input type="email" class="form-control form-gaming-input" id="email" name="email" value="<?= htmlspecialchars($email_input) ?>" placeholder="nombre@correo.com" required>
                    </div>
                </div>

                <!-- Contraseña -->
                <div class="mb-3">
                    <label for="password" class="form-label text-secondary small fw-semibold">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">
                            <i class="bi bi-shield-lock"></i>
                        </span>
                        <input type="password" class="form-control form-gaming-input" id="password" name="password" placeholder="Mínimo 6 caracteres" minlength="6" required>
                    </div>
                </div>

                <!-- Confirmar Contraseña -->
                <div class="mb-4">
                    <label for="confirm_password" class="form-label text-secondary small fw-semibold">Confirmar Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-secondary">
                            <i class="bi bi-shield-check"></i>
                        </span>
                        <input type="password" class="form-control form-gaming-input" id="confirm_password" name="confirm_password" placeholder="Repite tu contraseña" minlength="6" required>
                    </div>
                </div>

                <!-- Botón Registrarse -->
                <button type="submit" class="btn btn-neon w-100 py-3 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-person-check-fill fs-5"></i> Completar Registro
                </button>
            </form>

            <hr class="border-secondary border-opacity-25 my-4">

            <div class="text-center">
                <p class="text-secondary small mb-0">
                    ¿Ya tienes una cuenta registrada? <a href="login.php" class="text-info text-decoration-none fw-bold">Inicia sesión</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
