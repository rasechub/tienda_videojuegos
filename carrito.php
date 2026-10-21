<?php
require_once __DIR__ . '/conexion.php';

// Inicializar estructura de carrito si no existe
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// -------------------------------------------------------------
// Procesar Acciones del Carrito (Actualizar, Eliminar, Vaciar)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Eliminar un producto específico
    if ($action === 'remove_item') {
        $producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
        if (isset($_SESSION['carrito'][$producto_id])) {
            $nombre_item = $_SESSION['carrito'][$producto_id]['titulo'];
            unset($_SESSION['carrito'][$producto_id]);
            setFlashMessage('info', "'{$nombre_item}' fue eliminado de tu carrito.");
        }
        header("Location: carrito.php");
        exit;
    }

    // 2. Actualizar cantidad (+ / - o número directo)
    if ($action === 'update_qty') {
        $producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
        $nueva_cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 1;

        if (isset($_SESSION['carrito'][$producto_id])) {
            if ($nueva_cantidad <= 0) {
                unset($_SESSION['carrito'][$producto_id]);
                setFlashMessage('info', "Producto eliminado del carrito.");
            } else {
                // Verificar stock actual en la base de datos
                $stmt = $pdo->prepare("SELECT stock, titulo FROM productos WHERE id = :id");
                $stmt->execute([':id' => $producto_id]);
                $prod = $stmt->fetch();

                if ($prod) {
                    if ($nueva_cantidad > $prod['stock']) {
                        $_SESSION['carrito'][$producto_id]['cantidad'] = $prod['stock'];
                        setFlashMessage('warning', "Solo hay {$prod['stock']} unidades disponibles de '{$prod['titulo']}'.");
                    } else {
                        $_SESSION['carrito'][$producto_id]['cantidad'] = $nueva_cantidad;
                    }
                }
            }
        }
        header("Location: carrito.php");
        exit;
    }

    // 3. Vaciar el carrito completamente
    if ($action === 'clear_cart') {
        $_SESSION['carrito'] = [];
        setFlashMessage('info', "Se ha vaciado el carrito de compras.");
        header("Location: carrito.php");
        exit;
    }

    // 4. Procesar y Confirmar Pedido (Checkout)
    if ($action === 'checkout') {
        // Verificar si el usuario ha iniciado sesión
        if (!isset($_SESSION['usuario_id'])) {
            setFlashMessage('warning', "Debes iniciar sesión con tu cuenta para confirmar tu pedido.");
            header("Location: login.php?redirect=carrito.php");
            exit;
        }

        if (empty($_SESSION['carrito'])) {
            setFlashMessage('danger', "Tu carrito está vacío. Agrega videojuegos antes de comprar.");
            header("Location: index.php");
            exit;
        }

        $usuario_id = (int)$_SESSION['usuario_id'];

        // Iniciar Transacción de Base de Datos para asegurar consistencia
        try {
            $pdo->beginTransaction();

            $total_pedido = 0;
            $items_a_procesar = [];

            // Validar stock actualizado de cada producto con bloqueo para evitar sobreventa
            foreach ($_SESSION['carrito'] as $id => $item) {
                $stmt = $pdo->prepare("SELECT id, titulo, precio, stock FROM productos WHERE id = :id FOR UPDATE");
                $stmt->execute([':id' => $id]);
                $producto_db = $stmt->fetch();

                if (!$producto_db) {
                    throw new Exception("El producto '{$item['titulo']}' ya no existe en la tienda.");
                }

                if ($producto_db['stock'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para '{$producto_db['titulo']}'. Stock disponible: {$producto_db['stock']}.");
                }

                $precio_actual = (float)$producto_db['precio'];
                $subtotal = $precio_actual * $item['cantidad'];
                $total_pedido += $subtotal;

                $items_a_procesar[] = [
                    'producto_id'     => $producto_db['id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $precio_actual,
                    'stock_restante'  => $producto_db['stock'] - $item['cantidad']
                ];
            }

            // 1. Insertar en tabla `pedidos`
            $stmt_pedido = $pdo->prepare("INSERT INTO pedidos (usuario_id, total, estado) VALUES (:usuario_id, :total, 'Pendiente')");
            $stmt_pedido->execute([
                ':usuario_id' => $usuario_id,
                ':total'      => $total_pedido
            ]);
            $pedido_id = $pdo->lastInsertId();

            // 2. Insertar cada producto en `pedido_detalle` y descontar stock en `productos`
            $stmt_detalle = $pdo->prepare("INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unitario) VALUES (:pedido_id, :producto_id, :cantidad, :precio_unitario)");
            $stmt_update_stock = $pdo->prepare("UPDATE productos SET stock = :nuevo_stock WHERE id = :id");

            foreach ($items_a_procesar as $item) {
                // Detalle
                $stmt_detalle->execute([
                    ':pedido_id'        => $pedido_id,
                    ':producto_id'      => $item['producto_id'],
                    ':cantidad'         => $item['cantidad'],
                    ':precio_unitario'  => $item['precio_unitario']
                ]);

                // Actualizar Stock
                $stmt_update_stock->execute([
                    ':nuevo_stock' => $item['stock_restante'],
                    ':id'          => $item['producto_id']
                ]);
            }

            // Confirmar transacción
            $pdo->commit();

            // Vaciar carrito tras compra exitosa
            $_SESSION['carrito'] = [];

            setFlashMessage('success', "¡Excelente! Tu pedido #{$pedido_id} ha sido registrado exitosamente con estado 'Pendiente'.");
            header("Location: dashboard.php");
            exit;

        } catch (Exception $e) {
            // Revertir cualquier cambio en caso de error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlashMessage('danger', "No se pudo procesar el pedido: " . $e->getMessage());
            header("Location: carrito.php");
            exit;
        }
    }
}

// Calcular totales
$total_carrito = 0;
$total_articulos = 0;
foreach ($_SESSION['carrito'] as $item) {
    $total_carrito += $item['precio'] * $item['cantidad'];
    $total_articulos += $item['cantidad'];
}

$page_title = "Carrito de Compras";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container my-5 flex-grow-1">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h2 text-white mb-1"><i class="bi bi-cart3 text-info me-2"></i> Carrito de Compras</h1>
            <p class="text-secondary small mb-0">Revisa tus videojuegos seleccionados antes de completar la orden.</p>
        </div>
        <?php if (!empty($_SESSION['carrito'])): ?>
            <form method="POST" action="carrito.php" onsubmit="return confirm('¿Estás seguro de que deseas vaciar tu carrito?');">
                <input type="hidden" name="action" value="clear_cart">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash3 me-1"></i> Vaciar Carrito
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($_SESSION['carrito'])): ?>
        <div class="card-gaming text-center p-5 my-4">
            <div class="my-3">
                <i class="bi bi-cart-x text-secondary" style="font-size: 5rem;"></i>
            </div>
            <h3 class="text-white mt-3">Tu carrito está vacío</h3>
            <p class="text-secondary">Aún no has añadido ningún videojuego a tu orden.</p>
            <div class="mt-3">
                <a href="index.php" class="btn btn-neon px-4 py-2">
                    <i class="bi bi-controller me-2"></i> Ir al Catálogo de Juegos
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Tabla de Productos en el Carrito -->
            <div class="col-lg-8">
                <div class="card card-gaming p-3 p-md-4">
                    <div class="table-responsive">
                        <table class="table table-gaming align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="min-width: 220px;">Videojuego</th>
                                    <th scope="col" class="text-center">Precio</th>
                                    <th scope="col" class="text-center" style="width: 150px;">Cantidad</th>
                                    <th scope="col" class="text-end">Subtotal</th>
                                    <th scope="col" class="text-center" style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['carrito'] as $id => $item): 
                                    $subtotal_item = $item['precio'] * $item['cantidad'];
                                ?>
                                    <tr>
                                        <!-- Título e Imagen -->
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?= htmlspecialchars($item['imagen_url']) ?>" alt="<?= htmlspecialchars($item['titulo']) ?>" class="rounded-3 border border-secondary" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80';">
                                                <div>
                                                    <h6 class="text-white mb-0 fw-bold"><?= htmlspecialchars($item['titulo']) ?></h6>
                                                    <span class="text-secondary small">Entrega Digital Inmediata</span>
                                                </div>
                                            </div>
                                        </td>
                                        <!-- Precio Unitario -->
                                        <td class="text-center font-monospace">
                                            $<?= number_format((float)$item['precio'], 2) ?>
                                        </td>
                                        <!-- Control de Cantidad -->
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <!-- Botón Decrementar -->
                                                <form method="POST" action="carrito.php" class="d-inline m-0">
                                                    <input type="hidden" name="action" value="update_qty">
                                                    <input type="hidden" name="producto_id" value="<?= (int)$id ?>">
                                                    <input type="hidden" name="cantidad" value="<?= max(1, $item['cantidad'] - 1) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1" <?= $item['cantidad'] <= 1 ? 'disabled' : '' ?>>
                                                        <i class="bi bi-dash"></i>
                                                    </button>
                                                </form>

                                                <span class="badge bg-dark border border-secondary px-3 py-2 text-white fs-6">
                                                    <?= (int)$item['cantidad'] ?>
                                                </span>

                                                <!-- Botón Incrementar -->
                                                <form method="POST" action="carrito.php" class="d-inline m-0">
                                                    <input type="hidden" name="action" value="update_qty">
                                                    <input type="hidden" name="producto_id" value="<?= (int)$id ?>">
                                                    <input type="hidden" name="cantidad" value="<?= $item['cantidad'] + 1 ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        <!-- Subtotal -->
                                        <td class="text-end fw-bold text-info font-monospace fs-6">
                                            $<?= number_format($subtotal_item, 2) ?>
                                        </td>
                                        <!-- Botón Eliminar -->
                                        <td class="text-center">
                                            <form method="POST" action="carrito.php" class="m-0">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="producto_id" value="<?= (int)$id ?>">
                                                <button type="submit" class="btn btn-sm text-danger border-0 p-1" title="Eliminar del carrito">
                                                    <i class="bi bi-trash fs-5"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="index.php" class="text-secondary text-decoration-none small">
                        <i class="bi bi-arrow-left me-1"></i> Seguir explorando más videojuegos
                    </a>
                </div>
            </div>

            <!-- Resumen del Pedido y Checkout -->
            <div class="col-lg-4">
                <div class="card card-gaming p-4 sticky-top" style="top: 90px;">
                    <h4 class="text-white mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                        <i class="bi bi-receipt me-2 text-info"></i> Resumen de la Orden
                    </h4>

                    <div class="d-flex justify-content-between mb-2 text-secondary">
                        <span>Total de Artículos:</span>
                        <span class="text-white fw-bold"><?= $total_articulos ?> unidades</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2 text-secondary">
                        <span>Envío Digital:</span>
                        <span class="text-success fw-bold">GRATIS (0.00)</span>
                    </div>

                    <div class="d-flex justify-content-between mb-3 text-secondary">
                        <span>Impuestos incluidos:</span>
                        <span class="text-white">$0.00</span>
                    </div>

                    <hr class="border-secondary border-opacity-25 my-3">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fs-5 text-white fw-bold">Total a Pagar:</span>
                        <span class="fs-3 fw-bold text-info font-monospace">
                            $<?= number_format($total_carrito, 2) ?> <small class="text-secondary fs-6">USD</small>
                        </span>
                    </div>

                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <!-- Formulario de Confirmación si el usuario está autenticado -->
                        <form method="POST" action="carrito.php">
                            <input type="hidden" name="action" value="checkout">
                            <button type="submit" class="btn btn-neon w-100 py-3 fs-6 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-check2-circle fs-5"></i> Confirmar y Realizar Pedido
                            </button>
                        </form>
                        <div class="text-center mt-3 text-secondary small">
                            <i class="bi bi-person-check text-success me-1"></i> Pedido vinculado a: <strong><?= htmlspecialchars($_SESSION['usuario_email']) ?></strong>
                        </div>
                    <?php else: ?>
                        <!-- Aviso si no está logueado -->
                        <div class="alert alert-warning py-2 px-3 small mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Debes iniciar sesión para confirmar tu compra.
                        </div>
                        <a href="login.php?redirect=carrito.php" class="btn btn-neon w-100 py-3 fs-6 d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-in-right fs-5"></i> Iniciar Sesión para Comprar
                        </a>
                        <div class="text-center mt-2">
                            <span class="text-secondary small">¿No tienes cuenta? <a href="register.php" class="text-info">Regístrate aquí</a></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
