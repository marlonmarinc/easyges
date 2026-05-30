<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$stmt = db()->query("SELECT id, nombre FROM clientes WHERE activo = 1 ORDER BY nombre");
$clientes = $stmt->fetchAll();

$stmt = db()->query("SELECT id, codigo, nombre, precio_venta, stock, iva_incluido, impuesto_pct FROM productos WHERE activo = 1 AND stock > 0 ORDER BY nombre");
$productos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Factura - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/facturacion.css">
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
            <i class="fa-solid fa-file-invoice"></i><span>Facturación</span>
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
        <div class="topbar-right">
            <span class="topbar-user">
                <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong>
                <small>Nueva Factura</small>
            </span>
        </div>
    </header>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-file-invoice-dollar"></i> Nueva Factura</h1>
                <p class="page-subtitle">Crear factura con detalles completos</p>
            </div>
        </div>

        <form id="factura-form" class="factura-form">
            <div class="factura-section">
                <h3><i class="fa-solid fa-user"></i> Datos del Cliente</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Cliente *</label>
                        <select id="cliente_id" name="cliente_id" class="form-control" required>
                            <option value="">Seleccionar cliente...</option>
                            <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="factura-section">
                <h3><i class="fa-solid fa-file-alt"></i> Datos de la Factura</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha *</label>
                        <input type="date" id="fecha" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Método de Pago *</label>
                        <select id="metodo_pago" name="metodo_pago" class="form-control">
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta">Tarjeta Débito/Crédito</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Nequi">Nequi</option>
                            <option value="Daviplata">Daviplata</option>
                            <option value="Credito">Crédito</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Notas / Observaciones</label>
                    <textarea id="notas" name="notas" class="form-control" rows="2" placeholder="Observaciones adicionales..."></textarea>
                </div>
            </div>

            <div class="factura-section" style="grid-column: 1 / -1;">
                <h3><i class="fa-solid fa-box"></i> Productos</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 40%;">Producto</th>
                            <th style="width: 15%;">Cantidad</th>
                            <th style="width: 20%;">Precio Unit.</th>
                            <th style="width: 10%;">Desc %</th>
                            <th style="width: 15%;">Subtotal</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                    </tbody>
                </table>
                <button type="button" class="btn-add-item" onclick="agregarItem()">
                    <i class="fa-solid fa-plus"></i> Agregar Producto
                </button>

                <div class="total-box">
                    <div class="row">
                        <span>Subtotal:</span>
                        <span id="subtotal">$0</span>
                    </div>
                    <div class="row">
                        <span>Descuento:</span>
                        <span id="descuento">$0</span>
                    </div>
                    <div class="row">
                        <span>IVA (19%):</span>
                        <span id="impuesto">$0</span>
                    </div>
                    <div class="row grand-total">
                        <span>Total:</span>
                        <span id="total">$0</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="index.php" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-teal">
                    <i class="fa-solid fa-save"></i> Guardar Factura
                </button>
            </div>
        </form>
    </div>
</main>

<div id="productos-data" style="display: none;"><?= json_encode($productos) ?></div>

<script>
const productos = JSON.parse(document.getElementById('productos-data').textContent);
let items = [];

function agregarItem() {
    const id = Date.now();
    const selectHtml = `
        <select class="item-producto form-control" onchange="actualizarProducto(${id}, this.value)">
            <option value="">Seleccionar...</option>
            ${productos.map(p => `<option value="${p.id}" data-precio="${p.precio_venta}" data-stock="${p.stock}" data-iva-incluido="${p.iva_incluido}" data-impuesto-pct="${p.impuesto_pct}">${p.nombre} ($${Number(p.precio_venta).toLocaleString()}) - Stock: ${p.stock}</option>`).join('')}
        </select>`;
    
    const row = document.createElement('tr');
    row.id = 'item-' + id;
    row.innerHTML = `
        <td>${selectHtml}</td>
        <td><input type="number" class="item-cantidad form-control" value="1" min="1" onchange="calcularTotales()"></td>
        <td><input type="number" class="item-precio form-control" value="0" min="0" onchange="calcularTotales()"></td>
        <td><input type="number" class="item-desc form-control" value="0" min="0" max="100" onchange="calcularTotales()"></td>
        <td class="item-subtotal">$0</td>
        <td><button type="button" class="btn-icon btn-danger" onclick="eliminarItem(${id})"><i class="fa-solid fa-times"></i></button></td>
    `;
    document.getElementById('items-body').appendChild(row);
}

