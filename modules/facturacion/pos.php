<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

try {
    db()->query("ALTER TABLE productos ADD COLUMN iva_incluido TINYINT(1) DEFAULT 1");
} catch (Exception $e) {}

$stmt = db()->query("SELECT id, codigo, nombre, precio_venta, stock, IFNULL(iva_incluido,1) as iva_incluido, IFNULL(impuesto_pct,19) as impuesto_pct FROM productos WHERE activo = 1 AND stock > 0 ORDER BY nombre LIMIT 50");
$productos = $stmt->fetchAll();

$stmt = db()->query("SELECT * FROM clientes WHERE activo = 1 ORDER BY nombre LIMIT 20");
$clientes = $stmt->fetchAll();

$stmt = db()->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - Punto de Venta - EASYGES</title>
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
                <small>POS - Punto de Venta</small>
            </span>
        </div>
    </header>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title"><i class="fa-solid fa-cash-register"></i> Punto de Venta</h1>
                <p class="page-subtitle">Venta rápida en mostrador</p>
            </div>
        </div>

        <div class="pos-layout">
            <div class="pos-products">
                <div class="pos-products-header">
                    <input type="text" id="buscador" placeholder="Buscar producto por nombre o código..." onkeyup="buscarProductos()">
                    <select id="filtro-categoria" onchange="filtrarCategoria()">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="products-grid" id="products-grid">
                    <?php foreach ($productos as $p): ?>
                    <div class="product-card" onclick="agregarProducto(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre']) ?>', <?= $p['precio_venta'] ?>, <?= $p['stock'] ?>, <?= $p['iva_incluido'] ?? 1 ?>, <?= $p['impuesto_pct'] ?? 19 ?>)">
                        <div class="product-name"><?= htmlspecialchars($p['nombre']) ?></div>
                        <div class="product-price">$<?= number_format($p['precio_venta'], 0) ?></div>
                        <div class="product-stock">Stock: <?= $p['stock'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pos-cart">
                <div class="cart-header">
                    <h3><i class="fa-solid fa-cart-shopping"></i> Carrito</h3>
                    <button class="btn btn-outline" onclick="vaciarCarrito()" style="padding: .35rem .75rem; font-size: .8rem;">
                        <i class="fa-solid fa-trash"></i> Limpiar
                    </button>
                </div>
                
                <div class="cart-items" id="cart-items">
                    <div class="cart-empty">
                        <i class="fa-solid fa-basket"></i>
                        <p>Agregue productos del catálogo</p>
                    </div>
                </div>

                <div class="cart-footer">
                    <div class="cart-totals">
                        <div class="cart-total-row">
                            <span>Subtotal</span>
                            <span id="cart-subtotal">$0</span>
                        </div>
                        <div class="cart-total-row">
                            <span>IVA (19%)</span>
                            <span id="cart-iva">$0</span>
                        </div>
                        <div class="cart-total-row grand-total">
                            <span>Total</span>
                            <span id="cart-total">$0</span>
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom: .75rem;">
                        <div class="form-group" style="flex: 2;">
                            <label>Cliente (opcional)</label>
                            <select id="cliente-select" class="form-control">
                                <option value="">Cliente mostrador</option>
                                <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row" style="margin-bottom: 1rem;">
                        <div class="form-group">
                            <label>Método de pago</label>
                            <select id="metodo-pago" class="form-control">
                                <option value="Efectivo">Efectivo</option>
                                <option value="Tarjeta">Tarjeta Débito/Crédito</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Nequi">Nequi</option>
                                <option value="Daviplata">Daviplata</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Recibo de</label>
                            <input type="number" id="monto-recibido" class="form-control" placeholder="0" onkeyup="calcularCambio()">
                        </div>
                    </div>

                    <div class="cart-total-row" id="row-cambio" style="display: none;">
                        <span>Cambio</span>
                        <span id="cart-cambio" style="color: var(--green); font-weight: 600;">$0</span>
                    </div>

                    <div class="cart-actions">
                        <button class="btn btn-teal" onclick="cobrar(false)">
                            <i class="fa-solid fa-save"></i> Guardar
                        </button>
                        <button class="btn btn-primary" onclick="cobrar(true)">
                            <i class="fa-solid fa-print"></i> Cobrar e Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
let cart = [];

function buscarProductos() {
    const term = document.getElementById('buscador').value.toLowerCase();
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        const name = card.querySelector('.product-name').textContent.toLowerCase();
        card.style.display = name.includes(term) ? 'block' : 'none';
    });
}

function filtrarCategoria() {
    const catId = document.getElementById('filtro-categoria').value;
    const cards = document.querySelectorAll('.product-card');
    // En una implementación real, los productos tendrían data-categoria
    // Por ahora filtramos visualmente
}

