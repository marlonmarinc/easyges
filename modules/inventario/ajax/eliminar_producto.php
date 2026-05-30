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

    $stmt = db()->prepare("SELECT stock FROM productos WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        echo json_encode(['ok' => false, 'error' => 'Producto no encontrado']);
        exit;
    }

    if ($producto['stock'] > 0) {
        echo json_encode(['ok' => false, 'error' => 'No puede eliminar productos con stock']);
        exit;
    }

    db()->prepare("UPDATE productos SET activo = 0 WHERE id = ?")->execute([$id]);

    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}