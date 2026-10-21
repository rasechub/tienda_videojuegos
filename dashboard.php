<?php
require_once __DIR__ . '/conexion.php';

// Verificar sesión activa
if (!isset($_SESSION['usuario_id'])) {
    setFlashMessage('warning', "Debes iniciar sesión para acceder al panel.");
    header("Location: login.php");
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$usuario_rol = $_SESSION['usuario_rol'] ?? 'cliente';
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuario_email = $_SESSION['usuario_email'] ?? '';

// ==============================================================
// PROCESAMIENTO DE ACCIONES PARA EL ROL ADMINISTRADOR
// ==============================================================
if ($usuario_rol === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Agregar nuevo producto
    if ($action === 'create_product') {
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = (float)($_POST['precio'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $imagen_url = trim($_POST['imagen_url'] ?? '');

        if (empty($titulo) || empty($descripcion) || $precio <= 0 || $stock < 0) {
            setFlashMessage('danger', "Por favor completa todos los campos con valores válidos.");
        } else {
            if (empty($imagen_url)) {
                $imagen_url = 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80';
            }
            $stmt = $pdo->prepare("INSERT INTO productos (titulo, descripcion, precio, stock, imagen_url) VALUES (:titulo, :descripcion, :precio, :stock, :imagen_url)");
            $stmt->execute([
                ':titulo'      => $titulo,
                ':descripcion' => $descripcion,
                ':precio'      => $precio,
                ':stock'       => $stock,
                ':imagen_url'  => $imagen_url
            ]);
            setFlashMessage('success', "El videojuego '{$titulo}' fue añadido exitosamente al catálogo.");
        }
        header("Location: dashboard.php");
        exit;
    }

    // 2. Actualizar Stock Rápido
    if ($action === 'update_stock') {
        $prod_id = (int)($_POST['producto_id'] ?? 0);
        $nuevo_stock = max(0, (int)($_POST['stock'] ?? 0));

        if ($prod_id > 0) {
            $stmt = $pdo->prepare("UPDATE productos SET stock = :stock WHERE id = :id");
            $stmt->execute([':stock' => $nuevo_stock, ':id' => $prod_id]);
            setFlashMessage('success', "Stock del producto #{$prod_id} actualizado a {$nuevo_stock} unidades.");
        }
        header("Location: dashboard.php");
        exit;
    }

    // 3. Editar Producto Completo
    if ($action === 'edit_product') {
        $prod_id = (int)($_POST['producto_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $precio = (float)($_POST['precio'] ?? 0);
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $imagen_url = trim($_POST['imagen_url'] ?? '');

        if ($prod_id > 0 && !empty($titulo) && $precio > 0) {
            $stmt = $pdo->prepare("UPDATE productos SET titulo = :titulo, descripcion = :descripcion, precio = :precio, stock = :stock, imagen_url = :imagen_url WHERE id = :id");
            $stmt->execute([
                ':titulo'      => $titulo,
                ':descripcion' => $descripcion,
                ':precio'      => $precio,
                ':stock'       => $stock,
                ':imagen_url'  => $imagen_url,
                ':id'          => $prod_id
            ]);
            setFlashMessage('success', "Producto #{$prod_id} modificado correctamente.");
        } else {
            setFlashMessage('danger', "Datos no válidos para actualizar el producto.");
        }
        header("Location: dashboard.php");
        exit;
    }

    // 4. Eliminar Producto
    if ($action === 'delete_product') {
        $prod_id = (int)($_POST['producto_id'] ?? 0);
        if ($prod_id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM productos WHERE id = :id");
                $stmt->execute([':id' => $prod_id]);
                setFlashMessage('success', "Producto #{$prod_id} eliminado exitosamente.");
            } catch (PDOException $e) {
                setFlashMessage('warning', "No es posible eliminar el producto porque tiene pedidos asociados. Puedes cambiar su stock a 0 en su lugar.");
            }
        }
        header("Location: dashboard.php");
        exit;
    }

    // 5. Actualizar Estado de un Pedido
    if ($action === 'update_order_status') {
        $pedido_id = (int)($_POST['pedido_id'] ?? 0);
        $nuevo_estado = $_POST['nuevo_estado'] ?? '';

        if ($pedido_id > 0 && in_array($nuevo_estado, ['Pendiente', 'Completado'])) {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = :estado WHERE id = :id");
            $stmt->execute([':estado' => $nuevo_estado, ':id' => $pedido_id]);
            setFlashMessage('success', "Estado del Pedido #{$pedido_id} actualizado a '{$nuevo_estado}'.");
        }
        header("Location: dashboard.php");
        exit;
    }
}

// ==============================================================
// CONSULTAS SEGÚN EL ROL
// ==============================================================
if ($usuario_rol === 'admin') {
    $total_pedidos  = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
    $total_ventas   = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM pedidos")->fetchColumn();
    $total_productos = $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    $total_clientes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'cliente'")->fetchColumn();

    $stmt_prods = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
    $admin_productos = $stmt_prods->fetchAll();

    $stmt_orders = $pdo->query("
        SELECT p.id, p.fecha, p.total, p.estado, u.nombre AS cliente_nombre, u.email AS cliente_email
        FROM pedidos p
        JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.id DESC
    ");
    $admin_pedidos = $stmt_orders->fetchAll();

    $stmt_detalles = $pdo->query("
        SELECT d.pedido_id, d.cantidad, d.precio_unitario, pr.titulo, pr.imagen_url
        FROM pedido_detalle d
        JOIN productos pr ON d.producto_id = pr.id
    ");
    $detalles_agrupados = [];
    while ($row = $stmt_detalles->fetch()) {
        $detalles_agrupados[$row['pedido_id']][] = $row;
    }

} else {
    $stmt_cliente_pedidos = $pdo->prepare("
        SELECT id, fecha, total, estado
        FROM pedidos
        WHERE usuario_id = :uid
        ORDER BY id DESC
    ");
    $stmt_cliente_pedidos->execute([':uid' => $usuario_id]);
    $mis_pedidos = $stmt_cliente_pedidos->fetchAll();

    $stmt_detalles = $pdo->prepare("
        SELECT d.pedido_id, d.cantidad, d.precio_unitario, pr.titulo, pr.imagen_url
        FROM pedido_detalle d
        JOIN pedidos p ON d.pedido_id = p.id
        JOIN productos pr ON d.producto_id = pr.id
        WHERE p.usuario_id = :uid
    ");
    $stmt_detalles->execute([':uid' => $usuario_id]);
    $detalles_agrupados = [];
    while ($row = $stmt_detalles->fetch()) {
        $detalles_agrupados[$row['pedido_id']][] = $row;
    }
}

$page_title = ($usuario_rol === 'admin') ? "Panel de Administración" : "Mi Cuenta";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container my-5 flex-grow-1">
    <!-- Header del Dashboard -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom border-secondary border-opacity-25">
        <div>
            <h1 class="h2 text-white mb-1 d-flex align-items-center gap-2">
                <i class="bi <?= $usuario_rol === 'admin' ? 'bi-shield-shaded text-danger' : 'bi-person-badge text-info' ?>"></i>
                <?= $usuario_rol === 'admin' ? 'Centro de Control Administrativo' : 'Panel de Cliente' ?>
            </h1>
            <p class="text-secondary small mb-0">
                Bienvenido, <strong class="text-white"><?= htmlspecialchars($usuario_nombre) ?></strong> (<?= htmlspecialchars($usuario_email) ?>)
            </p>
        </div>
        <div>
            <span class="badge <?= $usuario_rol === 'admin' ? 'badge-admin' : 'badge-cliente' ?> p-2 fs-6 text-uppercase">
                <i class="bi bi-person-fill me-1"></i> Rol: <?= $usuario_rol ?>
            </span>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- VISTA CLIENTE                                                    -->
    <!-- ============================================================== -->
    <?php if ($usuario_rol === 'cliente'): ?>
        <div class="row g-4">
            <div class="col-12">
                <div class="card card-gaming p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="h4 text-white mb-0">
                            <i class="bi bi-clock-history text-info me-2"></i> Mi Historial de Pedidos
                        </h3>
                        <a href="index.php" class="btn btn-outline-neon btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Nuevo Pedido
                        </a>
                    </div>

                    <?php if (empty($mis_pedidos)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-box2 text-secondary" style="font-size: 4rem;"></i>
                            <h4 class="text-white mt-3">Aún no has realizado ningún pedido</h4>
                            <p class="text-secondary">Explora nuestro catálogo y adquiere tus títulos favoritos con entrega inmediata.</p>
                            <a href="index.php" class="btn btn-neon mt-2">Ir a Comprar</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-gaming align-middle">
                                <thead>
                                    <tr>
                                        <th># Pedido</th>
                                        <th>Fecha y Hora</th>
                                        <th>Total Facturado</th>
                                        <th>Estado</th>
                                        <th class="text-center">Detalles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mis_pedidos as $pedido): ?>
                                        <tr>
                                            <td class="fw-bold text-info font-monospace">#<?= str_pad($pedido['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></td>
                                            <td class="fw-bold font-monospace">S/ <?= number_format((float)$pedido['total'], 2) ?></td>
                                            <td>
                                                <?php if ($pedido['estado'] === 'Completado'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                        <i class="bi bi-check-all me-1"></i> Completado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">
                                                        <i class="bi bi-hourglass-split me-1"></i> Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalPedido<?= $pedido['id'] ?>">
                                                    <i class="bi bi-eye-fill me-1"></i> Ver Productos
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- MODALES de detalle de pedido FUERA de la tabla -->
        <?php foreach ($mis_pedidos as $pedido): ?>
            <div class="modal fade" id="modalPedido<?= $pedido['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-surface border-secondary">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title text-white">Detalle de Pedido #<?= $pedido['id'] ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if (isset($detalles_agrupados[$pedido['id']])): ?>
                                <div class="list-group list-group-flush bg-transparent">
                                    <?php foreach ($detalles_agrupados[$pedido['id']] as $item): ?>
                                        <div class="list-group-item bg-transparent text-white border-secondary d-flex align-items-center gap-3 px-0 py-2">
                                            <img src="<?= htmlspecialchars($item['imagen_url']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 text-white"><?= htmlspecialchars($item['titulo']) ?></h6>
                                                <span class="text-secondary small">Cant: <?= (int)$item['cantidad'] ?> &times; S/ <?= number_format((float)$item['precio_unitario'], 2) ?></span>
                                            </div>
                                            <span class="fw-bold text-info font-monospace">
                                                S/ <?= number_format($item['cantidad'] * $item['precio_unitario'], 2) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-3 pt-3 border-top border-secondary d-flex justify-content-between align-items-center">
                                <strong class="text-white">Monto Total:</strong>
                                <span class="fs-5 text-info font-monospace fw-bold">S/ <?= number_format((float)$pedido['total'], 2) ?></span>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    <!-- ============================================================== -->
    <!-- VISTA ADMINISTRADOR                                              -->
    <!-- ============================================================== -->
    <?php else: ?>
        <!-- Tarjetas de Métricas -->
        <div class="row g-4 mb-5">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-gaming p-3 text-center border-start border-4 border-info">
                    <span class="text-secondary small text-uppercase">Pedidos Totales</span>
                    <h3 class="display-6 fw-bold text-white mb-0 mt-2 font-monospace"><?= $total_pedidos ?></h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-gaming p-3 text-center border-start border-4 border-success">
                    <span class="text-secondary small text-uppercase">Facturación Total</span>
                    <h3 class="display-6 fw-bold text-success mb-0 mt-2 font-monospace">S/ <?= number_format((float)$total_ventas, 2) ?></h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-gaming p-3 text-center border-start border-4 border-warning">
                    <span class="text-secondary small text-uppercase">Títulos en Catálogo</span>
                    <h3 class="display-6 fw-bold text-white mb-0 mt-2 font-monospace"><?= $total_productos ?></h3>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-gaming p-3 text-center border-start border-4 border-danger">
                    <span class="text-secondary small text-uppercase">Clientes Registrados</span>
                    <h3 class="display-6 fw-bold text-white mb-0 mt-2 font-monospace"><?= $total_clientes ?></h3>
                </div>
            </div>
        </div>

        <!-- Tabs de Gestión -->
        <ul class="nav nav-pills mb-4 gap-2" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active px-4 py-2" id="productos-tab" data-bs-toggle="pill" data-bs-target="#tab-productos" type="button" role="tab">
                    <i class="bi bi-controller me-2"></i> Gestión de Productos (CRUD)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link px-4 py-2" id="pedidos-tab" data-bs-toggle="pill" data-bs-target="#tab-pedidos" type="button" role="tab">
                    <i class="bi bi-box-seam me-2"></i> Todos los Pedidos de Clientes
                </button>
            </li>
        </ul>

        <div class="tab-content" id="adminTabsContent">
            <!-- PESTAÑA 1: CRUD PRODUCTOS -->
            <div class="tab-pane fade show active" id="tab-productos" role="tabpanel">
                <div class="card card-gaming p-4 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <h3 class="h4 text-white mb-1"><i class="bi bi-joystick text-info me-2"></i> Catálogo de Videojuegos</h3>
                            <p class="text-secondary small mb-0">Agrega nuevos títulos y administra el inventario y precios.</p>
                        </div>
                        <button class="btn btn-neon d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalCrearProducto">
                            <i class="bi bi-plus-circle-fill fs-5"></i> Agregar Nuevo Título
                        </button>
                    </div>

                    <!-- Tabla de Productos (SIN modales dentro) -->
                    <div class="table-responsive">
                        <table class="table table-gaming align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Portada</th>
                                    <th>Título</th>
                                    <th>Precio</th>
                                    <th style="min-width: 170px;">Stock</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admin_productos as $p): ?>
                                    <tr>
                                        <td class="font-monospace text-secondary">#<?= $p['id'] ?></td>
                                        <td>
                                            <img src="<?= htmlspecialchars($p['imagen_url']) ?>" alt="<?= htmlspecialchars($p['titulo']) ?>" class="rounded border border-secondary" style="width: 50px; height: 50px; object-fit: cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80';">
                                        </td>
                                        <td>
                                            <strong class="text-white"><?= htmlspecialchars($p['titulo']) ?></strong>
                                            <p class="text-secondary small mb-0 text-truncate" style="max-width: 250px;">
                                                <?= htmlspecialchars($p['descripcion']) ?>
                                            </p>
                                        </td>
                                        <td class="text-info fw-bold font-monospace">S/ <?= number_format((float)$p['precio'], 2) ?></td>
                                        <!-- Stock con actualización rápida -->
                                        <td>
                                            <form method="POST" action="dashboard.php" class="d-flex align-items-center gap-2 m-0">
                                                <input type="hidden" name="action" value="update_stock">
                                                <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                                <input type="number" name="stock" class="form-control form-control-sm form-gaming-input text-center" style="width: 75px;" value="<?= (int)$p['stock'] ?>" min="0" required>
                                                <button type="submit" class="btn btn-sm btn-outline-info" title="Guardar Stock">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <!-- Botones de Editar / Eliminar -->
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <!-- El botón apunta al modal por ID único -->
                                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalEditar<?= $p['id'] ?>" title="Editar detalles">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form method="POST" action="dashboard.php" class="m-0" onsubmit="return confirm('¿Seguro que deseas eliminar este videojuego del catálogo?');">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar producto">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 2: TODOS LOS PEDIDOS -->
            <div class="tab-pane fade" id="tab-pedidos" role="tabpanel">
                <div class="card card-gaming p-4">
                    <h3 class="h4 text-white mb-1"><i class="bi bi-receipt-cutoff text-info me-2"></i> Pedidos de Todos los Clientes</h3>
                    <p class="text-secondary small mb-4">Revisa las transacciones globales y actualiza el estado de entrega.</p>

                    <div class="table-responsive">
                        <table class="table table-gaming align-middle">
                            <thead>
                                <tr>
                                    <th># Pedido</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado Actual</th>
                                    <th>Modificar Estado</th>
                                    <th class="text-center">Artículos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($admin_pedidos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-secondary">No se han registrado pedidos en el sistema.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($admin_pedidos as $ped): ?>
                                        <tr>
                                            <td class="fw-bold font-monospace text-info">#<?= str_pad($ped['id'], 5, '0', STR_PAD_LEFT) ?></td>
                                            <td>
                                                <strong class="text-white"><?= htmlspecialchars($ped['cliente_nombre']) ?></strong><br>
                                                <span class="text-secondary small"><?= htmlspecialchars($ped['cliente_email']) ?></span>
                                            </td>
                                            <td class="small"><?= date('d/m/Y H:i', strtotime($ped['fecha'])) ?></td>
                                            <td class="fw-bold font-monospace text-white">S/ <?= number_format((float)$ped['total'], 2) ?></td>
                                            <td>
                                                <?php if ($ped['estado'] === 'Completado'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                                        <i class="bi bi-check2-circle me-1"></i> Completado
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1">
                                                        <i class="bi bi-hourglass-split me-1"></i> Pendiente
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="dashboard.php" class="d-flex align-items-center gap-2 m-0">
                                                    <input type="hidden" name="action" value="update_order_status">
                                                    <input type="hidden" name="pedido_id" value="<?= $ped['id'] ?>">
                                                    <select name="nuevo_estado" class="form-select form-select-sm form-gaming-input" style="width: 140px;">
                                                        <option value="Pendiente" <?= $ped['estado'] === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                                        <option value="Completado" <?= $ped['estado'] === 'Completado' ? 'selected' : '' ?>>Completado</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-outline-info" title="Guardar Estado">
                                                        <i class="bi bi-save"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdminPedido<?= $ped['id'] ?>">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> Detalle
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div><!-- /tab-content -->

        <!-- ===========================================================
             MODALES FUERA DE LAS TABLAS — evita el parpadeo por DOM inválido
             =========================================================== -->

        <!-- Modales de Edición de Producto -->
        <?php foreach ($admin_productos as $p): ?>
            <div class="modal fade" id="modalEditar<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-surface border-secondary">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title text-white"><i class="bi bi-pencil-square text-warning me-2"></i> Editar Videojuego #<?= $p['id'] ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" action="dashboard.php">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit_product">
                                <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">

                                <div class="mb-3">
                                    <label class="form-label text-secondary small fw-semibold">Título del Juego</label>
                                    <input type="text" name="titulo" class="form-control form-gaming-input" value="<?= htmlspecialchars($p['titulo']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary small fw-semibold">Descripción</label>
                                    <textarea name="descripcion" class="form-control form-gaming-input" rows="3" required><?= htmlspecialchars($p['descripcion']) ?></textarea>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label text-secondary small fw-semibold">Precio (S/ PEN)</label>
                                        <input type="number" step="0.01" name="precio" class="form-control form-gaming-input" value="<?= $p['precio'] ?>" min="0.01" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-secondary small fw-semibold">Stock Disponible</label>
                                        <input type="number" name="stock" class="form-control form-gaming-input" value="<?= $p['stock'] ?>" min="0" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-secondary small fw-semibold">URL de Imagen</label>
                                    <input type="url" name="imagen_url" class="form-control form-gaming-input" value="<?= htmlspecialchars($p['imagen_url']) ?>" required>
                                </div>
                            </div>
                            <div class="modal-footer border-secondary">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-neon btn-sm">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Modales de Detalle de Pedido (Admin) -->
        <?php foreach ($admin_pedidos as $ped): ?>
            <div class="modal fade" id="modalAdminPedido<?= $ped['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-surface border-secondary">
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title text-white">Artículos del Pedido #<?= $ped['id'] ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-secondary small mb-3">
                                Comprador: <strong class="text-white"><?= htmlspecialchars($ped['cliente_nombre']) ?></strong>
                                (<?= htmlspecialchars($ped['cliente_email']) ?>)
                            </div>
                            <?php if (isset($detalles_agrupados[$ped['id']])): ?>
                                <div class="list-group list-group-flush bg-transparent">
                                    <?php foreach ($detalles_agrupados[$ped['id']] as $item): ?>
                                        <div class="list-group-item bg-transparent text-white border-secondary d-flex align-items-center gap-3 px-0 py-2">
                                            <img src="<?= htmlspecialchars($item['imagen_url']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 text-white"><?= htmlspecialchars($item['titulo']) ?></h6>
                                                <span class="text-secondary small"><?= (int)$item['cantidad'] ?> unidad(es) &times; S/ <?= number_format((float)$item['precio_unitario'], 2) ?></span>
                                            </div>
                                            <span class="fw-bold text-info font-monospace">
                                                S/ <?= number_format($item['cantidad'] * $item['precio_unitario'], 2) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-3 pt-3 border-top border-secondary d-flex justify-content-between align-items-center">
                                <strong class="text-white">Total del Pedido:</strong>
                                <span class="fs-5 text-info font-monospace fw-bold">S/ <?= number_format((float)$ped['total'], 2) ?></span>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- MODAL AGREGAR NUEVO PRODUCTO -->
        <div class="modal fade" id="modalCrearProducto" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-surface border-secondary">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-white"><i class="bi bi-plus-circle-fill text-info me-2"></i> Nuevo Videojuego para Catálogo</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="dashboard.php">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create_product">

                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-semibold">Título del Videojuego</label>
                                <input type="text" name="titulo" class="form-control form-gaming-input" placeholder="Ej: Cyberpunk 2077" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-semibold">Descripción del Producto</label>
                                <textarea name="descripcion" class="form-control form-gaming-input" rows="3" placeholder="Sinopsis o detalles de la edición..." required></textarea>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label text-secondary small fw-semibold">Precio (S/ PEN)</label>
                                    <input type="number" step="0.01" name="precio" class="form-control form-gaming-input" placeholder="49.90" min="0.01" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-secondary small fw-semibold">Stock Inicial</label>
                                    <input type="number" name="stock" class="form-control form-gaming-input" placeholder="10" min="0" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-secondary small fw-semibold">URL de la Portada / Imagen</label>
                                <input type="url" name="imagen_url" class="form-control form-gaming-input" placeholder="https://ejemplo.com/caratula.jpg">
                                <span class="text-secondary" style="font-size: 0.75rem;">Opcional. Si se deja vacío se usará una imagen por defecto.</span>
                            </div>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-neon btn-sm">Publicar Videojuego</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
