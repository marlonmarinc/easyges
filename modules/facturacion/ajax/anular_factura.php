<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../../../config/conexion.php';

try {
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['ok' => false, 'error' => 'ID requerido']);
        exit;
    }

    $stmt = db()->prepare("SELECT estado FROM facturas WHERE id = ?");
    $stmt->execute([$id]);
    $factura = $stmt->fetch();

    if (!$factura) {
        echo json_encode(['ok' => false, 'error' => 'Factura no encontrada']);
        exit;
    }

    if ($factura['estado'] === 'anulada') {
        echo json_encode(['ok' => false, 'error' => 'Ya está anulada']);
        exit;
    }

    db()->beginTransaction();

    $stmt = db()->prepare("UPDATE facturas SET estado = 'anulada' WHERE id = ?");
    $stmt->execute([$id]);

    $stmt = db()->query("
        SELECT fd.producto_id, fd.cantidad 
        FROM factura_detalle fd 
        WHERE fd.factura_id = $id
    ");
    $detalles = $stmt->fetchAll();

    foreach ($detalles as $d) {
        db()->prepare("UPDATE productos SET stock = stock + ? WHERE id = ?")
            ->execute([$d['cantidad'], $d['producto_id']]);
    }

    db()->commit();

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    db()->rollBack();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}