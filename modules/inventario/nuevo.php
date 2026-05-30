<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$stmt = db()->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll();

$stmt = db()->query("SELECT id, nombre FROM marcas WHERE activo = 1 ORDER BY nombre");
$marcas = $stmt->fetchAll();

$errores = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $marca_id = (int)($_POST['marca_id'] ?? 0);
    $precio_compra = (float)($_POST['precio_compra'] ?? 0);
    $precio_venta = (float)($_POST['precio_venta'] ?? 0);
    $impuesto_pct = (float)($_POST['impuesto_pct'] ?? 19);
    $iva_incluido = isset($_POST['iva_incluido']) ? 1 : 0;
    $stock = (int)($_POST['stock'] ?? 0);
    $stock_minimo = (int)($_POST['stock_minimo'] ?? 3);
    $talla = trim($_POST['talla'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $homologacion = trim($_POST['homologacion'] ?? '');
    $nivel_proteccion = trim($_POST['nivel_proteccion'] ?? '');
    $impermeable = isset($_POST['impermeable']) ? 1 : 0;

    if (empty($codigo)) $errores[] = "El código es requerido";
    if (empty($nombre)) $errores[] = "El nombre es requerido";
    if (!$categoria_id) $errores[] = "Seleccione una categoría";
    if (!$marca_id) $errores[] = "Seleccione una marca";
    if ($precio_compra < 0) $errores[] = "El precio de compra no puede ser negativo";
    if ($precio_venta < 0) $errores[] = "El precio de venta no puede ser negativo";

    if (empty($errores)) {
        try {
            $stmt = db()->prepare("
                INSERT INTO productos (codigo, nombre, descripcion, categoria_id, marca_id, precio_compra, precio_venta, impuesto_pct, iva_incluido, stock, stock_minimo, talla, color, homologacion, nivel_proteccion, impermeable)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $codigo, $nombre, $descripcion, $categoria_id, $marca_id,
                $precio_compra, $precio_venta, $impuesto_pct, $iva_incluido,
                $stock, $stock_minimo, $talla, $color, $homologacion, $nivel_proteccion, $impermeable
            ]);

            $producto_id = db()->lastInsertId();

            if (!empty($talla) && $stock > 0) {
                db()->prepare("INSERT INTO producto_tallas (producto_id, talla, stock) VALUES (?, ?, ?)")
                    ->execute([$producto_id, $talla, $stock]);
            }

            $success = true;
            header('Location: index.php?msg=created');
            exit;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $errores[] = "El código del producto ya existe";
            } else {
                $errores[] = "Error al guardar: " . $e->getMessage();
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
    <title>Nuevo Producto - EASYGES</title>
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
                <h1 class="page-title"><i class="fa-solid fa-box"></i> Nuevo Producto</h1>
                <p class="page-subtitle">Agregar producto al inventario</p>
            </div>
        </div>

        <?php if (!empty($errores)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php foreach ($errores as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="product-form-grid">
            <div class="product-form-section">
                <h3><i class="fa-solid fa-info-circle"></i> Información Básica</h3>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Código *</label>
                    <input type="text" name="codigo" class="form-control" required placeholder="ej: PROD-001"
                           value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Nombre del producto"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3" placeholder="Descripción del producto"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="form-row" style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label>Categoría *</label>
                        <select name="categoria_id" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($_POST['categoria_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Marca *</label>
                        <select name="marca_id" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($marcas as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($_POST['marca_id'] ?? '') == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row" style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label>Talla / Tamaño</label>
                        <input type="text" name="talla" class="form-control" placeholder="S, M, L, XL, 42..."
                               value="<?= htmlspecialchars($_POST['talla'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Color</label>
                        <input type="text" name="color" class="form-control" placeholder="Negro, Rojo..."
                               value="<?= htmlspecialchars($_POST['color'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="product-form-section">
                <h3><i class="fa-solid fa-dollar-sign"></i> Precios</h3>
                
                <div class="pricing-box">
                    <h4>Configuración de Precios</h4>
                    
                    <div class="price-row">
                        <label>Precio Compra (sin IVA)</label>
                        <input type="number" name="precio_compra" step="0.01" min="0" class="form-control" required
                               value="<?= htmlspecialchars($_POST['precio_compra'] ?? '') ?>">
                    </div>

                    <div class="price-row">
                        <label>Precio Venta (sin IVA)</label>
                        <input type="number" name="precio_venta" step="0.01" min="0" class="form-control" required
                               value="<?= htmlspecialchars($_POST['precio_venta'] ?? '') ?>">
                    </div>

                    <div class="price-row">
                        <label>Porcentaje IVA (%)</label>
                        <input type="number" name="impuesto_pct" step="0.01" min="0" max="100" class="form-control"
                               value="<?= htmlspecialchars($_POST['impuesto_pct'] ?? '19') ?>">
                    </div>

                    <div class="iva-option">
                        <input type="checkbox" id="iva_incluido" name="iva_incluido" 
                               <?= isset($_POST['iva_incluido']) ? 'checked' : '' ?>
                               onchange="calcularPrecios()">
                        <label for="iva_incluido">
                            <strong>IVA incluido en precio de venta</strong>
                            <small id="iva-hint">Marque esta opción si el precio de venta ya incluye el IVA</small>
                        </label>
                    </div>
                    <div id="iva-status" style="margin-top: .5rem; padding: .5rem; border-radius: 6px; font-size: .85rem; display: none;">
                        <i class="fa-solid fa-info-circle"></i> <span id="iva-status-text"></span>
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <h4>Resumen de Precios</h4>
                    <div style="background: var(--gray-100); padding: 1rem; border-radius: 8px; font-size: .9rem;">
                        <div style="display: flex; justify-content: space-between; padding: .35rem 0;">
                            <span>Precio venta sin IVA:</span>
                            <strong id="resumen-sin-iva">$0</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: .35rem 0;">
                            <span>IVA (19%):</span>
                            <strong id="resumen-iva">$0</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: .35rem 0; border-top: 1px solid var(--gray-300); margin-top: .5rem; padding-top: .75rem;">
                            <span>Precio venta final:</span>
                            <strong id="resumen-total" style="color: var(--teal);">$0</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="product-form-section">
                <h3><i class="fa-solid fa-boxes-stacked"></i> Inventario</h3>
                
                <div class="form-row" style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label>Stock Inicial</label>
                        <input type="number" name="stock" min="0" class="form-control"
                               value="<?= htmlspecialchars($_POST['stock'] ?? '0') ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Stock Mínimo</label>
                        <input type="number" name="stock_minimo" min="0" class="form-control"
                               value="<?= htmlspecialchars($_POST['stock_minimo'] ?? '3') ?>">
                    </div>
                </div>

                <div style="margin-top: 1rem;">
                    <h4>Atributos Moto-Gear</h4>
                    
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Homologación</label>
                        <select name="homologacion" class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="ECE 22.06" <?= ($_POST['homologacion'] ?? '') == 'ECE 22.06' ? 'selected' : '' ?>>ECE 22.06</option>
                            <option value="DOT" <?= ($_POST['homologacion'] ?? '') == 'DOT' ? 'selected' : '' ?>>DOT</option>
                            <option value="SNELL" <?= ($_POST['homologacion'] ?? '') == 'SNELL' ? 'selected' : '' ?>>SNELL</option>
                            <option value="ECE 22.05" <?= ($_POST['homologacion'] ?? '') == 'ECE 22.05' ? 'selected' : '' ?>>ECE 22.05</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label>Nivel Protección</label>
                        <select name="nivel_proteccion" class="form-control">
                            <option value="">Seleccionar...</option>
                            <option value="CE Nivel 1" <?= ($_POST['nivel_proteccion'] ?? '') == 'CE Nivel 1' ? 'selected' : '' ?>>CE Nivel 1</option>
                            <option value="CE Nivel 2" <?= ($_POST['nivel_proteccion'] ?? '') == 'CE Nivel 2' ? 'selected' : '' ?>>CE Nivel 2</option>
                        </select>
                    </div>

                    <div class="iva-option">
                        <input type="checkbox" id="impermeable" name="impermeable" 
                               <?= isset($_POST['impermeable']) ? 'checked' : '' ?>>
                        <label for="impermeable">
                            <strong>Impermeable</strong>
                            <small>Producto resistente al agua</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-actions" style="grid-column: 1 / -1;">
                <a href="index.php" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Guardar Producto
                </button>
            </div>
        </form>
    </div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
}

function calcularPrecios() {
    let precioVenta = parseFloat(document.querySelector('input[name="precio_venta"]').value) || 0;
    let ivaPct = parseFloat(document.querySelector('input[name="impuesto_pct"]').value) || 19;
    let ivaIncluido = document.getElementById('iva_incluido').checked;

    let precioSinIva, iva, precioFinal;

    if (ivaIncluido) {
        precioSinIva = precioVenta / (1 + ivaPct / 100);
        iva = precioVenta - precioSinIva;
        precioFinal = precioVenta;
    } else {
        precioSinIva = precioVenta;
        iva = precioVenta * (ivaPct / 100);
        precioFinal = precioVenta + iva;
    }

    document.getElementById('resumen-sin-iva').textContent = '$' + precioSinIva.toLocaleString('es-CO', {minimumFractionDigits: 0});
    document.getElementById('resumen-iva').textContent = '$' + iva.toLocaleString('es-CO', {minimumFractionDigits: 0});
    document.getElementById('resumen-total').textContent = '$' + precioFinal.toLocaleString('es-CO', {minimumFractionDigits: 0});
    
    // Actualizar estado visual del IVA
    const statusEl = document.getElementById('iva-status');
    const statusText = document.getElementById('iva-status-text');
    const hint = document.getElementById('iva-hint');
    
    if (ivaIncluido) {
        statusEl.style.display = 'block';
        statusEl.style.background = '#dcfce7';
        statusEl.style.color = '#166534';
        statusText.textContent = 'El precio enteredo ya incluye IVA. Se calculará el valor sin IVA.';
    } else {
        statusEl.style.display = 'block';
        statusEl.style.background = '#dbeafe';
        statusEl.style.color = '#1e40af';
        statusText.textContent = 'El IVA se agregará al precio de venta.';
    }
}

document.querySelector('input[name="precio_venta"]').addEventListener('input', calcularPrecios);
document.querySelector('input[name="impuesto_pct"]').addEventListener('input', calcularPrecios);

calcularPrecios();
</script>
</body>
</html>