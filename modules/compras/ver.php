<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT c.*, p.nombre as proveedor_nombre, p.nit as proveedor_nit, u.nombre as usuario_nombre FROM compras c JOIN proveedores p ON c.proveedor_id = p.id JOIN usuarios u ON c.usuario_id = u.id WHERE c.id = ?");
$stmt->execute([$id]);
$compra = $stmt->fetch();

if (!$compra) { header('Location: index.php'); exit; }

$stmt = db()->prepare("SELECT cd.*, pr.nombre as producto_nombre, pr.codigo FROM compra_detalle cd JOIN productos pr ON cd.producto_id = pr.id WHERE cd.compra_id = ?");
$stmt->execute([$id]);
$detalles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compra <?= $compra['numero'] ?> - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/compras.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><div class="sidebar-logo"><i class="fa-solid fa-building"></i></div><div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión de Ventas</span></div></div>
    <nav class="sidebar-nav"><a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a><a href="index.php" class="nav-item active"><i class="fa-solid fa-cart-shopping"></i><span>Compras</span></a></nav>
    <div class="sidebar-footer"><a href="index.php" class="btn-logout"><i class="fa-solid fa-arrow-left"></i> Volver</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button></header>
    <div class="page-content">
        <div class="page-header"><div><h1 class="page-title">Orden de Compra <?= $compra['numero'] ?></h1><p class="page-subtitle"><?= date('d/m/Y', strtotime($compra['fecha'])) ?></p></div></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
            <div class="form-section"><h3>Proveedor</h3><p><strong><?= htmlspecialchars($compra['proveedor_nombre']) ?></strong></p><p>NIT: <?= htmlspecialchars($compra['proveedor_nit']) ?></p></div>
            <div class="form-section"><h3>Información</h3><p><strong>Estado:</strong> <span class="badge badge-<?= $compra['estado'] ?>"><?= $compra['estado'] ?></span></p><p><strong>Fecha Entrega:</strong> <?= $compra['fecha_entrega'] ? date('d/m/Y', strtotime($compra['fecha_entrega'])) : 'Sin definir' ?></p><p><strong>Solicitó:</strong> <?= htmlspecialchars($compra['usuario_nombre']) ?></p></div>
        </div>
        <div class="form-section">
            <h3>Detalle de Compra</h3>
            <table class="data-table">
                <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr></thead>
                <tbody>
                    <?php foreach ($detalles as $d): ?>
                    <tr><td><?= htmlspecialchars($d['codigo']) ?> - <?= htmlspecialchars($d['producto_nombre']) ?></td><td><?= $d['cantidad'] ?></td><td>$<?= number_format($d['precio_unitario'], 0) ?></td><td>$<?= number_format($d['subtotal'], 0) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="3" style="text-align:right;font-weight:700;">TOTAL:</td><td style="font-weight:700;font-size:1.1rem;">$<?= number_format($compra['total'], 0) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
<style>.form-section{background:white;border-radius:var(--radius);padding:1.5rem;box-shadow:var(--shadow-sm);}.form-section h3{margin:0 0 1rem 0;font-size:1rem;color:var(--gray-800);padding-bottom:.75rem;border-bottom:1px solid var(--gray-200);}</style>
</body>
</html>