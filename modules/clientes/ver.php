<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header('Location: index.php');
    exit;
}

$stmt = db()->prepare("SELECT * FROM facturas WHERE cliente_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$id]);
$facturas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/clientes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><div class="sidebar-logo"><i class="fa-solid fa-building"></i></div><div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión de Ventas</span></div></div>
    <nav class="sidebar-nav"><a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a><a href="index.php" class="nav-item active"><i class="fa-solid fa-users"></i><span>Clientes</span></a></nav>
    <div class="sidebar-footer"><a href="index.php" class="btn-logout"><i class="fa-solid fa-arrow-left"></i> Volver</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button></header>
    <div class="page-content">
        <div class="page-header">
            <div><h1 class="page-title"><?= htmlspecialchars($cliente['nombre']) ?> <?= htmlspecialchars($cliente['apellido'] ?? '') ?></h1><p class="page-subtitle"><?= $cliente['tipo_doc'] ?> <?= htmlspecialchars($cliente['numero_doc'] ?? '-') ?></p></div>
            <div class="page-actions"><a href="editar.php?id=<?= $id ?>" class="btn btn-primary"><i class="fa-solid fa-edit"></i> Editar</a></div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-section">
                <h3><i class="fa-solid fa-user"></i> Datos de Contacto</h3>
                <p><strong>Email:</strong> <?= htmlspecialchars($cliente['email'] ?? '-') ?></p>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['telefono'] ?? '-') ?></p>
                <p><strong>Dirección:</strong> <?= htmlspecialchars($cliente['direccion'] ?? '-') ?></p>
                <p><strong>Ciudad:</strong> <?= htmlspecialchars($cliente['ciudad'] ?? '-') ?>, <?= htmlspecialchars($cliente['departamento'] ?? '-') ?></p>
            </div>
        </div>
        <div class="form-section" style="margin-top: 1.5rem;">
            <h3><i class="fa-solid fa-file-invoice"></i> Historial de Compras</h3>
            <?php if (empty($facturas)): ?><p style="color: var(--gray-400);">No hay compras registradas</p>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Factura</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($facturas as $f): ?>
                    <tr><td><?= $f['numero'] ?></td><td><?= date('d/m/Y', strtotime($f['fecha'])) ?></td><td>$<?= number_format($f['total'], 0) ?></td><td><span class="badge badge-<?= $f['estado'] ?>"><?= $f['estado'] ?></span></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>