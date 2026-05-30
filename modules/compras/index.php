<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$where = "1=1";
$params = [];

$stmt = db()->prepare("
    SELECT c.*, p.nombre as proveedor_nombre, u.nombre as usuario_nombre
    FROM compras c
    JOIN proveedores p ON c.proveedor_id = p.id
    JOIN usuarios u ON c.usuario_id = u.id
    WHERE $where
    ORDER BY c.fecha DESC
    LIMIT 50
");
$stmt->execute($params);
$compras = $stmt->fetchAll();

$stmt = db()->query("SELECT COUNT(*) as total FROM compras");
$total_compras = $stmt->fetchColumn();

$stmt = db()->query("SELECT COALESCE(SUM(total),0) as total FROM compras WHERE estado != 'anulada'");
$total_gastado = $stmt->fetchColumn();

$stmt = db()->query("SELECT COUNT(*) as pendientes FROM compras WHERE estado = 'pendiente'");
$pendientes = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/compras.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><div class="sidebar-logo"><i class="fa-solid fa-building"></i></div><div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión de Ventas</span></div></div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
        <?php if (puedeAccederModulo('facturacion')): ?><a href="../facturacion/" class="nav-item"><i class="fa-solid fa-file-invoice"></i><span>Facturación</span></a><?php endif; ?>
        <?php if (puedeAccederModulo('inventario')): ?><a href="../inventario/" class="nav-item"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a><?php endif; ?>
        <a href="index.php" class="nav-item active"><i class="fa-solid fa-cart-shopping"></i><span>Compras</span></a>
        <?php if (puedeAccederModulo('clientes')): ?><a href="../clientes/" class="nav-item"><i class="fa-solid fa-users"></i><span>Clientes</span></a><?php endif; ?>
    </nav>
    <div class="sidebar-footer"><div class="user-info"><div class="user-avatar"><i class="fa-solid fa-user"></i></div><div class="user-details"><span class="user-name"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span><span class="user-role"><?= htmlspecialchars($_SESSION['usuario_rol']) ?></span></div></div><a href="../../auth/logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button><div class="topbar-right"><span class="topbar-user"><strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong><small><?= htmlspecialchars($_SESSION['usuario_rol']) ?></small></span></div></header>
    <div class="page-content">
        <div class="page-header">
            <div><h1 class="page-title">Compras</h1><p class="page-subtitle">Órdenes de compra a proveedores</p></div>
            <div class="page-actions"><a href="nueva.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nueva Compra</a></div>
        </div>
        <div class="stats-grid">
            <div class="stat-card stat-blue"><div class="stat-info"><span class="stat-label">Total Compras</span><span class="stat-value"><?= $total_compras ?></span></div><div class="stat-icon"><i class="fa-solid fa-shopping-cart"></i></div></div>
            <div class="stat-card stat-green"><div class="stat-info"><span class="stat-label">Total Gastado</span><span class="stat-value">$<?= number_format($total_gastado, 0) ?></span></div><div class="stat-icon"><i class="fa-solid fa-dollar-sign"></i></div></div>
            <div class="stat-card stat-orange"><div class="stat-info"><span class="stat-label">Pendientes</span><span class="stat-value"><?= $pendientes ?></span></div><div class="stat-icon"><i class="fa-solid fa-clock"></i></div></div>
        </div>
        <div class="table-card">
            <table class="data-table">
                <thead><tr><th>Orden</th><th>Fecha</th><th>Proveedor</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php if (empty($compras)): ?>
                    <tr><td colspan="6" class="empty-state"><i class="fa-solid fa-cart-shopping"></i><p>No hay compras registradas</p></td></tr>
                    <?php else: ?>
                    <?php foreach ($compras as $c): ?>
                    <tr>
                        <td><strong><?= $c['numero'] ?></strong></td>
                        <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                        <td><?= htmlspecialchars($c['proveedor_nombre']) ?></td>
                        <td>$<?= number_format($c['total'], 0) ?></td>
                        <td><span class="badge badge-<?= $c['estado'] ?>"><?= $c['estado'] ?></span></td>
                        <td><div class="action-btns"><a href="ver.php?id=<?= $c['id'] ?>" class="btn-icon" title="Ver"><i class="fa-solid fa-eye"></i></a></div></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>