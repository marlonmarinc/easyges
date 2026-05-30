<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    header('Location: index.php');
    exit;
}

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
        $stmt = db()->prepare("UPDATE clientes SET nombre=?, apellido=?, tipo_doc=?, numero_doc=?, email=?, telefono=?, direccion=?, ciudad=?, departamento=?, tipo_moto=?, estilo_moto=?, talla_casco=?, talla_ropa=? WHERE id=?");
        $stmt->execute([$nombre, $apellido, $tipo_doc, $numero_doc, $email, $telefono, $direccion, $ciudad, $departamento, $tipo_moto, $estilo_moto, $talla_casco, $talla_ropa, $id]);
        header('Location: index.php?msg=updated');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - EASYGES</title>
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
        <div class="page-header"><div><h1 class="page-title"><i class="fa-solid fa-edit"></i> Editar Cliente</h1></div></div>
        <?php if (!empty($errores)): ?><div class="alert alert-error"><?php foreach ($errores as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="POST" class="cliente-form">
            <div class="form-section">
                <h3><i class="fa-solid fa-user"></i> Datos Personales</h3>
                <div class="form-row">
                    <div class="form-group"><label>Nombre *</label><input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($cliente['nombre']) ?>"></div>
                    <div class="form-group"><label>Apellido</label><input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($cliente['apellido'] ?? '') ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Tipo Documento</label><select name="tipo_doc" class="form-control"><option value="CC" <?= $cliente['tipo_doc']=='CC'?'selected':'' ?>>Cédula</option><option value="NIT" <?= $cliente['tipo_doc']=='NIT'?'selected':'' ?>>NIT</option><option value="CE" <?= $cliente['tipo_doc']=='CE'?'selected':'' ?>>Cédula Extranjería</option></select></div>
                    <div class="form-group"><label>Número Documento</label><input type="text" name="numero_doc" class="form-control" value="<?= htmlspecialchars($cliente['numero_doc'] ?? '') ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cliente['email'] ?? '') ?>"></div>
                    <div class="form-group"><label>Teléfono</label><input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>"></div>
                </div>
                <div class="form-group"><label>Dirección</label><input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label>Ciudad</label><input type="text" name="ciudad" class="form-control" value="<?= htmlspecialchars($cliente['ciudad'] ?? '') ?>"></div>
                    <div class="form-group"><label>Departamento</label><input type="text" name="departamento" class="form-control" value="<?= htmlspecialchars($cliente['departamento'] ?? '') ?>"></div>
                </div>
            </div>
            <div class="form-actions"><a href="index.php" class="btn btn-outline">Cancelar</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Guardar Cambios</button></div>
        </form>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>