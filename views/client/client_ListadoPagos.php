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
if (!isset($user['role']) || $user['role'] !== 'Administrativo') {
    die("🚫 Acceso restringido: esta página es solo para usuarios Administrativo.");
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
                <span class="logo-text">Administrativo</span>
            </div>
            <nav class="sidebar-menu"> 
                <ul>
                    <li onclick="location.href='client_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='client_asociar.php'">
                        <span class="material-icons" style="color: #5b21b6;">person</span><span class="link-text">Registrar Socio</span>
                    </li>
                    <li onclick="location.href='client_pagoFacturas.php'">
                        <span class="material-icons" style="color: #5b21b6;">attach_money</span><span class="link-text">Pago Facturas</span>
                    </li>
                    <li onclick="location.href='client_ListadoPagos.php'">
                        <span class="material-icons" style="color: #5b21b6;">assignment_turned_in</span><span class="link-text">Listado de pagos</span>
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
                    <p>En esta página, vas a poder visualizar las ordenes de pago que ya estan cargadas y conocer en que estado se encuentran.</p>
                </div>

 <!-- Filtros -->
                <div class="card">
                    <h2>Filtros para buscar a un socio</h2>
                    <form class="form-modern" id="form-filtros">
                        <div class="form-grid grid-2">

                            <div class="input-group">
                                <label for="search_nombre">Buscar por nombre</label>
                                <div class="input-icon">
                                    <span class="material-icons">search</span>
                                    <input type="text" id="search_nombre" name="search_nombre" placeholder="Juan" />
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="search_dni">Buscar por DNI</label>
                                <div class="input-icon">
                                    <span class="material-icons">search</span>
                                    <input type="text" id="search_dni" name="search_dni" inputmode="numeric" pattern="^\d{0,10}$" maxlength="10" placeholder="DNI" />
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

                <!-- Tabla -->
                <div class="card tabla-card">
                    <h2>Tabla de socios</h2>
                    <div class="tabla-wrapper">
                        <table class="data-table" id="tabla-socios">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Estado Bancario</th>
                                    <th>Estado Cuotas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-socios">
                                <!-- filas dinámicas -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="alert-container" id="alertContainer"></div>
            </section>
        </div>
    </div>

    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>