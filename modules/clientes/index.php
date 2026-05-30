<?php
require_once '../../config/proteger_modulo.php';
require_once '../../config/conexion.php';

$busqueda = $_GET['busqueda'] ?? '';

$where = "c.activo = 1";
$params = [];

if ($busqueda) {
    $where .= " AND (c.nombre LIKE ? OR c.apellido LIKE ? OR c.numero_doc LIKE ? OR c.email LIKE ? OR c.telefono LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

$stmt = db()->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM facturas WHERE cliente_id = c.id) as total_facturas,
           (SELECT COALESCE(SUM(total),0) FROM facturas WHERE cliente_id = c.id AND estado = 'pagada') as total_compras
    FROM clientes c
    WHERE $where
    ORDER BY c.nombre ASC
    LIMIT 100
");
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$stmt = db()->query("SELECT COUNT(*) as total FROM clientes WHERE activo = 1");
$total_clientes = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - EASYGES</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="css/clientes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="erp-body">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="fa-solid fa-building"></i></div>
        <div class="sidebar-brand">
            <span class="brand-name">EASYGES</span>
            <span class="brand-sub">Gestión de Ventas</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item"><i class="fa-solid fa-table-columns"></i><span>Dashboard</span></a>
        <?php if (puedeAccederModulo('facturacion')): ?>
        <a href="../facturacion/" class="nav-item"><i class="fa-solid fa-file-invoice"></i><span>Facturación</span></a>
        <?php endif; ?>
        <?php if (puedeAccederModulo('inventario')): ?>
        <a href="../inventario/" class="nav-item"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a>
        <?php endif; ?>
        <?php if (puedeAccederModulo('compras')): ?>
        <a href="../compras/" class="nav-item"><i class="fa-solid fa-cart-shopping"></i><span>Compras</span></a>
        <?php endif; ?>
        <a href="index.php" class="nav-item active"><i class="fa-solid fa-users"></i><span>Clientes</span></a>
        <?php if (puedeAccederModulo('contabilidad')): ?>
        <a href="../contabilidad/" class="nav-item"><i class="fa-solid fa-calculator"></i><span>Contabilidad</span></a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
                <span class="user-role"><?= htmlspecialchars($_SESSION['usuario_rol']) ?></span>
            </div>
        </div>
        <a href="../../auth/logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión</a>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
        <div class="topbar-right">
            <span class="topbar-user"><strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong><small><?= htmlspecialchars($_SESSION['usuario_rol']) ?></small></span>
        </div>
    </header>

    <div class="page-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Clientes</h1>
                <p class="page-subtitle">Gestión de clientes y moteros</p>
            </div>
            <div class="page-actions">
                <a href="nuevo.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nuevo Cliente</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card stat-blue">
                <div class="stat-info"><span class="stat-label">Total Clientes</span><span class="stat-value"><?= $total_clientes ?></span></div>
                <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-card stat-teal">
                <div class="stat-info"><span class="stat-label">Con purchases</span><span class="stat-value"><?= count(array_filter($clientes, fn($c) => $c['total_facturas'] > 0)) ?></span></div>
                <div class="stat-icon"><i class="fa-solid fa-shopping-bag"></i></div>
            </div>
        </div>

        <div class="filters-card">
            <form method="GET" action="index.php" class="filters-form">
                <div class="filter-group" style="flex: 2;">
                    <input type="text" name="busqueda" placeholder="Buscar por nombre, documento, email o teléfono..." value="<?= htmlspecialchars($busqueda) ?>" class="filter-input">
                </div>
                <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Buscar</button>
                <?php if ($busqueda): ?>
                <a href="index.php" class="btn btn-outline">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Contacto</th>
                        <th>Compras</th>
                        <th>Total Comprado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                    <tr><td colspan="6" class="empty-state"><i class="fa-solid fa-users"></i><p>No hay clientes registrados</p></td></tr>
                    <?php else: ?>
                    <?php foreach ($clientes as $c): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($c['nombre']) ?></strong>
                            <?php if ($c['apellido']): ?><br><small><?= htmlspecialchars($c['apellido']) ?></small><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($c['tipo_doc']) ?> <?= htmlspecialchars($c['numero_doc'] ?? '-') ?></td>
                        <td>
                            <?php if ($c['email']): ?><?= htmlspecialchars($c['email']) ?><br><?php endif; ?>
                            <?php if ($c['telefono']): ?><small><?= htmlspecialchars($c['telefono']) ?></small><?php endif; ?>
                        </td>
                        <td><?= $c['total_facturas'] ?></td>
                        <td>$<?= number_format($c['total_compras'], 0) ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="ver.php?id=<?= $c['id'] ?>" class="btn-icon" title="Ver"><i class="fa-solid fa-eye"></i></a>
                                <a href="editar.php?id=<?= $c['id'] ?>" class="btn-icon" title="Editar"><i class="fa-solid fa-edit"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>function toggleSidebar(){document.getElementById('sidebar').classList.toggle('collapsed');document.querySelector('.main-content').classList.toggle('expanded');}</script>
</body>
</html>