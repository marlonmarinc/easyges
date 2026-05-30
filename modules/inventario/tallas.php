<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tallas'])) {
    $tallas = $_POST['tallas'] ?? [];
    
    db()->beginTransaction();
    
    $stmt = db()->prepare("DELETE FROM producto_tallas WHERE producto_id = ?");
    $stmt->execute([$id]);
    
    $stmt = db()->prepare("INSERT INTO producto_tallas (producto_id, talla, stock) VALUES (?, ?, ?)");
    foreach ($tallas as $talla => $stock) {
        if (!empty($talla) && $stock > 0) {
            $stmt->execute([$id, $talla, (int)$stock]);
        }
    }
    
    $stmt = db()->query("SELECT COALESCE(SUM(stock),0) as total FROM producto_tallas WHERE producto_id = $id");
    $totalStock = $stmt->fetchColumn();
    
    db()->prepare("UPDATE productos SET stock = ? WHERE id = ?")->execute([$totalStock, $id]);
    
    db()->commit();
    
    header('Location: index.php?msg=tallas_updated');
    exit;
}

$stmt = db()->prepare("SELECT * FROM producto_tallas WHERE producto_id = ?");
$stmt->execute([$id]);
$tallas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tallas - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/inventario.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-building"></i></div>
        <div class="sidebar-brand">
            <span class="brand-name">EASYGES</span><span class="brand-sub">Gestión de Ventas</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item">
            <i class="fa-solid fa-table-columns"></i><span>Dashboard</span>
        </a>
        <a href="index.php" class="nav-item active">
            <i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="index.php" class="btn-logout">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-ruler"></i> Gestión de Tallas</h1>
                <p class="page-subtitle"><?= htmlspecialchars($producto['nombre']) ?></p>
            </div>
        </div>

        <div class="factura-section" style="max-width: 600px;">
            <div style="background: var(--gray-50); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <strong>Producto:</strong> <?= htmlspecialchars($producto['nombre']) ?><br>
                <strong>Código:</strong> <?= htmlspecialchars($producto['codigo']) ?><br>
                <strong>Stock actual:</strong> <?= $producto['stock'] ?> unidades
            </div>

            <form method="POST">
                <h3 style="margin-bottom: 1rem;">Tallas y Stock</h3>
                
                <div id="tallas-container">
                    <?php if (empty($tallas)): ?>
                    <div class="talla-item">
                        <input type="text" name="tallas[0]" placeholder="Talla" class="form-control" style="width: 100px;">
                        <input type="number" name="stock[0]" placeholder="Stock" min="0" value="0" class="form-control">
                    </div>
                    <?php else: ?>
                    <?php foreach ($tallas as $i => $t): ?>
                    <div class="talla-item">
                        <input type="text" name="tallas[<?= $i ?>]" value="<?= htmlspecialchars($t['talla']) ?>" class="form-control" style="width: 100px;">
                        <input type="number" name="stock[<?= $i ?>]" value="<?= $t['stock'] ?>" min="0" class="form-control">
                        <button type="button" onclick="this.parentElement.remove()" class="btn-icon btn-danger">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button type="button" onclick="agregarTalla()" class="btn btn-outline" style="margin-top: 1rem;">
                    <i class="fa-solid fa-plus"></i> Agregar Talla
                </button>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <a href="index.php" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Guardar Tallas
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
}

let contador = <?= count($tallas) ?>;

function agregarTalla() {
    const container = document.getElementById('tallas-container');
    const div = document.createElement('div');
    div.className = 'talla-item';
    div.innerHTML = `
        <input type="text" name="tallas[${contador}]" placeholder="Talla" class="form-control" style="width: 100px;">
        <input type="number" name="stock[${contador}]" placeholder="Stock" min="0" value="0" class="form-control">
        <button type="button" onclick="this.parentElement.remove()" class="btn-icon btn-danger">
            <i class="fa-solid fa-times"></i>
        </button>
    `;
    container.appendChild(div);
    contador++;
}
</script>
</body>
</html>