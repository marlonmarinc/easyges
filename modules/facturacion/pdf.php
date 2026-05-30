<?php
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
    die('Factura no encontrada');
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
    <title>Factura <?= $factura['numero'] ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; font-size: 12px; color: #333; }
        .invoice { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #00b4d8; padding-bottom: 15px; }
        .company h1 { color: #00b4d8; font-size: 20px; margin-bottom: 5px; }
        .company p { font-size: 11px; color: #666; }
        .invoice-title { text-align: right; }
        .invoice-title h2 { font-size: 18px; color: #333; }
        .invoice-title p { font-size: 12px; margin-top: 5px; }
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-box { background: #f8f9fa; padding: 12px; border-radius: 6px; width: 48%; }
        .info-box h4 { font-size: 10px; text-transform: uppercase; color: #666; margin-bottom: 8px; }
        .info-box p { margin-bottom: 3px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #00b4d8; color: white; padding: 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        td { padding: 10px; border-bottom: 1px solid #eee; font-size: 11px; }
        th:last-child, td:last-child { text-align: right; }
        .totals { float: right; width: 250px; }
        .totals table { margin-bottom: 0; }
        .totals td { border: none; padding: 5px 10px; }
        .totals tr:last-child td { font-size: 16px; font-weight: bold; border-top: 2px solid #333; padding-top: 10px; }
        .clear { clear: both; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #666; }
        .status { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; }
        .status-pagada { background: #dcfce7; color: #166534; }
        .status-pendiente { background: #fef9c3; color: #854d0e; }
        .status-anulada { background: #fee2e2; color: #991b1b; }
        
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .invoice { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="invoice">
        <div class="header">
            <div class="company">
                <h1><?= htmlspecialchars($empresa['nombre'] ?? 'EASYGES') ?></h1>
                <p>NIT: <?= htmlspecialchars($empresa['nit'] ?? '') ?></p>
                <p><?= htmlspecialchars($empresa['direccion'] ?? '') ?></p>
                <p><?= htmlspecialchars($empresa['ciudad'] ?? '') ?>, <?= htmlspecialchars($empresa['departamento'] ?? '') ?></p>
                <p>Tel: <?= htmlspecialchars($empresa['telefono'] ?? '') ?></p>
                <p>Email: <?= htmlspecialchars($empresa['email'] ?? '') ?></p>
            </div>
            <div class="invoice-title">
                <h2>FACTURA DE VENTA</h2>
                <p><strong>No.</strong> <?= htmlspecialchars($factura['numero']) ?></p>
                <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($factura['fecha'])) ?></p>
                <p style="margin-top: 8px;">
                    <span class="status status-<?= $factura['estado'] ?>"><?= strtoupper($factura['estado']) ?></span>
                </p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h4>CLIENTE</h4>
                <p><strong><?= htmlspecialchars($factura['cliente_nombre']) ?></strong></p>
                <p><?= htmlspecialchars($factura['tipo_doc']) ?>: <?= htmlspecialchars($factura['numero_doc'] ?? '') ?></p>
                <p><?= htmlspecialchars($factura['email'] ?? '') ?></p>
                <p><?= htmlspecialchars($factura['telefono'] ?? '') ?></p>
                <p><?= htmlspecialchars($factura['direccion'] ?? '') ?></p>
                <p><?= htmlspecialchars($factura['ciudad'] ?? '') ?></p>
            </div>
            <div class="info-box">
                <h4>INFORMACIÓN</h4>
                <p><strong>Método de Pago:</strong> <?= htmlspecialchars($factura['metodo_pago']) ?></p>
                <p><strong>Vendedor:</strong> <?= htmlspecialchars($factura['usuario_nombre']) ?></p>
                <?php if ($factura['notas']): ?>
                <p><strong>Notas:</strong> <?= htmlspecialchars($factura['notas']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th style="text-align: center;">Cant.</th>
                    <th>P. Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['codigo']) ?></td>
                    <td><?= htmlspecialchars($d['producto_nombre']) ?></td>
                    <td style="text-align: center;"><?= $d['cantidad'] ?></td>
                    <td>$<?= number_format($d['precio_unitario'], 0) ?></td>
                    <td>$<?= number_format($d['subtotal'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="clear"></div>
        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td>$<?= number_format($factura['subtotal'], 0) ?></td>
                </tr>
                <tr>
                    <td>Descuento:</td>
                    <td>$<?= number_format($factura['descuento'], 0) ?></td>
                </tr>
                <tr>
                    <td>IVA (19%):</td>
                    <td>$<?= number_format($factura['impuesto'], 0) ?></td>
                </tr>
                <tr>
                    <td>TOTAL:</td>
                    <td>$<?= number_format($factura['total'], 0) ?></td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Gracias por su compra</p>
            <p>Sistema de Gestión - EASYGES</p>
        </div>
    </div>
</body>
</html>