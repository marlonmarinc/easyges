<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require_once '../../../config/conexion.php';

$term = $_GET['term'] ?? '';

$stmt = db()->prepare("
    SELECT id, codigo, nombre, precio_venta, stock 
    FROM productos 
    WHERE activo = 1 AND stock > 0 AND (nombre LIKE ? OR codigo LIKE ?)
    LIMIT 20
");
$stmt->execute(["%$term%", "%$term%"]);
$productos = $stmt->fetchAll();

echo json_encode($productos);