function agregarProducto(id, nombre, precio, stock, ivaIncluido = 1, impuestoPct = 19) {
    const existente = cart.find(item => item.id === id);
    if (existente) {
        if (existente.cantidad < stock) {
            existente.cantidad++;
        } else {
            alert('Stock máximo alcanzado');
        }
    } else {
        cart.push({ id, nombre, precio, cantidad: 1, stock, ivaIncluido, impuestoPct });
    }
    renderCart();
}

function actualizarCantidad(id, cambio) {
    const item = cart.find(i => i.id === id);
    if (item) {
        item.cantidad += cambio;
        if (item.cantidad <= 0) {
            cart = cart.filter(i => i.id !== id);
        } else if (item.cantidad > item.stock) {
            item.cantidad = item.stock;
            alert('Stock máximo alcanzado');
        }
    }
    renderCart();
}

function eliminarProducto(id) {
    cart = cart.filter(i => i.id !== id);
    renderCart();
}

function vaciarCarrito() {
    if (confirm('¿Vaciar el carrito?')) {
        cart = [];
        renderCart();
    }
}

function renderCart() {
    const container = document.getElementById('cart-items');
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="cart-empty">
                <i class="fa-solid fa-basket"></i>
                <p>Agregue productos del catálogo</p>
            </div>`;
        actualizarTotales();
        return;
    }

    let html = '';
    cart.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.nombre}</div>
                    <div class="cart-item-price">$${Number(item.precio).toLocaleString()} x ${item.cantidad}</div>
                </div>
                <div class="cart-item-qty">
                    <button class="qty-btn" onclick="actualizarCantidad(${item.id}, -1)">-</button>
                    <span>${item.cantidad}</span>
                    <button class="qty-btn" onclick="actualizarCantidad(${item.id}, 1)">+</button>
                </div>
                <div class="cart-item-subtotal">$${subtotal.toLocaleString()}</div>
                <i class="fa-solid fa-times cart-item-remove" onclick="eliminarProducto(${item.id})"></i>
            </div>`;
    });
    container.innerHTML = html;
    actualizarTotales();
}

function actualizarTotales() {
    let subtotal = 0;
    let ivaTotal = 0;

    cart.forEach(item => {
        const tieneIva = item.ivaIncluido !== 0 && item.ivaIncluido !== false && item.ivaIncluido !== null;
        const pct = item.impuestoPct || 19;
        const precioSinIva = tieneIva 
            ? item.precio / (1 + pct / 100) 
            : item.precio;
        const ivaItem = item.precio - precioSinIva;
        
        subtotal += precioSinIva * item.cantidad;
        ivaTotal += ivaItem * item.cantidad;
    });

    const total = subtotal + ivaTotal;

    document.getElementById('cart-subtotal').textContent = '$' + subtotal.toLocaleString();
    document.getElementById('cart-iva').textContent = '$' + ivaTotal.toLocaleString();
    document.getElementById('cart-total').textContent = '$' + total.toLocaleString();
}

function calcularCambio() {
    let subtotal = 0;
    let ivaTotal = 0;

    cart.forEach(item => {
        const tieneIva = item.ivaIncluido !== 0 && item.ivaIncluido !== false && item.ivaIncluido !== null;
        const pct = item.impuestoPct || 19;
        const precioSinIva = tieneIva 
            ? item.precio / (1 + pct / 100) 
            : item.precio;
        const ivaItem = item.precio - precioSinIva;
        
        subtotal += precioSinIva * item.cantidad;
        ivaTotal += ivaItem * item.cantidad;
    });

    const total = subtotal + ivaTotal;
    const recibido = parseFloat(document.getElementById('monto-recibido').value) || 0;
    const cambio = Math.max(0, recibido - total);
    
    document.getElementById('row-cambio').style.display = 'flex';
    document.getElementById('cart-cambio').textContent = '$' + cambio.toLocaleString();
}

function cobrar(imprimir) {
    if (cart.length === 0) {
        alert('Agregue productos al carrito');
        return;
    }

    const clienteId = document.getElementById('cliente-select').value || null;
    const metodoPago = document.getElementById('metodo-pago').value;
    const montoRecibido = parseFloat(document.getElementById('monto-recibido').value) || 0;

    const items = cart.map(item => ({
        producto_id: item.id,
        cantidad: item.cantidad,
        precio_unitario: item.precio,
        subtotal: item.precio * item.cantidad
    }));

    const data = {
        cliente_id: clienteId,
        metodo_pago: metodoPago,
        items: items,
        observaciones: ''
    };

    fetch('ajax/guardar_factura.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            if (imprimir) {
                window.open('pdf.php?id=' + d.factura_id, '_blank');
            }
            cart = [];
            renderCart();
            document.getElementById('monto-recibido').value = '';
            document.getElementById('row-cambio').style.display = 'none';
            alert('Venta guardada: ' + d.numero);
        } else {
            alert(d.error || 'Error al guardar');
        }
    });
}
</script>
</body>
</html>