function actualizarProducto(rowId, productoId) {
    if (!productoId) return;
    const row = document.getElementById('item-' + rowId);
    const select = row.querySelector('.item-producto');
    const option = select.options[select.selectedIndex];
    const precio = parseFloat(option.dataset.precio) || 0;
    const ivaIncluido = option.dataset.ivaIncluido === '1';
    const impuestoPct = parseFloat(option.dataset.impuestoPct) || 19;
    
    row.dataset.ivaIncluido = ivaIncluido;
    row.dataset.impuestoPct = impuestoPct;
    row.dataset.precioBase = precio;
    row.querySelector('.item-precio').value = precio;
    calcularTotales();
}

function eliminarItem(id) {
    document.getElementById('item-' + id).remove();
    calcularTotales();
}

function calcularTotales() {
    let subtotalSinIva = 0;
    let descuento = 0;
    let ivaTotal = 0;

    document.querySelectorAll('#items-body tr').forEach(row => {
        const cantidad = parseFloat(row.querySelector('.item-cantidad').value) || 0;
        const precioBase = parseFloat(row.dataset.precioBase) || 0;
        const descPct = parseFloat(row.querySelector('.item-desc').value) || 0;
        const ivaIncluido = row.dataset.ivaIncluido === 'true';
        const impuestoPct = parseFloat(row.dataset.impuestoPct) || 19;
        
        // Calcular precio sin IVA y IVA
        let precioSinIva, ivaItem;
        if (ivaIncluido) {
            precioSinIva = precioBase / (1 + impuestoPct / 100);
            ivaItem = precioBase - precioSinIva;
        } else {
            precioSinIva = precioBase;
            ivaItem = precioBase * (impuestoPct / 100);
        }
        
        const itemSubtotal = cantidad * precioSinIva;
        const itemDesc = itemSubtotal * (descPct / 100);
        const itemIva = ivaItem * cantidad;
        
        row.querySelector('.item-subtotal').textContent = '$' + (itemSubtotal - itemDesc + itemIva).toLocaleString();
        
        subtotalSinIva += itemSubtotal;
        descuento += itemDesc;
        ivaTotal += itemIva;
    });

    const total = subtotalSinIva - descuento + ivaTotal;

    document.getElementById('subtotal').textContent = '$' + subtotalSinIva.toLocaleString();
    document.getElementById('descuento').textContent = '$' + descuento.toLocaleString();
    document.getElementById('impuesto').textContent = '$' + ivaTotal.toLocaleString();
    document.getElementById('total').textContent = '$' + total.toLocaleString();
}

document.getElementById('factura-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const clienteId = document.getElementById('cliente_id').value;
    if (!clienteId) {
        alert('Seleccione un cliente');
        return;
    }

    items = [];
    document.querySelectorAll('#items-body tr').forEach(row => {
        const productoId = row.querySelector('.item-producto').value;
        if (productoId) {
            items.push({
                producto_id: parseInt(productoId),
                cantidad: parseInt(row.querySelector('.item-cantidad').value),
                precio_unitario: parseFloat(row.querySelector('.item-precio').value),
                descuento_pct: parseFloat(row.querySelector('.item-desc').value)
            });
        }
    });

    if (items.length === 0) {
        alert('Agregue al menos un producto');
        return;
    }

    const data = {
        cliente_id: clienteId,
        fecha: document.getElementById('fecha').value,
        metodo_pago: document.getElementById('metodo_pago').value,
        notas: document.getElementById('notas').value,
        items: items
    };

    fetch('ajax/guardar_factura.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            window.location.href = 'ver.php?id=' + d.factura_id;
        } else {
            alert(d.error || 'Error al guardar');
        }
    });
});

agregarItem();
</script>
</body>
</html>