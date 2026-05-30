<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$producto_id = (int)($_GET['id'] ?? 0);

$stmt = db()->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$producto_id]);
$producto = $stmt->fetch();

if (!$producto) {
    header('Location: index.php');
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $cantidad = (int)($_POST['cantidad'] ?? 0);
    $notas = trim($_POST['notas'] ?? '');

    if (!in_array($tipo, ['entrada', 'salida', 'ajuste'])) {
        $error = 'Tipo de movimiento inválido';
    } elseif ($cantidad <= 0) {
        $error = 'La cantidad debe ser mayor a 0';
    } elseif ($tipo === 'salida' && $cantidad > $producto['stock']) {
        $error = 'Stock insuficiente. Stock actual: ' . $producto['stock'];
    } else {
        try {
            db()->beginTransaction();

            $stock_anterior = $producto['stock'];
            
            if ($tipo === 'entrada') {
                $stock_nuevo = $stock_anterior + $cantidad;
            } elseif ($tipo === 'salida') {
                $stock_nuevo = $stock_anterior - $cantidad;
            } else {
                $stock_nuevo = $cantidad;
            }

            db()->prepare("UPDATE productos SET stock = ? WHERE id = ?")
                ->execute([$stock_nuevo, $producto_id]);

            db()->prepare("
                INSERT INTO movimientos_inventario (producto_id, tipo, cantidad, stock_anterior, stock_nuevo, notas, usuario_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([$producto_id, $tipo, $cantidad, $stock_anterior, $stock_nuevo, $notas, $_SESSION['usuario_id']]);

            db()->commit();
            $success = true;
            
            header('Location: index.php?msg=stock_updated');
            exit;
        } catch (Exception $e) {
            db()->rollBack();
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuste Stock - EASYGES</title>
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
                <h1 class="page-title"><i class="fa-solid fa-plus-minus"></i> Ajuste de Stock</h1>
                <p class="page-subtitle"><?= htmlspecialchars($producto['nombre']) ?></p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="factura-section">
                <h3>Información del Producto</h3>
                
                <div style="background: var(--gray-50); padding: 1.25rem; border-radius: 10px;">
                    <table style="width: 100%; font-size: .9rem;">
                        <tr>
                            <td style="color: var(--gray-500);">Código</td>
                            <td><strong><?= htmlspecialchars($producto['codigo']) ?></strong></td>
                        </tr>
                        <tr>
                            <td style="color: var(--gray-500);">Nombre</td>
                            <td><?= htmlspecialchars($producto['nombre']) ?></td>
                        </tr>
                        <tr>
                            <td style="color: var(--gray-500);">Categoría</td>
                            <td><?= htmlspecialchars($producto['categoria_id']) ?></td>
                        </tr>
                        <tr>
                            <td style="color: var(--gray-500);">Stock Actual</td>
                            <td>
                                <span style="font-size: 1.5rem; font-weight: 700; color: var(--teal);">
                                    <?= $producto['stock'] ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="color: var(--gray-500);">Stock Mínimo</td>
                            <td><?= $producto['stock_minimo'] ?></td>
                        </tr>
                    </table>
                </div>

                <h3 style="margin-top: 1.5rem;">Historial Reciente</h3>
                <?php
                $stmt = db()->prepare("
                    SELECT * FROM movimientos_inventario 
                    WHERE producto_id = ? 
                    ORDER BY created_at DESC LIMIT 5
                ");
                $stmt->execute([$producto_id]);
                $historial = $stmt->fetchAll();
                ?>
                <?php if (empty($historial)): ?>
                <p style="color: var(--gray-400);">No hay movimientos registrados</p>
                <?php else: ?>
                <table class="data-table" style="font-size: .85rem;">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Cant.</th>
                            <th>Stock Nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $h): ?>
                        <tr>
                            <td><?= date('d/m H:i', strtotime($h['created_at'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $h['tipo'] === 'entrada' ? 'pagada' : ($h['tipo'] === 'salida' ? 'pendiente' : 'anulada') ?>">
                                    <?= $h['tipo'] ?>
                                </span>
                            </td>
                            <td><?= $h['cantidad'] ?></td>
                            <td><?= $h['stock_nuevo'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <div class="factura-section">
                <h3>Nuevo Movimiento</h3>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Tipo de Movimiento</label>
                        <div style="display: flex; gap: .75rem;">
                            <label style="flex: 1; cursor: pointer;">
                                <input type="radio" name="tipo" value="entrada" checked style="margin-right: .5rem;">
                                <span style="display: inline-block; padding: .75rem 1rem; background: #dcfce7; border-radius: 8px; width: 100%; text-align: center; color: #166534; font-weight: 600;">
                                    <i class="fa-solid fa-plus"></i> Entrada
                                </span>
                            </label>
                            <label style="flex: 1; cursor: pointer;">
                                <input type="radio" name="tipo" value="salida" style="margin-right: .5rem;">
                                <span style="display: inline-block; padding: .75rem 1rem; background: #fee2e2; border-radius: 8px; width: 100%; text-align: center; color: #991b1b; font-weight: 600;">
                                    <i class="fa-solid fa-minus"></i> Salida
                                </span>
                            </label>
                            <label style="flex: 1; cursor: pointer;">
                                <input type="radio" name="tipo" value="ajuste" style="margin-right: .5rem;">
                                <span style="display: inline-block; padding: .75rem 1rem; background: #dbeafe; border-radius: 8px; width: 100%; text-align: center; color: #1e40af; font-weight: 600;">
                                    <i class="fa-solid fa-pen"></i> Ajuste
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Cantidad</label>
                        <input type="number" name="cantidad" min="1" class="form-control" required 
                               placeholder="Cantidad a agregar/quitar">
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label>Notas / Motivo</label>
                        <textarea name="notas" class="form-control" rows="3" 
                                  placeholder="Ej: Compra de reposición, Ajuste de inventario..."></textarea>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <a href="index.php" class="btn btn-outline">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save"></i> Guardar Movimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
}
</script>
</body>
</html>