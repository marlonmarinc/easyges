<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$stmt = db()->query("SELECT * FROM empresa LIMIT 1");
$empresa = $stmt->fetch();

if (!$empresa) {
    db()->exec("INSERT INTO empresa (nit, nombre, nombre_comercial, direccion, ciudad, departamento, telefono, email, regimen) VALUES ('', 'Mi Empresa', '', '', '', '', '', '', 'comun')");
    $stmt = db()->query("SELECT * FROM empresa LIMIT 1");
    $empresa = $stmt->fetch();
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nit = trim($_POST['nit'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $nombre_comercial = trim($_POST['nombre_comercial'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $regimen = $_POST['regimen'] ?? 'comun';
    $prefijo_factura = trim($_POST['prefijo_factura'] ?? 'FEP');
    $logo = $empresa['logo'];

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $nombreLogo = 'logo_' . time() . '.' . $ext;
        $rutaLogo = '../../uploads/' . $nombreLogo;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $rutaLogo)) {
            $logo = 'uploads/' . $nombreLogo;
        }
    }

    if (empty($nit) || empty($nombre)) {
        $error = 'El NIT y nombre son requeridos';
    } else {
        $stmt = db()->prepare("UPDATE empresa SET nit=?, nombre=?, nombre_comercial=?, direccion=?, ciudad=?, departamento=?, telefono=?, email=?, regimen=?, prefijo_factura=?, logo=? WHERE id=?");
        $stmt->execute([$nit, $nombre, $nombre_comercial, $direccion, $ciudad, $departamento, $telefono, $email, $regimen, $prefijo_factura, $logo, $empresa['id']]);
        $msg = 'Datos guardados correctamente';
        $stmt = db()->query("SELECT * FROM empresa LIMIT 1");
        $empresa = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .config-form { max-width: 700px; margin: 0 auto; }
        .config-section { background: white; border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; }
        .config-section h3 { margin: 0 0 1.25rem 0; font-size: 1.1rem; color: var(--gray-800); padding-bottom: .75rem; border-bottom: 2px solid var(--teal-light); }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-weight: 600; font-size: .85rem; margin-bottom: .35rem; color: var(--gray-700); }
        .form-control { width: 100%; padding: .75rem; border: 1px solid var(--gray-300); border-radius: var(--radius-sm); font-size: .9rem; }
        .form-control:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(0,180,216,.1); }
        .btn-save { background: var(--teal); color: white; padding: .75rem 2rem; border: none; border-radius: var(--radius-sm); font-weight: 600; cursor: pointer; }
        .btn-save:hover { background: var(--teal-dark); }
        .alert { padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><div class="sidebar-logo"><i class="fa-solid fa-building"></i></div><div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión de Ventas</span></div></div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
    </nav>
    <div class="sidebar-footer"><a href="../../auth/logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button></header>
    <div class="page-content">
        <div class="page-header"><div><h1 class="page-title"><i class="fa-solid fa-gear"></i> Configuración de la Empresa</h1><p class="page-subtitle">Datos que aparecen en las facturas</p></div></div>
        
        <?php if ($msg): ?><div class="alert alert-success"><i class="fa-solid fa-check-circle"></i> <?= $msg ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div><?php endif; ?>
        
        <form method="POST" class="config-form" enctype="multipart/form-data">
            <div class="config-section">
                <h3><i class="fa-solid fa-building"></i> Datos de la Empresa</h3>
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Logo de la Empresa</label>
                    <?php if (!empty($empresa['logo'])): ?>
                    <div style="margin-bottom:.5rem;"><img src="../../<?= $empresa['logo'] ?>" alt="Logo" style="max-height:80px;"></div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                </div>
                <div class="form-row">
                    <div class="form-group"><label>NIT *</label><input type="text" name="nit" class="form-control" value="<?= htmlspecialchars($empresa['nit'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Régimen</label><select name="regimen" class="form-control"><option value="comun" <?= ($empresa['regimen'] ?? '')=='comun'?'selected':'' ?>>Régimen Común</option><option value="simplificado" <?= ($empresa['regimen'] ?? '')=='simplificado'?'selected':'' ?>>Régimen Simplificado</option></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Razón Social *</label><input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($empresa['nombre'] ?? '') ?>" required></div>
                    <div class="form-group"><label>Nombre Comercial</label><input type="text" name="nombre_comercial" class="form-control" value="<?= htmlspecialchars($empresa['nombre_comercial'] ?? '') ?>"></div>
                </div>
            </div>
            
            <div class="config-section">
                <h3><i class="fa-solid fa-map-marker"></i> Ubicación</h3>
                <div class="form-group" style="margin-bottom: 1rem;"><label>Dirección</label><input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($empresa['direccion'] ?? '') ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label>Ciudad</label><input type="text" name="ciudad" class="form-control" value="<?= htmlspecialchars($empresa['ciudad'] ?? '') ?>"></div>
                    <div class="form-group"><label>Departamento</label><input type="text" name="departamento" class="form-control" value="<?= htmlspecialchars($empresa['departamento'] ?? '') ?>"></div>
                </div>
            </div>
            
            <div class="config-section">
                <h3><i class="fa-solid fa-phone"></i> Contacto</h3>
                <div class="form-row">
                    <div class="form-group"><label>Teléfono</label><input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($empresa['telefono'] ?? '') ?>"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>"></div>
                </div>
            </div>
            
            <div class="config-section">
                <h3><i class="fa-solid fa-file-invoice"></i> Configuración de Facturación</h3>
                <div class="form-group"><label>Prefijo de Facturas</label><input type="text" name="prefijo_factura" class="form-control" value="<?= htmlspecialchars($empresa['prefijo_factura'] ?? 'FEP') ?>" placeholder="FEP"><small style="color: var(--gray-500);">Las facturas se generarán como: FEP-000001</small></div>
            </div>
            
            <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Guardar Cambios</button>
        </form>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>