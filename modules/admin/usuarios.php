<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    $nombre = trim($_POST['nombre']);
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];
    $rol = $_POST['rol'];

    if (!$nombre || !$usuario) {
        $error = 'Nombre y usuario son requeridos';
    } elseif ($password && strlen($password) < 4) {
        $error = 'La contraseña debe tener al menos 4 caracteres';
    } else {
        if (isset($_POST['id']) && $_POST['id']) {
            // Editar
            if ($password) {
                $stmt = db()->prepare("UPDATE usuarios SET nombre=?, usuario=?, password=?, rol=? WHERE id=?");
                $stmt->execute([$nombre, $usuario, $password, $rol, $_POST['id']]);
            } else {
                $stmt = db()->prepare("UPDATE usuarios SET nombre=?, usuario=?, rol=? WHERE id=?");
                $stmt->execute([$nombre, $usuario, $rol, $_POST['id']]);
            }
            $msg = 'Usuario actualizado';
        } else {
            // Nuevo
            if (!$password) {
                $error = 'La contraseña es requerida';
            } else {
                $stmt = db()->prepare("INSERT INTO usuarios (nombre, usuario, password, rol) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nombre, $usuario, $password, $rol]);
                $msg = 'Usuario creado';
            }
        }
    }
}

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    if ($id != 1) {
        db()->query("DELETE FROM usuarios WHERE id = $id");
    }
    header('Location: usuarios.php');
    exit;
}

$usuarios = db()->query("SELECT * FROM usuarios ORDER BY nombre")->fetchAll();
$editar = null;
if (isset($_GET['editar'])) {
    $stmt = db()->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $editar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .users-container { max-width: 900px; margin: 0 auto; }
        .card { background: white; border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); }
        .card h3 { margin: 0 0 1rem 0; font-size: 1.1rem; color: var(--gray-800); padding-bottom: .75rem; border-bottom: 2px solid var(--teal); }
        .form-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-weight: 600; font-size: .85rem; margin-bottom: .35rem; color: var(--gray-700); }
        .form-control { width: 100%; padding: .75rem; border: 1px solid var(--gray-300); border-radius: var(--radius-sm); font-size: .9rem; box-sizing:border-box; }
        .form-control:focus { outline: none; border-color: var(--teal); }
        .btn-primary { background: var(--teal); color: white; padding: .75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-danger { background: var(--red); color: white; padding: .4rem .8rem; border: none; border-radius: 4px; cursor: pointer; font-size: .8rem; text-decoration: none; }
        .btn-edit { background: var(--teal); color: white; padding: .4rem .8rem; border-radius: 4px; text-decoration: none; font-size: .8rem; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { background: var(--gray-100); text-align: left; padding: .75rem; font-weight: 600; border-bottom: 2px solid var(--gray-300); }
        .table td { padding: .75rem; border-bottom: 1px solid var(--gray-200); }
        .badge { padding: .25rem .5rem; border-radius: 4px; font-size: .75rem; font-weight: 600; background: var(--teal-light); color: var(--teal-dark); }
        .alert { background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><div class="sidebar-logo"><i class="fa-solid fa-building"></i></div><div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión</span></div></div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
        <a href="index.php" class="nav-item"><i class="fa-solid fa-gear"></i><span>Empresa</span></a>
        <a href="usuarios.php" class="nav-item active"><i class="fa-solid fa-users-gear"></i><span>Usuarios</span></a>
    </nav>
    <div class="sidebar-footer"><a href="../../dashboard.php" class="btn-logout"><i class="fa-solid fa-arrow-left"></i> Volver</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button></header>
    <div class="page-content">
        <div class="page-header"><h1 class="page-title"><i class="fa-solid fa-users-gear"></i> Usuarios del Sistema</h1></div>
        <div class="users-container">
            <?php if ($msg): ?><div class="alert"><?= $msg ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
            
            <div class="card">
                <h3><i class="fa-solid fa-<?= $editar ? 'edit' : 'plus' ?>"></i> <?= $editar ? 'Editar Usuario' : 'Nuevo Usuario' ?></h3>
                <form method="POST">
                    <?php if ($editar): ?>
                    <input type="hidden" name="id" value="<?= $editar['id'] ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($editar['nombre'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Usuario</label>
                            <input type="text" name="usuario" class="form-control" value="<?= htmlspecialchars($editar['usuario'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Contraseña <?= $editar ? '(dejar vacío para no cambiar)' : '' ?></label>
                            <input type="text" name="password" class="form-control" placeholder="<?= $editar ? 'Nueva contraseña...' : 'Contraseña' ?>" <?= $editar ? '' : 'required' ?>>
                        </div>
                        <div class="form-group">
                            <label>Rol</label>
                            <select name="rol" class="form-control" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach (['admin','facturacion','inventario','compras','vendedor','contabilidad','cajero'] as $r): ?>
                                <option value="<?= $r ?>" <?= ($editar['rol'] ?? '') == $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="guardar" class="btn-primary"><i class="fa-solid fa-save"></i> <?= $editar ? 'Actualizar' : 'Crear Usuario' ?></button>
                    <?php if ($editar): ?>
                    <a href="usuarios.php" class="btn-primary" style="background:var(--gray-400);text-decoration:none;margin-left:.5rem;">Cancelar</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <h3><i class="fa-solid fa-list"></i> Usuarios Registrados</h3>
                <table class="table">
                    <thead><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Último Login</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['usuario']) ?></td>
                        <td><?= htmlspecialchars($u['nombre']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($u['rol']) ?></span></td>
                        <td><?= $u['ultimo_login'] ? date('d/m/Y H:i', strtotime($u['ultimo_login'])) : '-' ?></td>
                        <td>
                            <a href="?editar=<?= $u['id'] ?>" class="btn-edit"><i class="fa-solid fa-edit"></i></a>
                            <?php if ($u['id'] != 1): ?>
                            <a href="?eliminar=<?= $u['id'] ?>" class="btn-danger" onclick="return confirm('Eliminar usuario?')"><i class="fa-solid fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>