<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

try {
    db()->query("CREATE TABLE IF NOT EXISTS metodos_pago (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL,
        tipo ENUM('efectivo','tarjeta','transferencia','credito') NOT NULL,
        activo TINYINT(1) DEFAULT 1,
        orden INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $count = db()->query("SELECT COUNT(*) as c FROM metodos_pago")->fetch();
    if ($count['c'] == 0) {
        db()->query("INSERT INTO metodos_pago (nombre, tipo, orden) VALUES 
            ('Efectivo', 'efectivo', 1),
            ('Tarjeta Débito', 'tarjeta', 2),
            ('Tarjeta Crédito', 'tarjeta', 3),
            ('Transferencia', 'transferencia', 4),
            ('Crédito 30 días', 'credito', 5),
            ('Crédito 60 días', 'credito', 6)");
    }
} catch (Exception $e) {}

$msg = '';
if (isset($_POST['guardar_metodos'])) {
    $metodos = $_POST['metodos'] ?? [];
    foreach ($metodos as $id => $data) {
        $nombre = trim($data['nombre'] ?? '');
        $activo = isset($data['activo']) ? 1 : 0;
        if ($nombre) {
            $stmt = db()->prepare("UPDATE metodos_pago SET nombre = ?, activo = ? WHERE id = ?");
            $stmt->execute([$nombre, $activo, $id]);
        }
    }
    $msg = 'Métodos de pago actualizados';
}

if (isset($_POST['nuevo_metodo'])) {
    $nombre = trim($_POST['nuevo_nombre'] ?? '');
    $tipo = $_POST['nuevo_tipo'] ?? 'efectivo';
    if ($nombre) {
        $stmt = db()->query("SELECT MAX(orden) + 1 as sig FROM metodos_pago");
        $orden = $stmt->fetch()['sig'] ?? 1;
        $stmt = db()->prepare("INSERT INTO metodos_pago (nombre, tipo, orden) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $tipo, $orden]);
        $msg = 'Método de pago agregado';
    }
}

if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    db()->query("DELETE FROM metodos_pago WHERE id = $id");
    header('Location: index.php');
    exit;
}

$stmt = db()->query("SELECT * FROM metodos_pago ORDER BY orden");
$metodos = $stmt->fetchAll();
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
        .config-container { max-width: 800px; margin: 0 auto; }
        .config-section { background: white; border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm); }
        .config-section h3 { margin: 0 0 1rem 0; font-size: 1.1rem; color: var(--gray-800); padding-bottom: 0.75rem; border-bottom: 2px solid var(--teal); }
        .method-row { display: flex; gap: 0.5rem; margin-bottom: 0.75rem; align-items: center; }
        .method-row input[type="text"] { flex: 1; padding: 0.6rem; border: 1px solid var(--gray-300); border-radius: 6px; }
        .method-row select { padding: 0.6rem; border: 1px solid var(--gray-300); border-radius: 6px; width: 140px; }
        .method-row input[type="checkbox"] { width: 20px; height: 20px; }
        .method-row .btn-delete { color: var(--red); cursor: pointer; }
        .btn-add { background: var(--teal); color: white; padding: 0.6rem 1.2rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-save { background: var(--teal); color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .alert { background: #d1fae5; color: #065f46; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
    </style>
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-building"></i></div>
        <div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión</span></div>
    </div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
    </nav>
    <div class="sidebar-footer"><a href="../../dashboard.php" class="btn-logout"><i class="fa-solid fa-arrow-left"></i> Volver</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button></header>
    <div class="page-content">
        <div class="page-header"><h1 class="page-title"><i class="fa-solid fa-gear"></i> Configuración</h1></div>
        <div class="config-container">
            <?php if ($msg): ?><div class="alert"><?= $msg ?></div><?php endif; ?>
            <div class="config-section">
                <h3><i class="fa-solid fa-credit-card"></i> Métodos de Pago</h3>
                <form method="POST">
                    <?php foreach ($metodos as $m): ?>
                    <div class="method-row">
                        <input type="text" name="metodos[<?= $m['id'] ?>][nombre]" value="<?= htmlspecialchars($m['nombre']) ?>">
                        <select disabled style="background: var(--gray-100);">
                            <option value="efectivo" <?= $m['tipo']=='efectivo'?'selected':'' ?>>Efectivo</option>
                            <option value="tarjeta" <?= $m['tipo']=='tarjeta'?'selected':'' ?>>Tarjeta</option>
                            <option value="transferencia" <?= $m['tipo']=='transferencia'?'selected':'' ?>>Transferencia</option>
                            <option value="credito" <?= $m['tipo']=='credito'?'selected':'' ?>>Crédito</option>
                        </select>
                        <label title="Activo"><input type="checkbox" name="metodos[<?= $m['id'] ?>][activo]" <?= $m['activo']?'checked':'' ?>></label>
                        <a href="?eliminar=<?= $m['id'] ?>" class="btn-delete" onclick="return confirm('Eliminar?')"><i class="fa-solid fa-trash"></i></a>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" name="guardar_metodos" class="btn-save" style="margin-top:1rem;"><i class="fa-solid fa-save"></i> Guardar Cambios</button>
                </form>
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--gray-200);">
                <h4>Agregar Nuevo Método</h4>
                <form method="POST" style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                    <input type="text" name="nuevo_nombre" placeholder="Nombre (ej: Crédito 30 días)" style="flex:1; padding: 0.6rem; border: 1px solid var(--gray-300); border-radius: 6px;" required>
                    <select name="nuevo_tipo" style="padding: 0.6rem; border: 1px solid var(--gray-300); border-radius: 6px;">
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="credito">Crédito</option>
                    </select>
                    <button type="submit" name="nuevo_metodo" class="btn-add"><i class="fa-solid fa-plus"></i> Agregar</button>
                </form>
            </div>
        </div>
    </div>
</main>
<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>