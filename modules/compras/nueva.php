<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$stmt = db()->query("SELECT id, nombre, nit FROM proveedores WHERE activo = 1 ORDER BY nombre");
$proveedores = $stmt->fetchAll();

$stmt = db()->query("SELECT id, codigo, nombre, stock, precio_compra FROM productos WHERE activo = 1 ORDER BY nombre");
$productos = $stmt->fetchAll();

if (empty($proveedores)) {
    echo "<script>alert('No hay proveedores registrados. Primero cree un proveedor en la base de datos.'); window.location.href='index.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Compra - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/compras.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .compra-container { max-width: 900px; margin: 0 auto; }
        .form-section{background:white;border-radius:var(--radius);padding:1.5rem;box-shadow:var(--shadow-sm);margin-bottom:1.5rem;}
        .form-section h3{margin:0 0 1rem 0;font-size:1.1rem;color:var(--gray-800);padding-bottom:.75rem;border-bottom:2px solid var(--teal-light);}
        .form-row{display:flex;gap:1rem;margin-bottom:1rem;}
        .form-group{flex:1;}
        .form-group label{display:block;font-weight:600;font-size:.85rem;margin-bottom:.5rem;color:var(--gray-700);}
        .form-control{width:100%;padding:.75rem;border:2px solid var(--gray-200);border-radius:var(--radius-sm);font-size:.9rem;}
        .form-control:focus{border-color:var(--teal);outline:none;}
        .producto-item{display:flex;gap:0.5rem;margin-bottom:1rem;align-items:center;flex-wrap:nowrap;}
        .producto-item select{flex:2;min-width:180px;}
        .producto-item input{width:80px;}
        .producto-item .precio-compra{width:100px;}
        .producto-item .precio-venta{width:100px;}
        .producto-item .iva-select{width:90px;}
        .producto-item label{font-size:0.75rem;color:var(--gray-600);}
        .btn-add-item{margin-top:1rem;width:100%;padding:1rem;background:var(--teal-light);border:2px dashed var(--teal);border-radius:var(--radius-sm);color:var(--teal-dark);font-weight:600;cursor:pointer;}
        .btn-add-item:hover{background:var(--teal);color:white;}
        .total-box{background:var(--sidebar-bg);border-radius:var(--radius);padding:1.5rem;color:white;text-align:right;margin-top:1.5rem;}
        .total-box .row{justify-content:space-between;padding:.5rem 0;font-size:.95rem;}
        .total-box .row span:first-child{color:rgba(255,255,255,.7);}
        .total-box .grand-total{font-size:1.5rem;font-weight:700;border-top:2px solid rgba(255,255,255,.2);margin-top:.75rem;padding-top:.75rem;color:var(--teal-light);}
        .btn-save{background:var(--teal);color:white;padding:.75rem 2rem;border:none;border-radius:var(--radius-sm);font-weight:600;cursor:pointer;}
        .btn-save:hover{background:var(--teal-dark);}
        .btn-icon{width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:6px;border:none;background:var(--gray-100);color:var(--gray-600);cursor:pointer;}
        .btn-icon.btn-danger:hover{background:#fee2e2;color:var(--red);}
    </style>
</head>
<body class="erp-body">
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-building"></i></div>
        <div class="sidebar-brand"><span class="brand-name">EASYGES</span><span class="brand-sub">Gestión de Ventas</span></div>
    </div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
        <a href="index.php" class="nav-item active"><i class="fa-solid fa-cart-shopping"></i><span>Compras</span></a>
    </nav>
    <div class="sidebar-footer"><a href="index.php" class="btn-logout"><i class="fa-solid fa-arrow-left"></i> Volver</a></div>
</aside>
<main class="main-content">
    <header class="topbar"><button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button></header>
    <div class="page-content">
        <div class="page-header">
            <div><h1 class="page-title"><i class="fa-solid fa-shopping-cart"></i> Nueva Orden de Compra</h1><p class="page-subtitle">Registrar compra a proveedor</p></div>
        </div>
        
        <div class="compra-container">
            <form id="compra-form" onsubmit="guardarCompra(event)">
                <div class="form-section">
                    <h3><i class="fa-solid fa-truck"></i> Datos de la Compra</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Proveedor *</label>
                            <select name="proveedor_id" id="proveedor_id" class="form-control" required>
                                <option value="">Seleccionar proveedor...</option>
                                <?php foreach ($proveedores as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['nit']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Fecha de Entrega</label>
                            <input type="date" name="fecha_entrega" id="fecha_entrega" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notas / Observaciones</label>
                        <textarea name="notas" id="notas" class="form-control" rows="2" placeholder="Observaciones adicionales..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fa-solid fa-box"></i> Productos</h3>
                    <div style="display:flex;gap:0.5rem;margin-bottom:1rem;font-weight:600;font-size:0.85rem;color:var(--gray-700);padding:0 0.5rem;">
                        <span style="flex:2;">Producto</span>
                        <span style="width:80px;">Cant.</span>
                        <span style="width:100px;">Costo</span>
                        <span style="width:80px;">IVA</span>
                        <span style="width:100px;">Venta</span>
                        <span style="width:40px;"></span>
                    </div>
                    <div id="items-container">
                        <div class="producto-item">
                            <select class="form-control producto-select" onchange="actualizarPrecio(this)">
                                <option value="">Seleccionar producto...</option>
                                <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio_compra'] ?? 0 ?>" data-iva="<?= $p['impuesto_pct'] ?? 19 ?>" data-venta="<?= $p['precio_venta'] ?? 0 ?>"><?= htmlspecialchars($p['nombre']) ?> - <?= htmlspecialchars($p['codigo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" class="form-control cantidad" value="1" min="1" placeholder="1" onchange="calcularTotal()">
                            <input type="number" class="form-control precio-compra" value="0" step="0.01" placeholder="$0" onchange="calcularTotal()">
                            <select class="form-control iva-select" onchange="calcularTotal()">
                                <option value="0">No</option>
                                <option value="19">Sí (19%)</option>
                            </select>
                            <input type="number" class="form-control precio-venta" value="0" step="0.01" placeholder="$0">
                            <button type="button" class="btn-icon btn-danger" onclick="eliminarProducto(this)"><i class="fa-solid fa-times"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn-add-item" onclick="agregarProducto()">
                        <i class="fa-solid fa-plus"></i> Agregar Producto
                    </button>

                    <div class="total-box">
                        <div class="row"><span>Subtotal:</span><span id="subtotal">$0</span></div>
                        <div class="row"><span>IVA (19%):</span><span id="iva">$0</span></div>
                        <div class="row grand-total"><span>TOTAL:</span><span id="total">$0</span></div>
                    </div>
                </div>

                <div style="display:flex;gap:1rem;justify-content:flex-end;">
                    <a href="index.php" class="btn btn-outline" style="padding:.75rem 1.5rem;text-decoration:none;border-radius:8px;border:1px solid var(--gray-300);color:var(--gray-600);">Cancelar</a>
                    <button type="submit" class="btn-save"><i class="fa-solid fa-save"></i> Guardar Orden de Compra</button>
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

function agregarProducto() {
    const container = document.getElementById('items-container');
    const html = `
        <div class="producto-item">
            <select class="form-control producto-select" onchange="actualizarPrecio(this)">
                <option value="">Seleccionar producto...</option>
                <?php foreach ($productos as $p): ?>
                <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio_compra'] ?? 0 ?>" data-iva="<?= $p['impuesto_pct'] ?? 19 ?>" data-venta="<?= $p['precio_venta'] ?? 0 ?>"><?= htmlspecialchars($p['nombre']) ?> - <?= htmlspecialchars($p['codigo']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" class="form-control cantidad" value="1" min="1" placeholder="1" onchange="calcularTotal()">
            <input type="number" class="form-control precio-compra" value="0" step="0.01" placeholder="$0" onchange="calcularTotal()">
            <select class="form-control iva-select" onchange="calcularTotal()">
                <option value="0">No</option>
                <option value="19">Sí (19%)</option>
            </select>
            <input type="number" class="form-control precio-venta" value="0" step="0.01" placeholder="$0">
            <button type="button" class="btn-icon btn-danger" onclick="eliminarProducto(this)"><i class="fa-solid fa-times"></i></button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function eliminarProducto(btn) {
    const container = document.getElementById('items-container');
    if (container.children.length > 1) {
        btn.parentElement.remove();
        calcularTotal();
    } else {
        alert('Debe tener al menos un producto');
    }
}

function actualizarPrecio(select) {
    const row = select.parentElement;
    const option = select.options[select.selectedIndex];
    row.querySelector('.precio-compra').value = option.dataset.precio || 0;
    row.querySelector('.iva-select').value = option.dataset.iva || 19;
    row.querySelector('.precio-venta').value = option.dataset.venta || 0;
    calcularTotal();
}

function calcularTotal() {
    let subtotal = 0;
    let totalIva = 0;
    document.querySelectorAll('.producto-item').forEach(row => {
        const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
        const precio = parseFloat(row.querySelector('.precio-compra').value) || 0;
        const ivaPct = parseFloat(row.querySelector('.iva-select').value) || 0;
        const lineaSubtotal = cantidad * precio;
        subtotal += lineaSubtotal;
        totalIva += lineaSubtotal * (ivaPct / 100);
    });
    
    const total = subtotal + totalIva;
    
    document.getElementById('subtotal').textContent = '$' + Math.round(subtotal).toLocaleString();
    document.getElementById('iva').textContent = '$' + Math.round(totalIva).toLocaleString();
    document.getElementById('total').textContent = '$' + Math.round(total).toLocaleString();
}

function guardarCompra(e) {
    e.preventDefault();
    
    const proveedorId = document.getElementById('proveedor_id').value;
    if (!proveedorId) {
        alert('Seleccione un proveedor');
        return;
    }

    const items = [];
    document.querySelectorAll('.producto-item').forEach(row => {
        const productoId = row.querySelector('.producto-select').value;
        const cantidad = parseInt(row.querySelector('.cantidad').value) || 0;
        const precio = parseFloat(row.querySelector('.precio-compra').value) || 0;
        const ivaPct = parseFloat(row.querySelector('.iva-select').value) || 0;
        const precioVenta = parseFloat(row.querySelector('.precio-venta').value) || 0;
        
        if (productoId && cantidad > 0) {
            items.push({ 
                producto_id: parseInt(productoId), 
                cantidad, 
                precio,
                iva_pct: ivaPct,
                precio_venta: precioVenta
            });
        }
    });

    if (items.length === 0) {
        alert('Agregue al menos un producto');
        return;
    }

    const data = {
        proveedor_id: parseInt(proveedorId),
        fecha_entrega: document.getElementById('fecha_entrega').value,
        notas: document.getElementById('notas').value,
        items: items
    };

    fetch('ajax/guardar_compra.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            alert('Orden de compra guardada: ' + d.numero);
            window.location.href = 'ver.php?id=' + d.compra_id;
        } else {
            alert(d.error || 'Error al guardar');
        }
    })
    .catch(err => {
        alert('Error de conexión: ' + err.message);
    });
}
</script>
</body>
</html>