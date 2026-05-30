<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $tipo_doc = $_POST['tipo_doc'] ?? 'CC';
    $numero_doc = trim($_POST['numero_doc'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $departamento = trim($_POST['departamento'] ?? '');
    $tipo_moto = trim($_POST['tipo_moto'] ?? '');
    $estilo_moto = $_POST['estilo_moto'] ?? '';
    $talla_casco = trim($_POST['talla_casco'] ?? '');
    $talla_ropa = trim($_POST['talla_ropa'] ?? '');

    if (empty($nombre)) $errores[] = "El nombre es requerido";

    if (empty($errores)) {
        try {
            $stmt = db()->prepare("INSERT INTO clientes (nombre, apellido, tipo_doc, numero_doc, email, telefono, direccion, ciudad, departamento, tipo_moto, estilo_moto, talla_casco, talla_ropa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $apellido, $tipo_doc, $numero_doc, $email, $telefono, $direccion, $ciudad, $departamento, $tipo_moto, $estilo_moto, $talla_casco, $talla_ropa]);
            header('Location: index.php?msg=created');
            exit;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $errores[] = "El número de documento ya existe";
            } else {
                $errores[] = "Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Cliente - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/clientes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><div class="sidebar-logo"><i class="fa-solid fa-building"></i></div><div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión de Ventas</span></div></div>
    <nav class="sidebar-nav"><a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a><a href="index.php" class="nav-item active"><i class="fa-solid fa-users"></i><span>Clientes</span></a></nav>
    <div class="sidebar-footer"><a href="index.php" class="btn-logout"><i class="fa-solid fa-arrow-left"></i> Volver</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button></header>
    <div class="page-content">
        <div class="page-header"><div><h1 class="page-title"><i class="fa-solid fa-user-plus"></i> Nuevo Cliente</h1><p class="page-subtitle">Agregar cliente motero</p></div></div>
        <?php if (!empty($errores)): ?><div class="alert alert-error"><?php foreach ($errores as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="POST" class="cliente-form">
            <div class="form-section">
                <h3><i class="fa-solid fa-user"></i> Datos Personales</h3>
                <div class="form-row">
                    <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"></div>
                    <div class="form-group"><label>Apellido</label><input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Tipo Documento</label><select name="tipo_doc" class="form-control"><option value="CC" <?= ($_POST['tipo_doc'] ?? 'CC') == 'CC' ? 'selected' : '' ?>>Cédula</option><option value="NIT" <?= ($_POST['tipo_doc'] ?? '') == 'NIT' ? 'selected' : '' ?>>NIT</option><option value="CE" <?= ($_POST['tipo_doc'] ?? '') == 'CE' ? 'selected' : '' ?>>Cédula Extranjería</option><option value="Pasaporte" <?= ($_POST['tipo_doc'] ?? '') == 'Pasaporte' ? 'selected' : '' ?>>Pasaporte</option></select></div>
                    <div class="form-group"><label>Número Documento</label><input type="text" name="numero_doc" class="form-control" value="<?= htmlspecialchars($_POST['numero_doc'] ?? '') ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"></div>
                    <div class="form-group"><label>Teléfono</label><input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"></div>
                </div>
                <div class="form-group"><label>Dirección</label><input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label>Ciudad</label><input type="text" name="ciudad" class="form-control" value="<?= htmlspecialchars($_POST['ciudad'] ?? '') ?>"></div>
                    <div class="form-group"><label>Departamento</label><input type="text" name="departamento" class="form-control" value="<?= htmlspecialchars($_POST['departamento'] ?? '') ?>"></div>
                </div>
            </div>
            <div class="form-actions"><a href="index.php" class="btn btn-outline">Cancelar</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Guardar Cliente</button></div>
        </form>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>