<?php
// ============================================
// dashboard.php
// ============================================
session_start();

// Proteger ruta — si no hay sesión, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/conexion.php';

// ============================================
// Obtener estadísticas para el dashboard
// ============================================

// Ingresos totales (facturas pagadas)
$stmt = db()->query("SELECT COALESCE(SUM(total),0) as total FROM facturas WHERE estado = 'pagada'");
$ingresos_totales = $stmt->fetchColumn();

// Ingresos del mes actual
$stmt = db()->query("SELECT COALESCE(SUM(total),0) as total FROM facturas WHERE estado = 'pagada' AND MONTH(fecha) = MONTH(NOW()) AND YEAR(fecha) = YEAR(NOW())");
$ingresos_mes = $stmt->fetchColumn();

// Ingresos mes anterior (para calcular % cambio)
$stmt = db()->query("SELECT COALESCE(SUM(total),0) as total FROM facturas WHERE estado = 'pagada' AND MONTH(fecha) = MONTH(NOW()-INTERVAL 1 MONTH) AND YEAR(fecha) = YEAR(NOW()-INTERVAL 1 MONTH)");
$ingresos_mes_anterior = $stmt->fetchColumn();

// % cambio mes
$pct_cambio_mes = 0;
if ($ingresos_mes_anterior > 0) {
    $pct_cambio_mes = round((($ingresos_mes - $ingresos_mes_anterior) / $ingresos_mes_anterior) * 100, 1);
}

// Facturas emitidas (total y pendientes)
$stmt = db()->query("SELECT COUNT(*) FROM facturas");
$facturas_total = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) FROM facturas WHERE estado = 'pendiente'");
$facturas_pendientes = $stmt->fetchColumn();

// Productos con stock bajo
$stmt = db()->query("SELECT COUNT(*) FROM productos WHERE stock <= stock_minimo AND activo = 1");
$stock_bajo = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) FROM productos WHERE activo = 1");
$productos_total = $stmt->fetchColumn();

// Clientes activos
$stmt = db()->query("SELECT COUNT(*) FROM clientes WHERE activo = 1");
$clientes_activos = $stmt->fetchColumn();

