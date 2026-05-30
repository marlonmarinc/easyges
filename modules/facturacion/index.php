<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

$where = "1=1";
$params = [];

if ($busqueda) {
    $where .= " AND (f.numero LIKE ? OR c.nombre LIKE ? OR c.numero_doc LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}
if ($estado) {
    $where .= " AND f.estado = ?";
    $params[] = $estado;
}
if ($fecha_inicio) {
    $where .= " AND f.fecha >= ?";
    $params[] = $fecha_inicio;
}
if ($fecha_fin) {
    $where .= " AND f.fecha <= ?";
    $params[] = $fecha_fin;
}

$stmt = db()->prepare("
    SELECT f.*, c.nombre as cliente_nombre, c.numero_doc, u.nombre as usuario_nombre,
           fe.estado_dian, fe.cufe
    FROM facturas f
    JOIN clientes c ON f.cliente_id = c.id
    JOIN usuarios u ON f.usuario_id = u.id
    LEFT JOIN facturas_electronicas fe ON f.id = fe.factura_id
    WHERE $where
    ORDER BY f.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$facturas = $stmt->fetchAll();

$statsWhere = "estado != 'anulada'";
$pendientesWhere = "estado = 'pendiente'";

if ($fecha_inicio) {
    $statsWhere .= " AND fecha >= '$fecha_inicio'";
    $pendientesWhere .= " AND fecha >= '$fecha_inicio'";
}
if ($fecha_fin) {
    $statsWhere .= " AND fecha <= '$fecha_fin'";
    $pendientesWhere .= " AND fecha <= '$fecha_fin'";
}

$stmt = db()->query("SELECT COUNT(*) as total, COALESCE(SUM(total),0) as suma FROM facturas WHERE $statsWhere");
$stats = $stmt->fetch();

$stmt = db()->query("SELECT COUNT(*) as pendientes FROM facturas WHERE $pendientesWhere");
$pendientes = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturación - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/facturacion.css">
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
        <a href="index.php" class="nav-item active">
            <i class="fa-solid fa-file-invoice"></i><span>Facturación</span>
        </a>
        <a href="../inventario/" class="nav-item">
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
                <h1 class="page-title">Facturación</h1>
                <p class="page-subtitle">Gestión de facturas y POS</p>
            </div>
            <div class="page-actions">
                <a href="pos.php" class="btn btn-teal">
                    <i class="fa-solid fa-cash-register"></i> POS / Venta Rápida
                </a>
                <a href="nueva.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Nueva Factura
                </a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-green">
                <div class="stat-info">
                    <span class="stat-label">Total Facturado</span>
                    <span class="stat-value">$<?= number_format($stats['suma'], 0) ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-dollar-sign"></i></div>
            </div>
            <div class="stat-card stat-blue">
                <div class="stat-info">
                    <span class="stat-label">Total Facturas</span>
                    <span class="stat-value stat-value-sm"><?= $stats['total'] ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-file-invoice"></i></div>
            </div>
            <div class="stat-card stat-orange">
                <div class="stat-info">
                    <span class="stat-label">Pendientes</span>
                    <span class="stat-value stat-value-sm"><?= $pendientes ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
            </div>
            <div class="stat-card stat-purple">
                <div class="stat-info">
                    <span class="stat-label">Electrónicas</span>
                    <span class="stat-value stat-value-sm"><?= count(array_filter($facturas, fn($f) => $f['cufe'])) ?></span>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
            </div>
        </div>

        <div class="filters-card">
            <form method="GET" action="index.php" class="filters-form">
                <div class="filter-group">
                    <input type="text" name="busqueda" placeholder="Buscar por número, cliente o documento..." 
                           value="<?= htmlspecialchars($busqueda) ?>" class="filter-input">
                </div>
                <div class="filter-group">
                    <select name="estado" class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="pagada" <?= $estado === 'pagada' ? 'selected' : '' ?>>Pagada</option>
                        <option value="anulada" <?= $estado === 'anulada' ? 'selected' : '' ?>>Anulada</option>
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
                <?php if ($busqueda || $estado || $fecha_inicio || $fecha_fin): ?>
                <a href="index.php" class="btn btn-outline">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Método</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>DIAN</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($facturas)): ?>
                    <tr>
                        <td colspan="9" class="empty-state">
                            <i class="fa-solid fa-file-circle-xmark"></i>
                            <p>No hay facturas registradas</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($facturas as $f): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($f['numero']) ?></strong></td>
                        <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                        <td><?= htmlspecialchars($f['cliente_nombre']) ?></td>
                        <td><?= htmlspecialchars($f['numero_doc'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($f['metodo_pago']) ?></td>
                        <td><strong>$<?= number_format($f['total'], 0) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $f['estado'] ?>">
                                <?= ucfirst($f['estado']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($f['cufe']): ?>
                            <span class="badge badge-<?= $f['estado_dian'] ?? 'pendiente' ?>" title="<?= $f['cufe'] ?>">
                                <?= strtoupper($f['estado_dian'] ?? 'pendiente') ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="ver.php?id=<?= $f['id'] ?>" class="btn-icon" title="Ver">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="pdf.php?id=<?= $f['id'] ?>" target="_blank" class="btn-icon" title="PDF">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                                <?php if ($f['estado'] !== 'anulada'): ?>
                                <button onclick="anularFactura(<?= $f['id'] ?>)" class="btn-icon btn-danger" title="Anular">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
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

function anularFactura(id) {
    if (confirm('¿Está seguro de que desea anular esta factura?')) {
        fetch('ajax/anular_factura.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                location.reload();
            } else {
                alert(d.error || 'Error al anular');
            }
        });
    }
}
</script>
</body>
</html>