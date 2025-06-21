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
    <style>
        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }
    </style>
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
                        <div class="form-grid grid-2">

                            <!-- Nombre -->
                            <div class="input-group">
                                <label for="usuario">Nombre</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="text" id="user_nombre" name="user_nombre" placeholder="Nombre del asociado" required>
                                </div>
                            </div>

                            <!-- DNI -->
                            <div class="input-group">
                                <label for="usuario">DNI</label>
                                <div class="input-icon">
                                    <span class="material-icons">assignment_ind</span>
                                    <input type="number" id="user_dni" name="user_dni" placeholder="DNI del asociado" required>
                                </div>
                            </div>

                            <!-- Correo -->
                            <div class="input-group">
                                <label for="usuario">Correo</label>
                                <div class="input-icon">
                                    <span class="material-icons">email</span>
                                    <input type="email" id="user_correo" name="user_correo" placeholder="Correo del asociado" required>
                                </div>
                            </div>

                            <!-- Telefono -->
                            <div class="input-group">
                                <label for="usuario">Telefono</label>
                                <div class="input-icon">
                                    <span class="material-icons">call</span>
                                    <input type="text" id="user_telefono" name="user_telefono" placeholder="Telefono del asociado" required>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="form-buttons">
                                <button class="btn btn-aceptar" type="submit">Asociar</button>
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
                <!-- Alert -->
                <div class="alert-container" id="alertContainer"></div>
            </section>

        </div>
    </div>


    <!-- modal para eliminar a un socio -->
    <div id="modal" class="modal hidden">
        <div class="modal-content">
            <h3 id="modal-title">¿Eliminar usuario?</h3>
            <p id="modal-body">Esta acción no se puede deshacer.</p>
            <div class="form-buttons">
                <button class="btn btn-aceptar" id="confirmarEliminar">Eliminar</button>
                <button class="btn btn-cancelar" onclick="closeModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Modal para editar usuario -->
<div id="modal-editar" class="modal hidden">
    <div class="modal-content" style="max-height: 80vh; overflow-y: auto;">
        <h3>Editar usuario</h3>
        <form id="formEditarUsuario">
            <input type="hidden" name="usuario_id" id="edit_usuario_id">
            
            <h4>Datos básicos</h4>
            <input type="text" name="nombre" id="edit_nombre" placeholder="Nombre" required>
            <input type="text" name="correo" id="edit_correo" placeholder="Correo">
            <input type="text" name="telefono" id="edit_telefono" placeholder="Teléfono">
            <input type="text" name="dni" id="edit_dni" placeholder="DNI">

            <h4>Información adicional</h4>
            <input type="text" name="direccion" id="edit_direccion" placeholder="Dirección">
            <input type="text" name="localidad" id="edit_localidad" placeholder="Localidad">
            <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento" placeholder="Fecha de nacimiento">

            <h4>Datos bancarios</h4>
            <input type="text" name="alias_a" id="edit_alias_a" placeholder="Alias A">
            <input type="text" name="cbu_a" id="edit_cbu_a" placeholder="CBU A">
            <input type="text" name="titular_a" id="edit_titular_a" placeholder="Titular A">
            <input type="text" name="cuit_a" id="edit_cuit_a" placeholder="CUIT A">
            <input type="text" name="banco_a" id="edit_banco_a" placeholder="Banco A">

            <h4>Disciplina libre</h4>
            <input type="text" name="disciplina_libre" id="edit_disciplina_libre" placeholder="Disciplina libre">

            <!-- botón de guardar -->
            <div class="form-buttons">
                <button class="btn btn-aceptar" type="submit">Guardar</button>
                <button class="btn btn-cancelar" type="button" onclick="closeEditModal()">Cancelar</button>
            </div>
        </form>
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
                        showAlert('success', data.message);
                        this.reset();
                        cargarUsuarios();
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    showAlert('error', "Hubo un error al crear el usuario.");
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

        // abrir modal de eliminar usuario
        function openModal() {
            document.getElementById("modal").classList.remove("hidden");
        }

        function closeModal() {
            document.getElementById("modal").classList.add("hidden");
        }

        // Cargar al iniciar
        window.addEventListener("DOMContentLoaded", cargarUsuarios);

        // Buscar al escribir
        document.getElementById("buscarCuit").addEventListener("input", cargarUsuarios);
        document.getElementById("buscarNombre").addEventListener("input", cargarUsuarios);

        // funcion para eliminar usuario
        let idUsuarioAEliminar = null;

        // Delegación para botón eliminar
        document.addEventListener("click", function(e) {
            if (e.target.closest(".btn-borrar")) {
                const fila = e.target.closest("tr");
                idUsuarioAEliminar = fila.querySelector("td").textContent.trim();

                document.getElementById("modal-title").textContent = "¿Eliminar usuario?";
                document.getElementById("modal-body").textContent = "Esta acción eliminará el registro definitivamente.";
                openModal();
            }
        });

        // Confirmación
        document.getElementById("confirmarEliminar").addEventListener("click", function() {
            if (!idUsuarioAEliminar) return;

            fetch("../../controllers/admin_altaUsuariosController.php", {
                    method: "DELETE",
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: idUsuarioAEliminar
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        showAlert('success', "Usuario eliminado correctamente.");
                        cargarUsuarios();
                    } else {
                        showAlert('error', data.message);
                    }
                    closeModal();
                    idUsuarioAEliminar = null;
                })
                .catch(err => {
                    console.error(err);
                    showAlert('error', "Error de conexión.");
                    closeModal();
                });
        });

        // logica modal editar usuario
        function openEditModal() {
    document.getElementById("modal-editar").classList.remove("hidden");
}