// Últimas 5 facturas
$stmt = db()->query("
    SELECT f.numero, f.fecha, f.total, f.estado, c.nombre as cliente
    FROM facturas f
    JOIN clientes c ON f.cliente_id = c.id
    ORDER BY f.created_at DESC
    LIMIT 5
");
$ultimas_facturas = $stmt->fetchAll();

// Nombre visible del rol
$roles_labels = [
    'admin'        => 'Admin',
    'facturacion'  => 'Facturación',
    'contabilidad' => 'Contabilidad',
    'inventario'   => 'Inventario',
    'compras'      => 'Compras',
    'clientes'     => 'Clientes',
];
$rol_label = $roles_labels[$_SESSION['usuario_rol']] ?? $_SESSION['usuario_rol'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EASYGES — Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">

<!-- ============================================
     SIDEBAR
============================================ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fa-solid fa-building"></i>
        </div>
        <div class="sidebar-brand">
            <span class="brand-name">EASYGES</span>
            <span class="brand-sub">Gestión de Ventas</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item active">
            <i class="fa-solid fa-table-columns"></i>
            <span>Dashboard</span>
        </a>
        <?php require_once 'config/permisos.php'; ?>
        <?php if (puedeAccederModulo('facturacion')): ?>
        <a href="modules/facturacion/" class="nav-item">
            <i class="fa-solid fa-file-invoice"></i>
            <span>Facturación</span>
        </a>
        <?php endif; ?>
        <?php if (puedeAccederModulo('inventario')): ?>
        <a href="modules/inventario/" class="nav-item">
            <i class="fa-solid fa-boxes-stacked"></i>
            <span>Inventario</span>
        </a>
        <?php endif; ?>
        <?php if (puedeAccederModulo('compras')): ?>
        <a href="modules/compras/" class="nav-item">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Compras</span>
        </a>
        <?php endif; ?>
        <?php if (puedeAccederModulo('clientes')): ?>
        <a href="modules/clientes/" class="nav-item">
            <i class="fa-solid fa-users"></i>
            <span>Clientes</span>
        </a>
        <?php endif; ?>
        <?php if (puedeAccederModulo('contabilidad')): ?>
        <a href="modules/contabilidad/" class="nav-item">
            <i class="fa-solid fa-calculator"></i>
            <span>Contabilidad</span>
        </a>
        <?php endif; ?>
        <?php if (puedeAccederModulo('admin')): ?>
        <a href="modules/admin/index.php" class="nav-item">
            <i class="fa-solid fa-gear"></i>
            <span>Empresa</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <span class="user-role"><?= htmlspecialchars($rol_label) ?></span>
            </div>
        </div>
        <a href="auth/logout.php" class="btn-logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            Cerrar Sesión
        </a>
    </div>
</aside>

<!-- ============================================
     CONTENIDO PRINCIPAL
============================================ -->
<main class="main-content">

    <!-- Topbar -->
    <header class="topbar">
        <button class="btn-toggle" id="sidebarToggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-right">
            <span class="topbar-user">
                <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>
                <small><?= htmlspecialchars($rol_label) ?></small>
            </span>
        </div>
    </header>

    <!-- Page content -->
    <div class="page-content">

        <!-- Título página -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></p>
            </div>
        </div>

        <!-- ============ TARJETAS ESTADÍSTICAS ============ -->
        <div class="stats-grid">

            <!-- Ingresos Totales -->
            <div class="stat-card stat-green">
                <div class="stat-info">
                    <span class="stat-label">Ingresos Totales</span>
                    <span class="stat-value">$<?= number_format($ingresos_totales, 2) ?></span>
                    <span class="stat-change <?= $pct_cambio_mes >= 0 ? 'positive' : 'negative' ?>">
                        <?= $pct_cambio_mes >= 0 ? '+' : '' ?><?= $pct_cambio_mes ?>% vs mes anterior
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
            </div>

            <!-- Ingresos del Mes -->
            <div class="stat-card stat-teal">
                <div class="stat-info">
                    <span class="stat-label">Ingresos del Mes</span>
                    <span class="stat-value">$<?= number_format($ingresos_mes, 2) ?></span>
                    <span class="stat-change <?= $pct_cambio_mes >= 0 ? 'positive' : 'negative' ?>">
                        <?= $pct_cambio_mes >= 0 ? '+' : '' ?><?= $pct_cambio_mes ?>% vs mes anterior
                    </span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>

            <!-- Facturas Emitidas -->
            <div class="stat-card stat-blue">
                <div class="stat-info">
                    <span class="stat-label">Facturas Emitidas</span>
                    <span class="stat-value stat-value-sm"><?= $facturas_total ?></span>
                    <span class="stat-change neutral"><?= $facturas_pendientes ?> pendientes</span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
            </div>

            <!-- Stock Bajo -->
            <div class="stat-card stat-red">
                <div class="stat-info">
                    <span class="stat-label">Stock Bajo</span>
                    <span class="stat-value stat-value-sm"><?= $stock_bajo ?></span>
                    <span class="stat-change neutral">de <?= $productos_total ?> productos</span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
            </div>

            <!-- Clientes Activos -->
            <div class="stat-card stat-purple">
                <div class="stat-info">
                    <span class="stat-label">Clientes Activos</span>
                    <span class="stat-value stat-value-sm"><?= $clientes_activos ?></span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <!-- Productos Totales -->
            <div class="stat-card stat-orange">
                <div class="stat-info">
                    <span class="stat-label">Productos Totales</span>
                    <span class="stat-value stat-value-sm"><?= $productos_total ?></span>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-box"></i>
                </div>
            </div>

        </div>

        <!-- ============ TABLAS ============ -->
        <div class="tables-grid">

            <!-- Últimas Facturas -->
            <div class="table-card">
                <div class="table-card-header">
                    <div class="table-card-icon teal">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <div>
                        <h3>Facturas Recientes</h3>
                        <p>Últimas operaciones</p>
                    </div>
                </div>

                <?php if (empty($ultimas_facturas)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-file-circle-xmark"></i>
                        <p>No hay facturas registradas</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas_facturas as $f): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($f['numero']) ?></strong></td>
                                <td><?= htmlspecialchars($f['cliente']) ?></td>
                                <td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td>
                                <td>$<?= number_format($f['total'], 2) ?></td>
                                <td>
                                    <span class="badge badge-<?= $f['estado'] ?>">
                                        <?= ucfirst($f['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Accesos Rápidos -->
            <div class="table-card">
                <div class="table-card-header">
                    <div class="table-card-icon blue">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div>
                        <h3>Accesos Rápidos</h3>
                        <p>Acciones frecuentes</p>
                    </div>
                </div>
                <div class="quick-actions">
                    <a href="modules/facturacion/nueva.php" class="quick-action-btn">
                        <i class="fa-solid fa-plus"></i>
                        Nueva Factura
                    </a>
                    <a href="modules/clientes/nuevo.php" class="quick-action-btn">
                        <i class="fa-solid fa-user-plus"></i>
                        Nuevo Cliente
                    </a>
                    <a href="modules/inventario/nuevo.php" class="quick-action-btn">
                        <i class="fa-solid fa-box"></i>
                        Nuevo Producto
                    </a>
                    <a href="modules/compras/nueva.php" class="quick-action-btn">
                        <i class="fa-solid fa-cart-plus"></i>
                        Nueva Compra
                    </a>
                    <a href="modules/contabilidad/" class="quick-action-btn">
                        <i class="fa-solid fa-chart-bar"></i>
                        Ver Reportes
                    </a>

                </div>
            </div>

        </div>
    </div><!-- /page-content -->
</main>

<!-- Botón flotante de ayuda -->
<button class="fab-help" title="Ayuda">?</button>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
}
</script>

</body>
</html>