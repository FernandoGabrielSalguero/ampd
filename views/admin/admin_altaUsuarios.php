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
                <div class="navbar-title">Alta Socios</div>
            </header>

            <!-- 📦 CONTENIDO -->
            <section class="content">

                <!-- Bienvenida -->
                <div class="card">
                    <h2>Hola 👋</h2>
                    <p>En esta página, vamos a poder dar de alta a los usuarios y modificar sus propiedades</p>
                </div>

                <!-- Formulario -->
                <div class="card">
                    <h2>Crear nuevo usuario</h2>
                    <form class="form-modern" id="formUsuario">
                        <div class="form-grid grid-4">

                            <!-- Nombre -->
                            <div class="input-group">
                                <label for="usuario">Nombre</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="text" id="user_nombre" name="user_nombre" placeholder="Coloca el Nombre del asociado" required>
                                </div>
                            </div>

                            <!-- DNI -->
                            <div class="input-group">
                                <label for="usuario">DNI</label>
                                <div class="input-icon">
                                    <span class="material-icons">assignment_ind</span>
                                    <input type="number" id="user_dni" name="user_dni" placeholder="Coloca el DNI del asociado" required>
                                </div>
                            </div>

                            <!-- Correo -->
                            <div class="input-group">
                                <label for="usuario">Correo</label>
                                <div class="input-icon">
                                    <span class="material-icons">email</span>
                                    <input type="email" id="user_correo" name="user_correo" placeholder="Coloca el Apellido del asociado" required>
                                </div>
                            </div>

                            <!-- Telefono -->
                            <div class="input-group">
                                <label for="usuario">Telefono</label>
                                <div class="input-icon">
                                    <span class="material-icons">call</span>
                                    <input type="text" id="user_telefono" name="user_telefono" placeholder="Coloca el Telefono del asociado" required>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="form-buttons">
                                <button class="btn btn-aceptar" type="submit">Crear usuario</button>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Tarjeta de buscador -->
                <div class="card">
                    <h2>Busca asociados</h2>

                    <form class="form-modern">
                        <div class="form-grid grid-2">
                            <!-- Buscar por DNI -->
                            <div class="input-group">
                                <label for="buscarCuit">Podes buscar por DNI</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="number" id="buscarCuit" name="buscarCuit" placeholder="20123456781">
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
                    <h2>Listado de usuarios registrados</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Telefono</th>
                                    <th>DNI</th>
                                    <th>N° Socio</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaUsuarios">
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


        // alta usuarios
document.getElementById("formUsuario").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch("../../controllers/admin_altaUsuariosController.php", {
            method: "POST",
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                alert(data.message);
                this.reset(); // limpiar el formulario
                cargarUsuarios(); // 🔄 recargar la tabla dinámicamente
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Hubo un error al crear el usuario.");
        });
})

        // cargamos usuarios
        function cargarUsuarios() {
            const dni = document.getElementById("buscarCuit").value;
            const nombre = document.getElementById("buscarNombre").value;

            fetch(`../../controllers/admin_altaUsuariosController.php?dni=${dni}&nombre=${nombre}`)
                .then(res => res.json())
                .then(data => {
                    const tabla = document.getElementById("tablaUsuarios");
                    tabla.innerHTML = "";

                    if (data.status === "success") {
                        data.data.forEach(user => {
const fila = `
    <tr>
        <td>${user.id}</td>
        <td>${user.nombre}</td>
        <td>${user.correo}</td>
        <td>${user.telefono}</td>
        <td>${user.dni ?? '-'}</td>
        <td>${user.n_socio ?? '-'}</td>
<td>
    <button class="btn btn-icon btn-editar" title="Editar" data-tooltip="Editar usuario">
        <span class="material-icons" style="color: #2563eb;">edit</span>
    </button>
    <button class="btn btn-icon btn-borrar" title="Borrar" data-tooltip="Borrar usuario">
        <span class="material-icons" style="color: #dc2626;">delete</span>
    </button>
</td>
    </tr>`;
                            tabla.innerHTML += fila;
                        });
                    } else {
                        tabla.innerHTML = `<tr><td colspan="6">Error al cargar usuarios.</td></tr>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                });
        }

        // Cargar al iniciar
        window.addEventListener("DOMContentLoaded", cargarUsuarios);

        // Buscar al escribir
        document.getElementById("buscarCuit").addEventListener("input", cargarUsuarios);
        document.getElementById("buscarNombre").addEventListener("input", cargarUsuarios);
    </script>
</body>

</html>