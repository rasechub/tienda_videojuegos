<?php
require_once __DIR__ . '/conexion.php';

// Manejo de la acción "Agregar al carrito"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $producto_id = isset($_POST['producto_id']) ? (int)$_POST['producto_id'] : 0;
    $cantidad_solicitada = isset($_POST['cantidad']) ? max(1, (int)$_POST['cantidad']) : 1;

    if ($producto_id > 0) {
        // Consultar producto con Prepared Statement para seguridad
        $stmt = $pdo->prepare("SELECT id, titulo, precio, stock, imagen_url FROM productos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $producto_id]);
        $prod = $stmt->fetch();

        if ($prod) {
            if ($prod['stock'] <= 0) {
                setFlashMessage('danger', "Lo sentimos, '{$prod['titulo']}' está actualmente agotado.");
            } else {
                if (!isset($_SESSION['carrito'])) {
                    $_SESSION['carrito'] = [];
                }

                $cant_actual = isset($_SESSION['carrito'][$producto_id]) ? $_SESSION['carrito'][$producto_id]['cantidad'] : 0;
                $nueva_cantidad = $cant_actual + $cantidad_solicitada;

                if ($nueva_cantidad > $prod['stock']) {
                    setFlashMessage('warning', "No puedes agregar más unidades de '{$prod['titulo']}'. Stock disponible: {$prod['stock']}.");
                } else {
                    $_SESSION['carrito'][$producto_id] = [
                        'id' => $prod['id'],
                        'titulo' => $prod['titulo'],
                        'precio' => (float)$prod['precio'],
                        'imagen_url' => $prod['imagen_url'],
                        'stock_max' => (int)$prod['stock'],
                        'cantidad' => $nueva_cantidad
                    ];
                    setFlashMessage('success', "¡'{$prod['titulo']}' fue agregado al carrito de compras!");
                }
            }
        } else {
            setFlashMessage('danger', "El producto seleccionado no existe.");
        }
    }
    header("Location: index.php");
    exit;
}

// Búsqueda opcional de productos
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
if (!empty($busqueda)) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE titulo LIKE :busqueda OR descripcion LIKE :busqueda ORDER BY id DESC");
    $stmt->execute([':busqueda' => '%' . $busqueda . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM productos ORDER BY id DESC");
}
$productos = $stmt->fetchAll();

$page_title = "Catálogo de Videojuegos";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="container my-4 flex-grow-1">
    <!-- Hero Banner Gaming -->
    <section class="hero-banner text-center text-md-start">
        <div class="row align-items-center">
            <div class="col-lg-8 ps-lg-5">
                <span class="badge bg-primary text-uppercase px-3 py-2 mb-3"><i class="bi bi-lightning-charge-fill me-1"></i> Ofertas de Temporada</span>
                <h1 class="display-4 fw-bold text-white mb-3">
                    Explora el Catálogo Oficial de <span class="brand-logo">XioNox Store</span>
                </h1>
                <p class="lead text-secondary mb-4">
                    Claves digitales 100% legítimas, expansiones tácticas y bundles de mods para llevar tu experiencia de juego al siguiente nivel.
                </p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-md-start">
                    <a href="#catalogo" class="btn btn-neon btn-lg px-4">
                        <i class="bi bi-controller me-2"></i> Ver Juegos Disponibles
                    </a>
                    <a href="carrito.php" class="btn btn-outline-light btn-lg px-4">
                        <i class="bi bi-cart3 me-2"></i> Mi Carrito
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <div class="p-3">
                    <i class="bi bi-headset-vr text-info opacity-75" style="font-size: 8rem; filter: drop-shadow(0 0 25px rgba(0, 242, 254, 0.4));"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="row align-items-center mb-4" id="catalogo">
        <div class="col-md-6 mb-3 mb-md-0">
            <h2 class="h3 text-white mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-fire text-danger"></i> Títulos Destacados
            </h2>
            <p class="text-secondary small mb-0">Selecciona tus videojuegos favoritos y recíbelos en tu cuenta.</p>
        </div>
        <div class="col-md-6">
            <form method="GET" action="index.php" class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" class="form-control form-gaming-input" placeholder="Buscar por título o descripción..." value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <?php if (!empty($busqueda)): ?>
                    <a href="index.php" class="btn btn-outline-secondary" title="Limpiar búsqueda">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
                <button type="submit" class="btn btn-neon">Buscar</button>
            </form>
        </div>
    </div>

    <!-- Grid de Productos -->
    <?php if (empty($productos)): ?>
        <div class="text-center py-5 card-gaming p-5">
            <i class="bi bi-emoji-frown display-1 text-secondary"></i>
            <h3 class="mt-3 text-white">No se encontraron productos</h3>
            <p class="text-secondary">Intenta con otro término de búsqueda o vuelve más tarde.</p>
            <a href="index.php" class="btn btn-outline-neon mt-2">Ver todo el catálogo</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($productos as $producto): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card card-gaming h-100 d-flex flex-column">
                        <!-- Imagen del Videojuego -->
                        <div class="game-img-wrapper">
                            <img src="<?= htmlspecialchars($producto['imagen_url']) ?>" alt="<?= htmlspecialchars($producto['titulo']) ?>" loading="lazy" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80';">
                            <!-- Stock Badge -->
                            <div class="game-badge">
                                <?php if ($producto['stock'] > 0): ?>
                                    <span class="badge badge-stock">
                                        <i class="bi bi-check-circle me-1"></i> Stock: <?= (int)$producto['stock'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-nostock">
                                        <i class="bi bi-x-circle me-1"></i> Agotado
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Contenido de la Card -->
                        <div class="card-body d-flex flex-column p-4">
                            <h3 class="h5 card-title text-white fw-bold mb-2 text-truncate" title="<?= htmlspecialchars($producto['titulo']) ?>">
                                <?= htmlspecialchars($producto['titulo']) ?>
                            </h3>
                            <p class="card-text text-secondary small flex-grow-1 line-clamp-3 mb-3">
                                <?= htmlspecialchars($producto['descripcion']) ?>
                            </p>

                            <!-- Precio y Botón de Acción -->
                            <div class="pt-3 border-top border-secondary border-opacity-25 mt-auto">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-secondary small">Precio:</span>
                                    <span class="fs-4 fw-bold text-info">
                                        S/ <?= number_format((float)$producto['precio'], 2) ?> <small class="text-secondary fs-6">PEN</small>
                                    </span>
                                </div>

                                <form method="POST" action="index.php" class="w-100">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="producto_id" value="<?= (int)$producto['id'] ?>">
                                    <input type="hidden" name="cantidad" value="1">
                                    
                                    <?php if ($producto['stock'] > 0): ?>
                                        <button type="submit" class="btn btn-neon w-100 d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-cart-plus-fill fs-6"></i> Agregar al Carrito
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-secondary w-100 disabled" disabled>
                                            <i class="bi bi-slash-circle me-1"></i> Sin Stock
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
