<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$busqueda = $_GET['busqueda'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$stock = $_GET['stock'] ?? '';

$where = "p.activo = 1";
$params = [];

if ($busqueda) {
    $where .= " AND (p.nombre LIKE ? OR p.codigo LIKE ? OR m.nombre LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
if ($categoria) {
    $where .= " AND p.categoria_id = ?";
    $params[] = $categoria;
}
if ($stock === 'bajo') {
    $where .= " AND p.stock <= p.stock_minimo";
} elseif ($stock === 'agotado') {
    $where .= " AND p.stock = 0";
}

$stmt = db()->prepare("
    SELECT p.*, c.nombre as categoria, m.nombre as marca,
           (p.stock - p.stock_minimo) as stock_diff
    FROM productos p
    JOIN categorias c ON p.categoria_id = c.id
    JOIN marcas m ON p.marca_id = m.id
    WHERE $where
    ORDER BY p.nombre ASC
    LIMIT 100
");
$stmt->execute($params);
$productos = $stmt->fetchAll();

$stmt = db()->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll();

$stmt = db()->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
$total_productos = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) as bajo FROM productos WHERE activo = 1 AND stock <= stock_minimo");
$stock_bajo = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) as agotado FROM productos WHERE activo = 1 AND stock = 0");
$stock_agotado = $stmt->fetchColumn();

$stmt = db()->query("SELECT COALESCE(SUM(stock * precio_venta),0) as valor FROM productos WHERE activo = 1");
$valor_inventario = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/inventario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-building"></i></div>
        <div class="sidebar-brand">
            <span class="brand-name">EASYGES</span>
            <span class="brand-sub">Gestión de Ventas</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item">
            <i class="fa-solid fa-table-columns"></i><span>Dashboard</span>
        </a>
        <a href="../facturacion/" class="nav-item">
            <i class="fa-solid fa-file-invoice"></i><span>Facturación</span>
        </a>
        <a href="index.php" class="nav-item active">
            <i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span>
        </a>
        <a href="../compras/" class="nav-item">
            <i class="fa-solid fa-cart-shopping"></i><span>Compras</span>
        </a>
        <a href="../clientes/" class="nav-item">
            <i class="fa-solid fa-users"></i><span>Clientes</span>
        </a>
        <a href="../contabilidad/" class="nav-item">
            <i class="fa-solid fa-calculator"></i><span>Contabilidad</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <span class="user-role"><?= htmlspecialchars($_SESSION['usuario_rol']) ?></span>
            </div>
        </div>
        <a href="../../auth/logout.php" class="btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
        </a>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-right">
            <span class="topbar-user">
                <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>
                <small><?= htmlspecialchars($_SESSION['usuario_rol']) ?></small>
            </span>
        </div>
    </header>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Inventario</h1>
                <p class="page-subtitle">Gestión de productos y stock</p>
            </div>
            <div class="page-actions">
                <a href="movimientos.php" class="btn btn-outline">
                    <i class="fa-solid fa-list"></i> Movimientos
                </a>
                <a href="nuevo.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Nuevo Producto
                </a>
            </div>
        </div>

        <?php if ($stock_bajo > 0): ?>
        <div class="alert-stock">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><strong>Atención:</strong> <?= $stock_bajo ?> producto(s) con stock bajo del mínimo. <a href="?stock=bajo">Ver productos</a></span>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card stat-teal">
                <div class="stat-info">
                    <span class="stat-label">Total Productos</span>
                    <span class="stat-value"><?= $total_productos ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-box"></i></div>
            </div>
            <div class="stat-card stat-orange">
                <div class="stat-info">
                    <span class="stat-label">Stock Bajo</span>
                    <span class="stat-value"><?= $stock_bajo ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            </div>
            <div class="stat-card stat-red">
                <div class="stat-info">
                    <span class="stat-label">Agotados</span>
                    <span class="stat-value"><?= $stock_agotado ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-box-open"></i></div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-info">
                    <span class="stat-label">Valor Inventario</span>
                    <span class="stat-value">$<?= number_format($valor_inventario, 0) ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-dollar-sign"></i></div>
            </div>
        </div>

        <form method="GET" action="index.php" class="inv-filters">
            <div class="filter-group">
                <input type="text" name="busqueda" placeholder="Buscar por código o nombre..." 
                       value="<?= htmlspecialchars($busqueda) ?>" class="filter-input">
            </div>
            <div class="filter-group">
                <select name="categoria" class="filter-select">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $categoria == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="stock" class="filter-select">
                    <option value="">Todo el stock</option>
                    <option value="bajo" <?= $stock === 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                    <option value="agotado" <?= $stock === 'agotado' ? 'selected' : '' ?>>Agotados</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-filter"></i> Filtrar
            </button>
            <?php if ($busqueda || $categoria || $stock): ?>
            <a href="index.php" class="btn btn-outline">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="inv-grid">
            <?php foreach ($productos as $p): ?>
            <div class="inv-card <?= $p['stock'] <= $p['stock_minimo'] ? 'stock-bajo' : 'stock-ok' ?>">
                <div class="inv-card-header">
                    <div>
                        <span class="inv-card-code"><?= htmlspecialchars($p['codigo']) ?></span>
                        <div class="inv-card-name"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div class="inv-card-brand"><?= htmlspecialchars($p['marca']) ?> • <?= htmlspecialchars($p['categoria']) ?></div>
                    </div>
                    <div class="kebab-menu">
                        <button class="kebab-btn" onclick="toggleMenu(<?= $p['id'] ?>)">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <div class="kebab-dropdown" id="menu-<?= $p['id'] ?>">
                            <a href="editar.php?id=<?= $p['id'] ?>"><i class="fa-solid fa-edit"></i> Editar</a>
                            <a href="tallas.php?id=<?= $p['id'] ?>"><i class="fa-solid fa-ruler"></i> Tallas</a>
                            <a href="movimiento.php?id=<?= $p['id'] ?>"><i class="fa-solid fa-plus-minus"></i> Ajuste Stock</a>
                            <a href="#" onclick="eliminarProducto(<?= $p['id'] ?>)" class="danger"><i class="fa-solid fa-trash"></i> Eliminar</a>
                        </div>
                    </div>
                </div>

                <div class="inv-card-prices">
                    <div class="inv-price">
                        <span class="inv-price-label">Compra</span>
                        <span class="inv-price-value">$<?= number_format($p['precio_compra'], 0) ?></span>
                    </div>
                    <div class="inv-price">
                        <span class="inv-price-label">Venta</span>
                        <span class="inv-price-value sale">$<?= number_format($p['precio_venta'], 0) ?></span>
                    </div>
                </div>

                <div class="inv-card-stock">
                    <span class="inv-stock-label">Stock: <?= $p['stock_minimo'] ?> min</span>
                    <span class="inv-stock-value <?= $p['stock'] <= $p['stock_minimo'] ? 'bajo' : 'ok' ?>">
                        <?= $p['stock'] ?>
                    </span>
                </div>

                <div class="inv-card-actions">
                    <a href="editar.php?id=<?= $p['id'] ?>" class="btn btn-outline">
                        <i class="fa-solid fa-edit"></i> Editar
                    </a>
                    <a href="movimiento.php?id=<?= $p['id'] ?>" class="btn btn-teal">
                        <i class="fa-solid fa-plus-minus"></i> Ajustar
                    </a>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($productos)): ?>
            <div class="table-card" style="grid-column: 1 / -1;">
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <p>No hay productos en el inventario</p>
                    <a href="nuevo.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fa-solid fa-plus"></i> Crear primer producto
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
}

function toggleMenu(id) {
    document.querySelectorAll('.kebab-dropdown').forEach(m => m.classList.remove('show'));
    document.getElementById('menu-' + id).classList.toggle('show');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.kebab-menu')) {
        document.querySelectorAll('.kebab-dropdown').forEach(m => m.classList.remove('show'));
    }
});

function eliminarProducto(id) {
    if (confirm('¿Está seguro de eliminar este producto? Esta acción no se puede deshacer.')) {
        fetch('ajax/eliminar_producto.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                location.reload();
            } else {
                alert(d.error || 'Error al eliminar');
            }
        });
    }
}
</script>
</body>
</html>