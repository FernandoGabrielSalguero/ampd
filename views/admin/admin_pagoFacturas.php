<?php
// Mostrar errores en pantalla (útil en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión y proteger acceso
session_start();

// ⚠️ Expiración por inactividad (20 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: /index.php?expired=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time(); // Actualiza el tiempo de actividad

// 🚧 Protección de acceso general
if (!isset($_SESSION['usuario'])) {
    die("⚠️ Acceso denegado. No has iniciado sesión.");
}

// 🔐 Protección por rol
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("🚫 Acceso restringido: esta página es solo para usuarios Administrador.");
}

// Datos del usuario en sesión
$nombre = $_SESSION['nombre'] ?? 'Sin nombre';
$correo = $_SESSION['correo'] ?? 'Sin correo';
$usuario = $_SESSION['usuario'] ?? 'Sin usuario';
$telefono = $_SESSION['telefono'] ?? 'Sin teléfono';


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

    <!-- 🔲 CONTENEDOR PRINCIPAL -->
    <div class="layout">

        <!-- 🧭 SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <span class="material-icons logo-icon">dashboard</span>
                <span class="logo-text">AMPD</span>
            </div>

            <nav class="sidebar-menu">
                <ul>
                    <li onclick="location.href='admin_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='admin_altaUsuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">person</span><span class="link-text">Alta usuarios</span>
                    </li>
                    <li onclick="location.href='admin_pagoFacturas.php'">
                        <span class="material-icons" style="color: #5b21b6;">upload_file</span><span class="link-text">Pago Facturas</span>
                    </li>
                    <li onclick="location.href='admin_Eventos.php'">
                        <span class="material-icons" style="color: #5b21b6;">nightlife</span><span class="link-text">Eventos</span>
                    </li>
                    <li onclick="location.href='admin_sucripciones.php'">
                        <span class="material-icons" style="color: #5b21b6;">assignment</span><span class="link-text">Suscripciones</span>
                    </li>
                    <li onclick="location.href='admin_consumoInternos.php'">
                        <span class="material-icons" style="color: #5b21b6;">shopping_cart</span><span class="link-text">Consumos internos</span>
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

        <!-- 🧱 MAIN -->
        <div class="main">

            <!-- 🟪 NAVBAR -->
            <header class="navbar">
                <button class="btn-icon" onclick="toggleSidebar()">
                    <span class="material-icons">menu</span>
                </button>
                <div class="navbar-title">Pago de facturas</div>
            </header>

            <!-- 📦 CONTENIDO -->
            <section class="content">

                <!-- Bienvenida -->
                <div class="card">
                    <h2>Hola 👋</h2>
                    <p>Vamos a poder visualizar las solicitudes de pagos de nuestros socios.</p>
                </div>

                <!-- Formulario de alta de pagos -->
<div class="card">
    <h2>Nuevo Pedido de Pago</h2>
    <form class="form-modern" id="formNuevoPago" method="POST" action="procesar_pago_evento.php">
        <div class="form-grid grid-2">
            <!-- Selección de Usuario -->
            <div class="input-group">
                <label for="usuario_id">Beneficiario</label>
                <select name="usuario_id" id="usuario_id" required>
                    <?php
                    require_once '../../conexion.php';
                    $usuarios = mysqli_query($conexion, "SELECT u.id, CONCAT(i.nombre, ' ', i.apellido, ' (DNI: ', i.dni, ')') as nombre_completo FROM usuarios u JOIN user_info i ON u.id = i.usuario_id ORDER BY i.nombre ASC");
                    while ($u = mysqli_fetch_assoc($usuarios)) {
                        echo "<option value='{$u['id']}'>{$u['nombre_completo']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Selección de Evento -->
            <div class="input-group">
                <label for="evento_id">Evento</label>
                <select name="evento_id" id="evento_id" required>
                    <?php
                    $eventos = mysqli_query($conexion, "SELECT id, nombre FROM eventos ORDER BY fecha DESC");
                    while ($e = mysqli_fetch_assoc($eventos)) {
                        echo "<option value='{$e['id']}'>{$e['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Monto del Contrato -->
            <div class="input-group">
                <label for="monto">Monto Contrato</label>
                <input type="number" step="0.01" name="monto" id="monto" required>
            </div>

            <!-- Cálculo Automático -->
            <div class="input-group">
                <label for="sellado">Sellado</label>
                <select name="sellado" id="sellado" required>
                    <option value="0.65">0.65%</option>
                    <option value="0.50">0.50%</option>
                </select>
            </div>

            <div class="input-group">
                <label for="impuesto_al_cheque">Impuesto al Cheque</label>
                <select name="impuesto_al_cheque" id="impuesto_al_cheque" required>
                    <option value="1.2">1.2%</option>
                    <option value="1.0">1.0%</option>
                </select>
            </div>

            <div class="input-group">
                <label for="retencion">Retención</label>
                <select name="retencion" id="retencion" required>
                    <option value="3">3%</option>
                    <option value="5">5%</option>
                </select>
            </div>

            <!-- Comprobante -->
            <div class="input-group">
                <label for="comprobante">Comprobante</label>
                <input type="text" name="comprobante" id="comprobante">
            </div>

        </div>

        <!-- Botón -->
        <button class="btn" type="submit">Registrar pago</button>
    </form>
</div>




                <!-- Tarjeta de buscador -->
                <div class="card">
                    <h2>Busca pedidos</h2>

                    <form class="form-modern">
                        <div class="form-grid grid-2">
                            <!-- Buscar por DNI -->
                            <div class="input-group">
                                <label for="buscarCuit">Podes buscar por DNI</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="text" id="buscarCuit" name="buscarCuit" placeholder="20123456781">
                                </div>
                            </div>

                            <!-- Buscar por Nombre -->
                            <div class="input-group">
                                <label for="buscarNombre">Podes buscar por nombre</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="text" id="buscarNombre" name="buscarNombre" placeholder="Ej: Juan Pérez">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabla -->
                <div class="card">
                    <h2>Listado de pedidos de liquidaciones</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Beneficiario</th>
                                    <th>Contrato</th>
                                    <th>Importe</th>
                                    <th>% Retención</th>
                                    <th>Fecha pedido</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaPagoFacturas">
                                <!-- Contenido dinámico -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

        </div>
    </div>
    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>