<?php
// includes/navbar.php
$cart_count = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cart_count += isset($item['cantidad']) ? (int)$item['cantidad'] : 0;
    }
}

$is_logged_in = isset($_SESSION['usuario_id']);
$user_nombre = $is_logged_in ? $_SESSION['usuario_nombre'] : '';
$user_rol = $is_logged_in ? $_SESSION['usuario_rol'] : '';
?>

<nav class="navbar navbar-expand-lg navbar-dark navbar-xionox py-3">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <i class="bi bi-joystick text-info fs-3"></i>
            <span class="brand-logo tracking-wider">XioNox <span class="text-white">Store</span></span>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active text-info fw-bold' : '' ?>" href="index.php">
                        <i class="bi bi-grid-fill me-1"></i> Catálogo
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <!-- Botón Carrito -->
                <a href="carrito.php" class="btn btn-outline-neon position-relative d-flex align-items-center gap-2 px-3 py-2">
                    <i class="bi bi-cart3 fs-5"></i>
                    <span class="d-none d-sm-inline">Carrito</span>
                    <?php if ($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-dark cart-badge">
                            <?= $cart_count ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Autenticación -->
                <?php if ($is_logged_in): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-light dropdown-toggle d-flex align-items-center gap-2 py-2 px-3 rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5 text-info"></i>
                            <span class="fw-semibold text-truncate" style="max-width: 140px;"><?= htmlspecialchars($user_nombre) ?></span>
                            <span class="badge <?= $user_rol === 'admin' ? 'badge-admin' : 'badge-cliente' ?> text-uppercase ms-1" style="font-size: 0.65rem;">
                                <?= $user_rol ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg bg-surface border-secondary mt-2">
                            <li>
                                <div class="px-3 py-2 border-bottom border-secondary text-secondary small">
                                    Conectado como:<br>
                                    <strong class="text-white"><?= htmlspecialchars($_SESSION['usuario_email'] ?? '') ?></strong>
                                </div>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="dashboard.php">
                                    <i class="bi bi-speedometer2 text-info"></i> 
                                    <?= $user_rol === 'admin' ? 'Panel de Administración' : 'Mis Pedidos & Cuenta' ?>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider border-secondary"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light px-3 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                    </a>
                    <a href="register.php" class="btn btn-neon px-3 py-2">
                        <i class="bi bi-person-plus-fill me-1"></i> Registro
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Contenedor de Alertas Flash Globales -->
<div class="container mt-3">
    <?php
    $flash = getFlashMessage();
    if ($flash):
        $alert_icon = [
            'success' => 'bi-check-circle-fill',
            'danger'  => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            'info'    => 'bi-info-circle-fill'
        ][$flash['tipo']] ?? 'bi-bell-fill';
    ?>
        <div class="alert alert-<?= htmlspecialchars($flash['tipo']) ?> alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="bi <?= $alert_icon ?> fs-5 me-2"></i>
            <div><?= htmlspecialchars($flash['mensaje']) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>
