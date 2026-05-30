<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

// Obtener estadísticas
$stmt = db()->query("SELECT COALESCE(SUM(total),0) as total FROM facturas WHERE estado = 'pagada' AND YEAR(fecha) = YEAR(NOW())");
$ingresos_anio = $stmt->fetchColumn();

$stmt = db()->query("SELECT COALESCE(SUM(total),0) as total FROM facturas WHERE estado = 'pagada' AND MONTH(fecha) = MONTH(NOW()) AND YEAR(fecha) = YEAR(NOW())");
$ingresos_mes = $stmt->fetchColumn();

$stmt = db()->query("SELECT COALESCE(SUM(total),0) as total FROM compras WHERE estado != 'anulada' AND YEAR(fecha) = YEAR(NOW())");
$gastos_anio = $stmt->fetchColumn();

$stmt = db()->query("SELECT COALESCE(SUM(total),0) as total FROM compras WHERE estado != 'anulada' AND MONTH(fecha) = MONTH(NOW()) AND YEAR(fecha) = YEAR(NOW())");
$gastos_mes = $stmt->fetchColumn();

$utilidad = $ingresos_anio - $gastos_anio;

// Ventas por mes (últimos 6 meses)
$stmt = db()->query("
    SELECT MONTH(fecha) as mes, SUM(total) as total 
    FROM facturas 
    WHERE estado = 'pagada' AND fecha >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY MONTH(fecha)
    ORDER BY mes
");
$ventas_mes = $stmt->fetchAll();

// Top clientes
$stmt = db()->query("
    SELECT c.nombre, SUM(f.total) as total, COUNT(*) as compras
    FROM facturas f
    JOIN clientes c ON f.cliente_id = c.id
    WHERE f.estado = 'pagada' AND YEAR(f.fecha) = YEAR(NOW())
    GROUP BY c.id
    ORDER BY total DESC
    LIMIT 5
");
$top_clientes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contabilidad - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/contabilidad.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><div class="sidebar-logo"><i class="fa-solid fa-building"></i></div><div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión de Ventas</span></div></div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
        <?php if (puedeAccederModulo('facturacion')): ?><a href="../facturacion/" class="nav-item"><i class="fa-solid fa-file-invoice"></i><span>Facturación</span></a><?php endif; ?>
        <?php if (puedeAccederModulo('inventario')): ?><a href="../inventario/" class="nav-item"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a><?php endif; ?>
        <?php if (puedeAccederModulo('compras')): ?><a href="../compras/" class="nav-item"><i class="fa-solid fa-cart-shopping"></i><span>Compras</span></a><?php endif; ?>
        <?php if (puedeAccederModulo('clientes')): ?><a href="../clientes/" class="nav-item"><i class="fa-solid fa-users"></i><span>Clientes</span></a><?php endif; ?>
        <a href="index.php" class="nav-item active"><i class="fa-solid fa-calculator"></i><span>Contabilidad</span></a>
    </nav>
    <div class="sidebar-footer"><div class="user-info"><div class="user-avatar"><i class="fa-solid fa-user"></i></div><div class="user-details"><span class="user-name"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span><span class="user-role"><?= htmlspecialchars($_SESSION['usuario_rol']) ?></span></div></div><a href="../../auth/logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button><div class="topbar-right"><span class="topbar-user"><strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong><small><?= htmlspecialchars($_SESSION['usuario_rol']) ?></small></span></div></header>
    <div class="page-content">
        <div class="page-header"><div><h1 class="page-title">Contabilidad</h1><p class="page-subtitle">Resumen financiero del año <?= date('Y') ?></p></div></div>
        
        <div class="stats-grid">
            <div class="stat-card stat-green">
                <div class="stat-info"><span class="stat-label">Ingresos del Año</span><span class="stat-value">$<?= number_format($ingresos_anio, 0) ?></span><span class="stat-change"><?= number_format($ingresos_mes, 0) ?> este mes</span></div>
                <div class="stat-icon"><i class="fa-solid fa-arrow-trend-up"></i></div>
            </div>
            <div class="stat-card stat-red">
                <div class="stat-info"><span class="stat-label">Gastos del Año</span><span class="stat-value">$<?= number_format($gastos_anio, 0) ?></span><span class="stat-change"><?= number_format($gastos_mes, 0) ?> este mes</span></div>
                <div class="stat-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
            </div>
            <div class="stat-card <?= $utilidad >= 0 ? 'stat-teal' : 'stat-orange' ?>">
                <div class="stat-info"><span class="stat-label">Utilidad del Año</span><span class="stat-value">$<?= number_format($utilidad, 0) ?></span></div>
                <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
            </div>
        </div>

        <div class="tables-grid">
            <div class="table-card">
                <div class="table-card-header"><div class="table-card-icon teal"><i class="fa-solid fa-users"></i></div><div><h3>Top Clientes</h3><p>Más compras este año</p></div></div>
                <?php if (empty($top_clientes)): ?>
                <div class="empty-state"><i class="fa-solid fa-user"></i><p>No hay datos</p></div>
                <?php else: ?>
                <table class="data-table">
                    <thead><tr><th>Cliente</th><th>Compras</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php foreach ($top_clientes as $c): ?>
                        <tr><td><?= htmlspecialchars($c['nombre']) ?></td><td><?= $c['compras'] ?></td><td>$<?= number_format($c['total'], 0) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            
            <div class="table-card">
                <div class="table-card-header"><div class="table-card-icon blue"><i class="fa-solid fa-chart-bar"></i></div><div><h3>Resumen</h3><p>Indicadores clave</p></div></div>
                <div style="padding: 1rem;">
                    <div style="display:flex;justify-content:space-between;padding:.75rem 0;border-bottom:1px solid var(--gray-100);"><span>Margen de utilidad</span><strong><?= $ingresos_anio > 0 ? round(($utilidad/$ingresos_anio)*100, 1) : 0 ?>%</strong></div>
                    <div style="display:flex;justify-content:space-between;padding:.75rem 0;border-bottom:1px solid var(--gray-100);"><span>Ticket promedio</span><strong>$<?= number_format($ingresos_anio / max(1, db()->query("SELECT COUNT(*) FROM facturas WHERE estado='pagada' AND YEAR(fecha)=YEAR(NOW())")->fetchColumn()), 0) ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:.75rem 0;"><span>ROI</span><strong><?= $gastos_anio > 0 ? round((($ingresos_anio-$gastos_anio)/$gastos_anio)*100, 1) : 0 ?>%</strong></div>
                </div>
            </div>
        </div>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>