<?php
// ============================================
// index.php - LOGIN
// ============================================
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/conexion.php';

    $usuario  = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor ingrese usuario y contraseña.';
    } else {
        $stmt = db()->prepare("SELECT * FROM usuarios WHERE usuario = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();

        // Verificar password — las de prueba usan password_verify
        // Los hashes en database.sql corresponden a: admin123, fact123, cont123, cajero123
        $ok = false;
        if ($user) {
            if ($password === $user['password']) {
                $ok = true;
            }
        }

        if ($ok) {
            // Registrar último login
            db()->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$user['id']]);

            // Guardar sesión
            $_SESSION['usuario_id']     = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_usuario']= $user['usuario'];
            $_SESSION['usuario_rol']    = $user['rol'];

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EASYGES — Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-body">

    <div class="login-bg">
        <div class="login-card">
            <!-- Icono principal -->
            <div class="login-icon">
                <i class="fa-solid fa-lock"></i>
            </div>

            <h1 class="login-title">EASYGES</h1>
            <p class="login-subtitle">Ingrese sus credenciales</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="login-form">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <div class="input-wrapper">
                        <i class="fa-regular fa-user input-icon"></i>
                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            placeholder="Ingrese su usuario"
                            value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Ingrese su contraseña"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Iniciar Sesión
                </button>
            </form>

            <div class="login-demo">
                <p><strong>Usuarios de prueba:</strong></p>
                <p>• admin / admin123 (Administrador - Todos los módulos)</p>
                <p>• contabilidad / cont123 (Contador - Inventario, Contabilidad)</p>
                <p>• cajero / cajero123 (Cajero - Facturación, Clientes)</p>
            </div>
        </div>
    </div>

</body>
</html>
