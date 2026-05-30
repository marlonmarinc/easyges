<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("
    SELECT f.*, c.nombre as cliente_nombre, c.tipo_doc, c.numero_doc, c.email, c.telefono, c.direccion, c.ciudad,
           u.nombre as usuario_nombre
    FROM facturas f
    JOIN clientes c ON f.cliente_id = c.id
    JOIN usuarios u ON f.usuario_id = u.id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$factura = $stmt->fetch();

if (!$factura) {
    header('Location: index.php');
    exit;
}

$stmt = db()->prepare("
    SELECT fd.*, p.nombre as producto_nombre, p.codigo
    FROM factura_detalle fd
    JOIN productos p ON fd.producto_id = p.id
    WHERE fd.factura_id = ?
");
$stmt->execute([$id]);
$detalles = $stmt->fetchAll();

$stmt = db()->query("SELECT * FROM empresa LIMIT 1");
$empresa = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?= $factura['numero'] ?> - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/facturacion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .factura-print { max-width: 800px; margin: 0 auto; }
        .factura-header-print { display: flex; justify-content: space-between; margin-bottom: 2rem; }
        .factura-datos { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
        .factura-datos h4 { margin: 0 0 .5rem 0; color: var(--gray-600); font-size: .8rem; text-transform: uppercase; }
        .factura-datos p { margin: 0; color: var(--gray-800); }
        .factura-info-box { background: var(--gray-50); padding: 1.25rem; border-radius: 10px; }
    </style>
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
        <a href="index.php" class="nav-item active">
            <i class="fa-solid fa-file-invoice"></i><span>Facturación</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="index.php" class="btn-logout">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Factura <?= htmlspecialchars($factura['numero']) ?></h1>
                <p class="page-subtitle">Detalle de la factura</p>
            </div>
            <div class="page-actions">
                <a href="pdf.php?id=<?= $id ?>" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Imprimir / PDF
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="factura-print">
            <div class="factura-section" style="margin-bottom: 1.5rem;">
                <div class="factura-header-print">
                    <div>
                        <h2 style="margin: 0; color: var(--teal);"><?= htmlspecialchars($empresa['nombre'] ?? 'EASYGES') ?></h2>
                        <p style="margin: .25rem 0;"><?= htmlspecialchars($empresa['nit'] ?? '') ?></p>
                        <p style="margin: 0; font-size: .9rem; color: var(--gray-600);">
                            <?= htmlspecialchars($empresa['direccion'] ?? '') ?>, <?= htmlspecialchars($empresa['ciudad'] ?? '') ?>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <h3 style="margin: 0; font-size: 1.5rem;">FACTURA DE VENTA</h3>
                        <p style="margin: .5rem 0;"><strong>No.</strong> <?= htmlspecialchars($factura['numero']) ?></p>
                        <p style="margin: 0;"><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($factura['fecha'])) ?></p>
                    </div>
                </div>

                <div class="factura-datos">
                    <div class="factura-info-box">
                        <h4>Cliente</h4>
                        <p><strong><?= htmlspecialchars($factura['cliente_nombre']) ?></strong></p>
                        <p><?= htmlspecialchars($factura['tipo_doc']) ?>: <?= htmlspecialchars($factura['numero_doc'] ?? '') ?></p>
                        <p><?= htmlspecialchars($factura['email'] ?? '') ?></p>
                        <p><?= htmlspecialchars($factura['telefono'] ?? '') ?></p>
                        <p><?= htmlspecialchars($factura['direccion'] ?? '') ?>, <?= htmlspecialchars($factura['ciudad'] ?? '') ?></p>
                    </div>
                    <div class="factura-info-box">
                        <h4>Información</h4>
                        <p><strong>Estado:</strong> 
                            <span class="badge badge-<?= $factura['estado'] ?>"><?= ucfirst($factura['estado']) ?></span>
                        </p>
                        <p><strong>Método de Pago:</strong> <?= htmlspecialchars($factura['metodo_pago']) ?></p>
                        <p><strong>Vendedor:</strong> <?= htmlspecialchars($factura['usuario_nombre']) ?></p>
                    </div>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th style="text-align: center;">Cant.</th>
                            <th style="text-align: right;">P. Unitario</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['codigo']) ?></td>
                            <td><?= htmlspecialchars($d['producto_nombre']) ?></td>
                            <td style="text-align: center;"><?= $d['cantidad'] ?></td>
                            <td style="text-align: right;">$<?= number_format($d['precio_unitario'], 0) ?></td>
                            <td style="text-align: right;">$<?= number_format($d['subtotal'], 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="total-box">
                    <div class="row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($factura['subtotal'], 0) ?></span>
                    </div>
                    <div class="row">
                        <span>Descuento:</span>
                        <span>$<?= number_format($factura['descuento'], 0) ?></span>
                    </div>
                    <div class="row">
                        <span>IVA (19%):</span>
                        <span>$<?= number_format($factura['impuesto'], 0) ?></span>
                    </div>
                    <div class="row grand-total">
                        <span>Total:</span>
                        <span>$<?= number_format($factura['total'], 0) ?></span>
                    </div>
                </div>

                <?php if ($factura['notas']): ?>
                <div style="margin-top: 1.5rem; padding: 1rem; background: var(--gray-50); border-radius: 8px;">
                    <strong>Notas:</strong> <?= htmlspecialchars($factura['notas']) ?>
                </div>
                <?php endif; ?>
            </div>
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