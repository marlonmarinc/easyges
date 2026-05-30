<?php
// ============================================
// config/permisos.php
// Sistema de control de acceso por roles
// ============================================

function getPermisos($rol) {
    $permisos = [
        // Dashboard siempre accesible
        'dashboard' => true,
        
        // ROLES: administrador, contador, cajero, facturacion, inventario, compras, vendedor
        
        // ADMINISTRADOR - Acceso total
        'facturacion' => [
            'administrador' => true,
            'contador' => false,
            'cajero' => true,
        ],
        'inventario' => [
            'administrador' => true,
            'contador' => true,
            'cajero' => false,
        ],
        'compras' => [
            'administrador' => true,
            'contador' => false,
            'cajero' => false,
        ],
        'clientes' => [
            'administrador' => true,
            'contador' => false,
            'cajero' => false,
        ],
        'contabilidad' => [
            'administrador' => true,
            'contador' => true,
            'cajero' => false,
        ],
        'admin' => [
            'administrador' => true,
            'contador' => false,
            'cajero' => false,
        ],
    ];
    
    return $permisos;
}

function tieneAcceso($modulo, $rol = null) {
    if ($rol === null) {
        $rol = $_SESSION['usuario_rol'] ?? 'invitado';
    }
    
    $permisos = getPermisos($rol);
    
    // Si el módulo no está en la lista, permitir acceso por defecto
    if (!isset($permisos[$modulo])) {
        return true;
    }
    
    $config = $permisos[$modulo];
    
    // Si el rol tiene acceso explícito
    if (isset($config[$rol]) && $config[$rol] === true) {
        return true;
    }
    
    return false;
}

function puedeAccederModulo($modulo) {
    $rol = $_SESSION['usuario_rol'] ?? 'invitado';
    return tieneAcceso($modulo, $rol);
}