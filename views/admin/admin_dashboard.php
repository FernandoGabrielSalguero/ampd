<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión correctamente
require_once __DIR__ . '/../../core/SessionManager.php';
SessionManager::start();

// Verificar si el usuario está logueado
$user = SessionManager::getUser();
if (!$user) {
    header("Location: /index.php?expired=1");
    exit;
}

// Verificar rol
if (!isset($user['role']) || $user['role'] !== 'Super_admin') {
    die("🚫 Acceso restringido: esta página es solo para usuarios Super_admin.");
}

// Opcional: datos del usuario
$usuario = $user['username'] ?? 'Sin usuario';
$email = $user['email'] ?? 'Sin email';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Impulsa SO</title>

    <!-- Íconos de Material Design -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <!-- Framework Success desde CDN -->
    <link rel="stylesheet" href="https://www.fernandosalguero.com/cdn/assets/css/framework.css">
    <script src="https://www.fernandosalguero.com/cdn/assets/javascript/framework.js" defer></script>
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="material-icons logo-icon">dashboard</span>
            <span class="logo-text">ISO</span>
        </div>
        <nav class="sidebar-menu">
            <ul>
                <li onclick="location.href='admin_dashboard.php'">
                    <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                </li>
                <li onclick="location.href='admin_altaUsuarios.php'">
                    <span class="material-icons" style="color: #5b21b6;">person</span><span class="link-text">Alta usuarios</span>
                </li>
                <li onclick="location.href='admin_importarUsuarios.php'">
                    <span class="material-icons" style="color: #5b21b6;">upload_file</span><span class="link-text">Carga Masiva</span>
                </li>
                <li onclick="location.href='admin_pagoFacturas.php'">
                    <span class="material-icons" style="color: #5b21b6;">attach_money</span><span class="link-text">Pago Facturas</span>
                </li>
                <li onclick="location.href='../../../logout.php'">
                    <span class="material-icons" style="color: red;">logout</span><span class="link-text">Salir</span>
                </li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <button class="btn-icon" onclick="toggleSidebar()">
                <span class="material-icons" id="collapseIcon">chevron_left</span>
            </button>
        </div>
    </aside>

    <div class="main">
        <header class="navbar">
            <button class="btn-icon" onclick="toggleSidebar()">
                <span class="material-icons">menu</span>
            </button>
            <div class="navbar-title">Inicio</div>
        </header>

        <section class="content">
            <div class="card">
                <h2>Hola 👋 <?= htmlspecialchars($usuario) ?></h2>
                <p>En esta página, vamos a tener KPI.</p>
            </div>

            <div class="card-grid grid-4">
                <div class="card"><h3>KPI 1</h3><p>Contenido 1</p></div>
                <div class="card"><h3>KPI 2</h3><p>Contenido 2</p></div>
                <div class="card"><h3>KPI 3</h3><p>Contenido 3</p></div>
                <div class="card"><h3>KPI 4</h3><p>Contenido 4</p></div>
            </div>

            <div class="card">
                <form class="form-modern">
                    <div class="input-group">
                        <label>Correo</label>
                        <div class="input-icon">
                            <span class="material-icons">mail</span>
                            <input type="email" placeholder="ejemplo@correo.com">
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button class="btn btn-aceptar" type="submit">Enviar</button>
                        <button class="btn btn-cancelar" type="button">Cancelar</button>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

<script src="../../views/partials/spinner-global.js"></script>

<script>
    console.log(<?php echo json_encode($_SESSION); ?>);
</script>
</body>
</html>
