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

            < class="content">
                <div class="card">
                    <h2>Hola 👋 <?= htmlspecialchars($usuario) ?></h2>
                    <p>En esta página, vas a poder registrar un nuevo socio</p>
                </div>



                <!-- Formulario -->
                <div class="card">
                    <h2>Formularios</h2>
                    <form class="form-modern">
                        <div class="form-grid grid-4">

                            <!-- Nombre completo -->
                            <div class="input-group">
                                <label for="nombre">Nombre completo</label>
                                <div class="input-icon input-icon-name">
                                    <input type="text" id="nombre" name="nombre" placeholder="Juan Pérez" required />
                                </div>
                            </div>

                            <!-- Correo electrónico -->
                            <div class="input-group">
                                <label for="email">Correo electrónico</label>
                                <div class="input-icon input-icon-email">
                                    <input id="email" name="email" placeholder="usuario@correo.com" />
                                </div>
                            </div>

                            <!-- Fecha de nacimiento -->
                            <div class="input-group">
                                <label for="fecha">Fecha de nacimiento</label>
                                <div class="input-icon input-icon-date">
                                    <input id="fecha" name="fecha" />
                                </div>
                            </div>

                            <!-- Teléfono -->
                            <div class="input-group">
                                <label for="telefono">Teléfono</label>
                                <div class="input-icon input-icon-phone">
                                    <input id="telefono" name="telefono" />
                                </div>
                            </div>

                            <!-- DNI -->
                            <div class="input-group">
                                <label for="dni">DNI</label>
                                <div class="input-icon input-icon-dni">
                                    <input id="dni" name="dni" />
                                </div>
                            </div>

                            <!-- Edad -->
                            <div class="input-group">
                                <label for="edad">Edad</label>
                                <div class="input-icon input-icon-age">
                                    <input id="edad" name="edad" />
                                </div>
                            </div>

                            <!-- CUIT -->
                            <div class="input-group">
                                <label for="cuit">CUIT</label>
                                <div class="input-icon input-icon-cuit">
                                    <input id="cuit" name="cuit" />
                                </div>
                            </div>

                            <!-- Provincia -->
                            <div class="input-group">
                                <label for="provincia">Provincia</label>
                                <div class="input-icon input-icon-globe">
                                    <select id="provincia" name="provincia" required>
                                        <option value="">Seleccionar</option>
                                        <option>Buenos Aires</option>
                                        <option>Córdoba</option>
                                        <option>Santa Fe</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Localidad -->
                            <div class="input-group">
                                <label for="localidad">Localidad</label>
                                <div class="input-icon input-icon-city">
                                    <input type="text" id="localidad" name="localidad" required />
                                </div>
                            </div>

                            <!-- Código Postal -->
                            <div class="input-group">
                                <label for="cp">Código Postal</label>
                                <div class="input-icon input-icon-cp">
                                    <input type="text" id="cp" name="cp" />
                                </div>
                            </div>

                            <!-- Dirección -->
                            <div class="input-group">
                                <label for="direccion">Dirección</label>
                                <div class="input-icon input-icon-address">
                                    <input type="text" id="direccion" name="direccion" required />
                                </div>
                            </div>
                        </div>
                        <!-- Observaciones -->
                        <div class="input-group">
                            <label for="observaciones">Observaciones</label>
                            <div class="input-icon input-icon-comment">
                                <textarea id="observaciones" name="observaciones" maxlength="233" rows="3"
                                    placeholder="Escribí un comentario..."></textarea>
                            </div>
                            <small class="char-count" data-for="observaciones">Quedan 233 caracteres.</small>
                        </div>

                        <!-- Botones -->
                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Enviar</button>
                            <button class="btn btn-cancelar" type="reset">Cancelar</button>
                        </div>
                    </form>
                </div>


                <!-- Tabla con socios registrados -->
                <div class="card tabla-card">
                    <h2>Tablas</h2>
                    <div class="tabla-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>DNI</th>
                                    <th>Estado Bancario</th>
                                    <th>Estado Cuotas</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Carlos</td>
                                    <td>32569852</td>
                                    <td><span class="badge success">Cuentas completas</span></td>
                                    <td><span class="badge warning">Cuota adeudada</span></td>
                                </tr>
                                <tr>
                                    <td>Laura</td>
                                    <td>56987563</td>
                                    <td><span class="badge warning">Cuentas incompletas</span></td>
                                    <td><span class="badge success">Cuota adeudada</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
        </div>
    </div>

    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>