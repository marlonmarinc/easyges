<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../../../config/conexion.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $clienteId = $data['cliente_id'] ?? null;
    $metodoPago = $data['metodo_pago'] ?? 'Efectivo';
    $notas = $data['observaciones'] ?? '';
    $items = $data['items'] ?? [];
    $fecha = $data['fecha'] ?? date('Y-m-d');

    if (!$clienteId) {
        $stmt = db()->query("SELECT id FROM clientes WHERE tipo_doc = 'CC' ORDER BY id LIMIT 1");
        $clienteDefault = $stmt->fetch();
        $clienteId = $clienteDefault['id'] ?? null;
    }

    if (empty($items)) {
        echo json_encode(['ok' => false, 'error' => 'No hay productos']);
        exit;
    }

    db()->beginTransaction();

    $stmt = db()->query("SELECT prefijo_factura FROM empresa LIMIT 1");
    $empresa = $stmt->fetch();
    $prefijo = $empresa['prefijo_factura'] ?? 'FEP';

    $stmt = db()->prepare("SELECT ultimo_numero + 1 as siguiente FROM consecutivos WHERE prefijo = ? AND anio = YEAR(NOW()) FOR UPDATE");
    $stmt->execute([$prefijo]);
    $sig = $stmt->fetch();

    if (!$sig) {
        $stmt = db()->prepare("INSERT INTO consecutivos (prefijo, ultimo_numero, anio) VALUES (?, 0, YEAR(NOW()))");
        $stmt->execute([$prefijo]);
        $sig['siguiente'] = 1;
    }

    $numero = $prefijo . '-' . str_pad($sig['siguiente'], 6, '0', STR_PAD_LEFT);

    // Calcular subtotal e IVA correctamente
    $subtotalSinIva = 0;
    $impuestoTotal = 0;

    // Obtener IDs de productos para consultar sus configuraciones de IVA
    $productoIds = array_column($items, 'producto_id');
    $productosInfo = [];
    
    if (!empty($productoIds)) {
        $placeholders = implode(',', array_fill(0, count($productoIds), '?'));
        $stmt = db()->prepare("SELECT id, iva_incluido, impuesto_pct FROM productos WHERE id IN ($placeholders)");
        $stmt->execute($productoIds);
        while ($row = $stmt->fetch()) {
            $productosInfo[$row['id']] = [
                'iva_incluido' => (bool)$row['iva_incluido'],
                'impuesto_pct' => (float)$row['impuesto_pct']
            ];
        }
    }

    foreach ($items as $item) {
        $precioUnitario = $item['precio_unitario'];
        $cantidad = $item['cantidad'];
        
        // Obtener configuración de IVA del producto
        $prodInfo = $productosInfo[$item['producto_id']] ?? ['iva_incluido' => false, 'impuesto_pct' => 19];
        
        // Calcular precio sin IVA y valor del IVA
        if ($prodInfo['iva_incluido']) {
            // El precio ya incluye IVA, calcular valor sin IVA
            $precioSinIva = $precioUnitario / (1 + $prodInfo['impuesto_pct'] / 100);
            $ivaItem = $precioUnitario - $precioSinIva;
        } else {
            // El precio no incluye IVA, agregar
            $precioSinIva = $precioUnitario;
            $ivaItem = $precioUnitario * ($prodInfo['impuesto_pct'] / 100);
        }
        
        $subtotalSinIva += $precioSinIva * $cantidad;
        $impuestoTotal += $ivaItem * $cantidad;
    }

    $descuento = 0;
    $total = $subtotalSinIva - $descuento + $impuestoTotal;

    $stmt = db()->prepare("
        INSERT INTO facturas (numero, cliente_id, usuario_id, fecha, subtotal, descuento, impuesto, total, metodo_pago, estado, notas)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pagada', ?)
    ");
    $stmt->execute([$numero, $clienteId, $_SESSION['usuario_id'], $fecha, $subtotalSinIva, $descuento, $impuestoTotal, $total, $metodoPago, $notas]);
    $facturaId = db()->lastInsertId();

    $stmt = db()->prepare("
        INSERT INTO factura_detalle (factura_id, producto_id, cantidad, precio_unitario, descuento_pct, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($items as $item) {
        $precioUnitario = $item['precio_unitario'];
        $cantidad = $item['cantidad'];
        
        // Obtener precio sin IVA para el detalle
        $prodInfo = $productosInfo[$item['producto_id']] ?? ['iva_incluido' => false, 'impuesto_pct' => 19];
        
        if ($prodInfo['iva_incluido']) {
            $precioSinIva = $precioUnitario / (1 + $prodInfo['impuesto_pct'] / 100);
        } else {
            $precioSinIva = $precioUnitario;
        }
        
        $itemSubtotal = $precioSinIva * $cantidad;
        
        $stmt->execute([
            $facturaId,
            $item['producto_id'],
            $cantidad,
            $precioSinIva,
            0,
            $itemSubtotal
        ]);

        db()->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?")
            ->execute([$cantidad, $item['producto_id']]);
    }

    db()->prepare("UPDATE consecutivos SET ultimo_numero = ? WHERE prefijo = ? AND anio = YEAR(NOW())")
        ->execute([$sig['siguiente'], $prefijo]);

    db()->commit();

    echo json_encode([
        'ok' => true,
        'factura_id' => $facturaId,
        'numero' => $numero,
        'debug' => [
            'subtotal' => $subtotalSinIva,
            'iva' => $impuestoTotal,
            'total' => $total
        ]
    ]);

} catch (Exception $e) {
    db()->rollBack();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}