<?php

function getPermisos() {
    return [
        'dashboard' => true,
        'facturacion' => ['admin' => true, 'vendedor' => true, 'facturacion' => true],
        'inventario'  => ['admin' => true, 'inventario' => true, 'vendedor' => true],
        'compras'     => ['admin' => true, 'compras' => true],
        'clientes'    => ['admin' => true, 'vendedor' => true, 'facturacion' => true],
        'contabilidad'=> ['admin' => true, 'facturacion' => true, 'vendedor' => true],
        'admin'       => ['admin' => true],
        'configuracion' => ['admin' => true],
        'reportes'    => ['admin' => true, 'facturacion' => true, 'vendedor' => true],
    ];
}

function puedeAccederModulo($modulo) {
    $permisos = getPermisos();
    if (!isset($permisos[$modulo])) return true;
    if ($permisos[$modulo] === true) return true;
    $rol = $_SESSION['usuario_rol'] ?? 'invitado';
    return isset($permisos[$modulo][$rol]) && $permisos[$modulo][$rol] === true;
}