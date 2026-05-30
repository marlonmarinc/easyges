<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$reporte = $_GET['reporte'] ?? 'ventas';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

function formatFecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function formatMoneda($valor) {
    return '$' . number_format($valor, 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .reportes-container { max-width: 1200px; margin: 0 auto; }
        .report-menu { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .report-menu a { padding: 0.75rem 1.25rem; text-decoration: none; border-radius: 6px; background: var(--gray-100); color: var(--gray-700); font-weight: 500; }
        .report-menu a.active { background: var(--teal); color: white; }
        .report-filters { background: white; padding: 1.25rem; border-radius: var(--radius); margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
        .report-filters label { font-weight: 600; font-size: 0.85rem; color: var(--gray-700); display: block; margin-bottom: 0.35rem; }
        .report-filters input, .report-filters select { padding: 0.6rem; border: 1px solid var(--gray-300); border-radius: 6px; }
        .report-filters button { background: var(--teal); color: white; border: none; padding: 0.6rem 1.25rem; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .report-card { background: white; border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; }
        .report-card h3 { margin: 0 0 1rem 0; font-size: 1.1rem; color: var(--gray-800); border-bottom: 2px solid var(--teal); padding-bottom: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-box { background: var(--gray-50); padding: 1rem; border-radius: 8px; text-align: center; }
        .stat-box .label { font-size: 0.85rem; color: var(--gray-600); }
        .stat-box .value { font-size: 1.5rem; font-weight: 700; color: var(--gray-900); }
        .report-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .report-table th { background: var(--gray-100); padding: 0.75rem; text-align: left; font-weight: 600; color: var(--gray-700); border-bottom: 2px solid var(--gray-300); }
        .report-table td { padding: 0.75rem; border-bottom: 1px solid var(--gray-200); }
        .report-table tr:hover { background: var(--gray-50); }
        .badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-verde { background: #d1fae5; color: #065f46; }
        .badge-amarillo { background: #fef3c7; color: #92400e; }
        .badge-rojo { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-building"></i></div>
        <div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión</span></div>
    </div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
        <a href="../facturacion/" class="nav-item"><i class="fa-solid fa-file-invoice"></i><span>Facturación</span></a>
        <a href="../inventario/" class="nav-item"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a>
        <a href="../compras/" class="nav-item"><i class="fa-solid fa-cart-shopping"></i><span>Compras</span></a>
        <a href="../clientes/" class="nav-item"><i class="fa-solid fa-users"></i><span>Clientes</span></a>
    </nav>
    <div class="sidebar-footer"><a href="../../dashboard.php" class="btn-logout"><i class="fa-solid fa-arrow-left"></i> Volver</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button></header>
    <div class="page-content">
        <div class="page-header">
            <h1 class="page-title"><i class="fa-solid fa-chart-bar"></i> Reportes</h1>
        </div>
        
        <div class="reportes-container">
            <div class="report-menu">
                <a href="?reporte=ventas" class="<?= $reporte=='ventas'?'active':'' ?>"><i class="fa-solid fa-file-invoice"></i> Ventas</a>
                <a href="?reporte=productos" class="<?= $reporte=='productos'?'active':'' ?>"><i class="fa-solid fa-box"></i> Productos</a>
                <a href="?reporte=clientes" class="<?= $reporte=='clientes'?'active':'' ?>"><i class="fa-solid fa-users"></i> Clientes</a>
                <a href="?reporte=compras" class="<?= $reporte=='compras'?'active':'' ?>"><i class="fa-solid fa-truck"></i> Compras</a>
                <a href="?reporte=inventario" class="<?= $reporte=='inventario'?'active':'' ?>"><i class="fa-solid fa-warehouse"></i> Inventario</a>
            </div>

            <form class="report-filters">
                <div>
                    <label>Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>">
                </div>
                <div>
                    <label>Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="submit">Filtrar</button>
                </div>
                <input type="hidden" name="reporte" value="<?= $reporte ?>">
            </form>

            <?php if ($reporte == 'ventas'): ?>
            <?php
            $stmt = db()->prepare("
                SELECT COUNT(*) as total, COALESCE(SUM(total),0) as monto, COALESCE(SUM(impuesto),0) as impuestos
                FROM facturas 
                WHERE fecha BETWEEN ? AND ? AND estado != 'anulada'
            ");
            $stmt->execute([$fecha_inicio, $fecha_fin]);
            $resumen = $stmt->fetch();

            $stmt = db()->prepare("
                SELECT f.*, c.nombre as cliente_nombre, u.nombre as usuario_nombre
                FROM facturas f
                LEFT JOIN clientes c ON f.cliente_id = c.id
                LEFT JOIN usuarios u ON f.usuario_id = u.id
                WHERE f.fecha BETWEEN ? AND ? AND f.estado != 'anulada'
                ORDER BY f.fecha DESC
                LIMIT 100
            ");
            $stmt->execute([$fecha_inicio, $fecha_fin]);
            $facturas = $stmt->fetchAll();
            ?>
            <div class="report-card">
                <h3><i class="fa-solid fa-file-invoice"></i> Resumen de Ventas</h3>
                <div class="stats-grid">
                    <div class="stat-box"><div class="label">Facturas</div><div class="value"><?= $resumen['total'] ?></div></div>
                    <div class="stat-box"><div class="label">Total Ventas</div><div class="value"><?= formatMoneda($resumen['monto']) ?></div></div>
                    <div class="stat-box"><div class="label">IVA</div><div class="value"><?= formatMoneda($resumen['impuestos']) ?></div></div>
                    <div class="stat-box"><div class="label">Promedio por Factura</div><div class="value"><?= $resumen['total'] > 0 ? formatMoneda($resumen['monto'] / $resumen['total']) : '$0' ?></div></div>
                </div>
            </div>
            <div class="report-card">
                <h3>Detalle de Facturas</h3>
                <table class="report-table">
                    <thead><tr><th>Fecha</th><th>Factura</th><th>Cliente</th><th>Subtotal</th><th>IVA</th><th>Total</th><th>Método</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($facturas as $f): ?>
                    <tr>
                        <td><?= formatFecha($f['fecha']) ?></td>
                        <td><?= $f['numero'] ?></td>
                        <td><?= htmlspecialchars($f['cliente_nombre'] ?? 'Mostrador') ?></td>
                        <td><?= formatMoneda($f['subtotal']) ?></td>
                        <td><?= formatMoneda($f['impuesto']) ?></td>
                        <td><strong><?= formatMoneda($f['total']) ?></strong></td>
                        <td><?= $f['metodo_pago'] ?></td>
                        <td><span class="badge badge-<?= $f['estado']=='pagada'?'verde':($f['estado']=='pendiente'?'amarillo':'rojo') ?>"><?= $f['estado'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php elseif ($reporte == 'productos'): ?>
            <?php
            $stmt = db()->query("
                SELECT p.codigo, p.nombre, p.stock, p.precio_venta, p.stock_minimo,
                       COALESCE(SUM(fd.cantidad),0) as vendidos
                FROM productos p
                LEFT JOIN factura_detalle fd ON p.id = fd.producto_id
                LEFT JOIN facturas f ON fd.factura_id = f.id AND f.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' AND f.estado != 'anulada'
                WHERE p.activo = 1
                GROUP BY p.id
                ORDER BY vendidos DESC
                LIMIT 30
            ");
            $productos = $stmt->fetchAll();

            $totalVendidos = array_sum(array_column($productos, 'vendidos'));
            $totalStock = array_sum(array_column($productos, 'stock'));
            ?>
            <div class="report-card">
                <h3><i class="fa-solid fa-box"></i> Productos Más Vendidos</h3>
                <div class="stats-grid">
                    <div class="stat-box"><div class="label">Productos Vendidos</div><div class="value"><?= $totalVendidos ?></div></div>
                    <div class="stat-box"><div class="label">Total Stock</div><div class="value"><?= $totalStock ?></div></div>
                </div>
            </div>
            <div class="report-card">
                <table class="report-table">
                    <thead><tr><th>Código</th><th>Producto</th><th>Vendidos</th><th>Stock</th><th>Precio</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['codigo']) ?></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= $p['vendidos'] ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><?= formatMoneda($p['precio_venta']) ?></td>
                        <td><span class="badge badge-<?= $p['stock'] > $p['stock_minimo'] ? 'verde' : 'rojo' ?>"><?= $p['stock'] > $p['stock_minimo'] ? 'OK' : 'Bajo' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php elseif ($reporte == 'clientes'): ?>
            <?php
            $stmt = db()->query("
                SELECT c.id, c.nombre, c.tipo_doc, c.numero_doc,
                       COUNT(f.id) as facturas,
                       COALESCE(SUM(f.total),0) as total
                FROM clientes c
                LEFT JOIN facturas f ON c.id = f.cliente_id AND f.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' AND f.estado != 'anulada'
                WHERE c.activo = 1
                GROUP BY c.id
                ORDER BY total DESC
                LIMIT 30
            ");
            $clientes = $stmt->fetchAll();
            ?>
            <div class="report-card">
                <h3><i class="fa-solid fa-users"></i> Mejores Clientes</h3>
                <table class="report-table">
                    <thead><tr><th>Cliente</th><th>Documento</th><th>Facturas</th><th>Total Comprado</th></tr></thead>
                    <tbody>
                    <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['nombre']) ?></td>
                        <td><?= $c['tipo_doc'] ?> <?= $c['numero_doc'] ?></td>
                        <td><?= $c['facturas'] ?></td>
                        <td><strong><?= formatMoneda($c['total']) ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php elseif ($reporte == 'compras'): ?>
            <?php
            $stmt = db()->query("
                SELECT COUNT(*) as total, COALESCE(SUM(total),0) as monto
                FROM compras 
                WHERE fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' AND estado != 'anulada'
            ");
            $resumen = $stmt->fetch();

            $stmt = db()->query("
                SELECT c.*, p.nombre as proveedor_nombre
                FROM compras c
                JOIN proveedores p ON c.proveedor_id = p.id
                WHERE c.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin' AND c.estado != 'anulada'
                ORDER BY c.fecha DESC
                LIMIT 50
            ");
            $compras = $stmt->fetchAll();
            ?>
            <div class="report-card">
                <h3><i class="fa-solid fa-truck"></i> Resumen de Compras</h3>
                <div class="stats-grid">
                    <div class="stat-box"><div class="label">Órdenes</div><div class="value"><?= $resumen['total'] ?></div></div>
                    <div class="stat-box"><div class="label">Total Compras</div><div class="value"><?= formatMoneda($resumen['monto']) ?></div></div>
                </div>
            </div>
            <div class="report-card">
                <table class="report-table">
                    <thead><tr><th>Fecha</th><th>Orden</th><th>Proveedor</th><th>Total</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($compras as $c): ?>
                    <tr>
                        <td><?= formatFecha($c['fecha']) ?></td>
                        <td><?= $c['numero'] ?></td>
                        <td><?= htmlspecialchars($c['proveedor_nombre']) ?></td>
                        <td><?= formatMoneda($c['total']) ?></td>
                        <td><span class="badge badge-<?= $c['estado']=='recibida'?'verde':($c['estado']=='pendiente'?'amarillo':'rojo') ?>"><?= $c['estado'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php elseif ($reporte == 'inventario'): ?>
            <?php
            $stmt = db()->query("
                SELECT COUNT(*) as total FROM productos WHERE activo = 1
            ");
            $total = $stmt->fetchColumn();

            $stmt = db()->query("
                SELECT COUNT(*) as bajo FROM productos WHERE activo = 1 AND stock <= stock_minimo
            ");
            $bajo = $stmt->fetchColumn();

            $stmt = db()->query("
                SELECT COUNT(*) as sin FROM productos WHERE activo = 1 AND stock = 0
            ");
            $sin = $stmt->fetchColumn();

            $stmt = db()->query("
                SELECT p.codigo, p.nombre, p.stock, p.stock_minimo, p.precio_compra, p.precio_venta
                FROM productos p
                WHERE p.activo = 1
                ORDER BY p.stock ASC
                LIMIT 30
            ");
            $productos = $stmt->fetchAll();
            ?>
            <div class="report-card">
                <h3><i class="fa-solid fa-warehouse"></i> Estado del Inventario</h3>
                <div class="stats-grid">
                    <div class="stat-box"><div class="label">Total Productos</div><div class="value"><?= $total ?></div></div>
                    <div class="stat-box"><div class="label">Stock Bajo</div><div class="value"><?= $bajo ?></div></div>
                    <div class="stat-box"><div class="label">Sin Stock</div><div class="value"><?= $sin ?></div></div>
                </div>
            </div>
            <div class="report-card">
                <table class="report-table">
                    <thead><tr><th>Código</th><th>Producto</th><th>Stock</th><th>Stock Mín</th><th>Costo</th><th>Venta</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($productos as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['codigo']) ?></td>
                        <td><?= htmlspecialchars($p['nombre']) ?></td>
                        <td><?= $p['stock'] ?></td>
                        <td><?= $p['stock_minimo'] ?></td>
                        <td><?= formatMoneda($p['precio_compra']) ?></td>
                        <td><?= formatMoneda($p['precio_venta']) ?></td>
                        <td><span class="badge badge-<?= $p['stock']==0?'rojo':($p['stock']<=$p['stock_minimo']?'amarillo':'verde') ?>"><?= $p['stock']==0?'Sin Stock':($p['stock']<$p['stock_minimo']?'Bajo':'OK') ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>