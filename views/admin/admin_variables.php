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
    <title>AMPD</title>

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
                <span class="logo-text">Super Admin</span>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <li onclick="location.href='admin_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='admin_variables.php'">
                        <span class="material-icons" style="color: #5b21b6;">tune</span><span class="link-text">Variables de entorno</span>
                    </li>
                    <!-- Boton de exit -->
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
                    <p>En esta página, vamos a manejar las variables de entorno de toda la plataforma AMPD</p>
                </div>

                <div class="card-grid grid-3">
                    <!-- Tarjeta 1: Débito/Crédito -->
                    <div class="card">
                        <h3>Variable de entorno Impuesto al débito y crédito</h3>
                        <form id="form-dc" class="form-inline gap-2 mt-2">
                            <input type="text" class="input" name="value" placeholder="Valor (ej. 1,2000)" required>
                            <button class="btn">Crear</button>
                        </form>
                        <div id="list-dc" class="mt-3"></div>
                    </div>

                    <!-- Tarjeta 2: Retención -->
                    <div class="card">
                        <h3>Variable de entorno Retención</h3>
                        <form id="form-ret" class="form-inline gap-2 mt-2">
                            <input type="text" class="input" name="value" placeholder="Valor (ej. 3,5000)" required>
                            <button class="btn">Crear</button>
                        </form>
                        <div id="list-ret" class="mt-3"></div>
                    </div>

                    <!-- Tarjeta 3: Entidad de facturación -->
                    <div class="card">
                        <h3>Variable de entorno Entidad facturación</h3>
                        <form id="form-bill" class="form-grid mt-2">
                            <div class="grid grid-2 gap-2">
                                <input type="text" class="input" name="name" placeholder="Nombre" required>
                                <input type="text" class="input" name="cuit" placeholder="CUIT" required>
                            </div>
                            <button class="btn mt-2">Crear</button>
                        </form>
                        <div id="list-bill" class="mt-3"></div>
                    </div>
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