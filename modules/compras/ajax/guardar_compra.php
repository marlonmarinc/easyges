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
    
    $proveedorId = $data['proveedor_id'] ?? null;
    $fechaEntrega = $data['fecha_entrega'] ?? null;
    $notas = $data['notas'] ?? '';
    $items = $data['items'] ?? [];

    if (!$proveedorId) {
        echo json_encode(['ok' => false, 'error' => 'Seleccione un proveedor']);
        exit;
    }

    if (empty($items)) {
        echo json_encode(['ok' => false, 'error' => 'Agregue al menos un producto']);
        exit;
    }

    db()->beginTransaction();

    // Generar número de orden
    $stmt = db()->query("SELECT COUNT(*) + 1 as siguiente FROM compras");
    $sig = $stmt->fetch();
    $numero = 'OC-' . str_pad($sig['siguiente'], 5, '0', STR_PAD_LEFT);

    // Calcular totales
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['precio'] * $item['cantidad'];
    }
    $impuesto = $subtotal * 0.19;
    $total = $subtotal + $impuesto;

    // Insertar compra
    $stmt = db()->prepare("
        INSERT INTO compras (numero, proveedor_id, usuario_id, fecha, fecha_entrega, subtotal, impuesto, total, estado, notas)
        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, 'pendiente', ?)
    ");
    $stmt->execute([$numero, $proveedorId, $_SESSION['usuario_id'], $fechaEntrega, $subtotal, $impuesto, $total, $notas]);
    $compraId = db()->lastInsertId();

    // Insertar detalle
    $stmt = db()->prepare("
        INSERT INTO compra_detalle (compra_id, producto_id, cantidad, precio_unitario, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");
    foreach ($items as $item) {
        $itemSubtotal = $item['precio'] * $item['cantidad'];
        $stmt->execute([$compraId, $item['producto_id'], $item['cantidad'], $item['precio'], $itemSubtotal]);
    }

    db()->commit();

    echo json_encode([
        'ok' => true,
        'compra_id' => $compraId,
        'numero' => $numero
    ]);

} catch (Exception $e) {
    db()->rollBack();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}