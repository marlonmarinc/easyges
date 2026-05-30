<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$producto_id = (int)($_GET['producto'] ?? 0);
$tipo = $_GET['tipo'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

$where = "1=1";
$params = [];

if ($producto_id) {
    $where .= " AND m.producto_id = ?";
    $params[] = $producto_id;
}
if ($tipo) {
    $where .= " AND m.tipo = ?";
    $params[] = $tipo;
}
if ($fecha_inicio) {
    $where .= " AND DATE(m.created_at) >= ?";
    $params[] = $fecha_inicio;
}
if ($fecha_fin) {
    $where .= " AND DATE(m.created_at) <= ?";
    $params[] = $fecha_fin;
}

$stmt = db()->prepare("
    SELECT m.*, p.nombre as producto_nombre, p.codigo, u.nombre as usuario_nombre
    FROM movimientos_inventario m
    JOIN productos p ON m.producto_id = p.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE $where
    ORDER BY m.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

$stmt = db()->query("SELECT id, nombre, codigo FROM productos WHERE activo = 1 ORDER BY nombre");
$productos = $stmt->fetchAll();

$stmt = db()->query("
    SELECT tipo, SUM(cantidad) as total 
    FROM movimientos_inventario 
    GROUP BY tipo
");
$totales = $stmt->fetchAll();
$stats = [];
foreach ($totales as $t) {
    $stats[$t['tipo']] = $t['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos de Inventario - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/inventario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-building"></i></div>
        <div class="sidebar-brand">
            <span class="brand-name">EASYGES</span><span class="brand-sub">Gestión de Ventas</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item">
            <i class="fa-solid fa-table-columns"></i><span>Dashboard</span>
        </a>
        <a href="index.php" class="nav-item">
            <i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span>
        </a>
        <a href="movimientos.php" class="nav-item active">
            <i class="fa-solid fa-list"></i><span>Movimientos</span>
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
                <h1 class="page-title">Movimientos de Inventario</h1>
                <p class="page-subtitle">Historial de entradas, salidas y ajustes</p>
            </div>
            <div class="page-actions">
                <a href="index.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Volver al Inventario
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-green">
                <div class="stat-info">
                    <span class="stat-label">Entradas</span>
                    <span class="stat-value"><?= $stats['entrada'] ?? 0 ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-arrow-down"></i></div>
            </div>
            <div class="stat-card stat-red">
                <div class="stat-info">
                    <span class="stat-label">Salidas</span>
                    <span class="stat-value"><?= $stats['salida'] ?? 0 ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-arrow-up"></i></div>
            </div>
            <div class="stat-card stat-blue">
                <div class="stat-info">
                    <span class="stat-label">Ajustes</span>
                    <span class="stat-value"><?= $stats['ajuste'] ?? 0 ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-pen"></i></div>
            </div>
            <div class="stat-card stat-purple">
                <div class="stat-info">
                    <span class="stat-label">Devoluciones</span>
                    <span class="stat-value"><?= $stats['devolucion'] ?? 0 ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-rotate-left"></i></div>
            </div>
        </div>

        <form method="GET" action="movimientos.php" class="inv-filters">
            <div class="filter-group">
                <select name="producto" class="filter-select">
                    <option value="">Todos los productos</option>
                    <?php foreach ($productos as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $producto_id == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['codigo']) ?> - <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="tipo" class="filter-select">
                    <option value="">Todos los tipos</option>
                    <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                    <option value="salida" <?= $tipo === 'salida' ? 'selected' : '' ?>>Salida</option>
                    <option value="ajuste" <?= $tipo === 'ajuste' ? 'selected' : '' ?>>Ajuste</option>
                    <option value="devolucion" <?= $tipo === 'devolucion' ? 'selected' : '' ?>>Devolución</option>
                </select>
            </div>
            <div class="filter-group">
                <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" class="filter-input">
            </div>
            <div class="filter-group">
                <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" class="filter-input">
            </div>
            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-filter"></i> Filtrar
            </button>
            <?php if ($producto_id || $tipo || $fecha_inicio || $fecha_fin): ?>
            <a href="movimientos.php" class="btn btn-outline">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Stock Anterior</th>
                        <th>Stock Nuevo</th>
                        <th>Usuario</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movimientos)): ?>
                    <tr>
                        <td colspan="8" class="empty-state">
                            <i class="fa-solid fa-list"></i>
                            <p>No hay movimientos registrados</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($movimientos as $m): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($m['codigo']) ?></strong><br>
                            <small><?= htmlspecialchars($m['producto_nombre']) ?></small>
                        </td>
                        <td>
                            <span class="badge badge-<?= $m['tipo'] === 'entrada' ? 'pagada' : ($m['tipo'] === 'salida' ? 'pendiente' : 'anulada') ?>">
                                <?= ucfirst($m['tipo']) ?>
                            </span>
                        </td>
                        <td><strong><?= $m['cantidad'] ?></strong></td>
                        <td><?= $m['stock_anterior'] ?></td>
                        <td><?= $m['stock_nuevo'] ?></td>
                        <td><?= htmlspecialchars($m['usuario_nombre']) ?></td>
                        <td><small><?= htmlspecialchars($m['notas'] ?? '-') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
}
</script>
</body>
</html>