function closeEditModal() {
    document.getElementById("modal-editar").classList.add("hidden");
}

document.addEventListener("click", function (e) {
    if (e.target.closest(".btn-editar")) {
        const fila = e.target.closest("tr");
        const id = fila.querySelector("td").textContent.trim();

        fetch(`../../controllers/admin_altaUsuariosController.php?detalle=1&id=${id}`)
            .then(res => res.json())
            .then(data => {
                console.log("📦 Datos recibidos para edición:", data); // Debug
                if (data.status === "success") {
                    const u = data.data;

                    document.getElementById("edit_usuario_id").value = id;
                    document.getElementById("edit_nombre").value = u.info.nombre ?? '';
                    document.getElementById("edit_correo").value = u.info.correo ?? '';
                    document.getElementById("edit_telefono").value = u.info.telefono ?? '';
                    document.getElementById("edit_dni").value = u.info.dni ?? '';

                    document.getElementById("edit_direccion").value = u.info.user_direccion ?? '';
                    document.getElementById("edit_localidad").value = u.info.user_localidad ?? '';
                    document.getElementById("edit_fecha_nacimiento").value = u.info.user_fecha_nacimiento ?? '';

                    document.getElementById("edit_alias_a").value = u.bancarios.alias_a ?? '';
                    document.getElementById("edit_cbu_a").value = u.bancarios.cbu_a ?? '';
                    document.getElementById("edit_titular_a").value = u.bancarios.titular_a ?? '';
                    document.getElementById("edit_cuit_a").value = u.bancarios.cuit_a ?? '';
                    document.getElementById("edit_banco_a").value = u.bancarios.banco_a ?? '';

                    document.getElementById("edit_disciplina_libre").value = u.disciplinaLibre.disciplina ?? '';

                    openEditModal();
                } else {
                    showAlert('error', 'No se pudo cargar la información del usuario.');
                }
            })
            .catch(err => {
                console.error(err);
                showAlert('error', 'Error al obtener datos del usuario.');
            });
    }
});

// Enviar los cambios al backend
document.getElementById("formEditarUsuario").addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch("../../controllers/admin_altaUsuariosController.php", {
        method: "PUT",
        body: JSON.stringify(Object.fromEntries(formData)),
        headers: {
            "Content-Type": "application/json"
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                showAlert('success', 'Usuario actualizado correctamente.');
                closeEditModal();
                cargarUsuarios();
            } else {
                showAlert('error', data.message);
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('error', 'Error de conexión al actualizar.');
        });
});

    </script>

    </div>
</body>

</html>