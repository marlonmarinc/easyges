<?php
// ============================================
// Proteger acceso a módulos
// Include al inicio de cada archivo PHP del módulo
// ============================================

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/permisos.php';

$modulo_actual = basename(dirname($_SERVER['SCRIPT_NAME']));
$rol = $_SESSION['usuario_rol'] ?? 'invitado';

if (!puedeAccederModulo($modulo_actual)) {
    // Redirigir al dashboard con mensaje de error
    $_SESSION['error_acceso'] = 'No tienes acceso a este módulo';
    header('Location: ../dashboard.php');
    exit;
}