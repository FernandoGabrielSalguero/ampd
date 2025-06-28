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
                        <div class="form-grid grid-3">
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

                            <!-- Buscar por N° Socio -->
                            <div class="input-group">
                                <label for="buscarNSocio">Podes buscar por N° Socio</label>
                                <div class="input-icon">
                                    <span class="material-icons">tag</span>
                                    <input type="text" id="buscarNSocio" name="buscarNSocio" placeholder="Ej: 123">
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
        <div class="modal-content" style="max-width: 900px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <h3>Editar usuario</h3>
            <form class="form-modern" id="formEditarUsuario">
                <input type="hidden" name="usuario_id" id="edit_usuario_id">

                <!-- Datos básicos -->
                <h4>Datos básicos</h4>
                <div class="form-grid grid-3">
                    <div class="input-group">
                        <label for="edit_nombre">Nombre</label>
                        <div class="input-icon">
                            <span class="material-icons">person</span>
                            <input type="text" name="nombre" id="edit_nombre" placeholder="Nombre" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_correo">Correo</label>
                        <div class="input-icon">
                            <span class="material-icons">email</span>
                            <input type="email" name="correo" id="edit_correo" placeholder="Correo">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_telefono">Teléfono</label>
                        <div class="input-icon">
                            <span class="material-icons">call</span>
                            <input type="text" name="telefono" id="edit_telefono" placeholder="Teléfono">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_dni">DNI</label>
                        <div class="input-icon">
                            <span class="material-icons">assignment_ind</span>
                            <input type="text" name="dni" id="edit_dni" placeholder="DNI">
                        </div>
                    </div>
                </div>

                <br>
                <hr>
                <br>

                <!-- Información adicional -->
                <h4>Información adicional</h4>
                <div class="form-grid grid-3">
                    <div class="input-group">
                        <label for="edit_direccion">Dirección</label>
                        <div class="input-icon">
                            <span class="material-icons">home</span>
                            <input type="text" name="direccion" id="edit_direccion" placeholder="Dirección">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_localidad">Localidad</label>
                        <div class="input-icon">
                            <span class="material-icons">location_city</span>
                            <input type="text" name="localidad" id="edit_localidad" placeholder="Localidad">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_fecha_nacimiento">Fecha de nacimiento</label>
                        <div class="input-icon">
                            <span class="material-icons">calendar_today</span>
                            <input type="date" name="fecha_nacimiento" id="edit_fecha_nacimiento">
                        </div>
                    </div>
                </div>

                <br>
                <hr>
                <br>

                <!-- Cuenta A -->
                <h4>Datos bancarios</h4>
                <br>
                <h5>Cuenta A</h5>
                <div class="form-grid grid-3">
                    <div class="input-group">
                        <label for="edit_alias_a">Alias A</label>
                        <div class="input-icon">
                            <span class="material-icons">alternate_email</span>
                            <input type="text" name="alias_a" id="edit_alias_a" placeholder="Alias A">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_cbu_a">CBU A</label>
                        <div class="input-icon">
                            <span class="material-icons">account_balance</span>
                            <input type="text" name="cbu_a" id="edit_cbu_a" placeholder="CBU A">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_titular_a">Titular A</label>
                        <div class="input-icon">
                            <span class="material-icons">person</span>
                            <input type="text" name="titular_a" id="edit_titular_a" placeholder="Titular A">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_cuit_a">CUIT A</label>
                        <div class="input-icon">
                            <span class="material-icons">badge</span>
                            <input type="text" name="cuit_a" id="edit_cuit_a" placeholder="CUIT A">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_banco_a">Banco A</label>
                        <div class="input-icon">
                            <span class="material-icons">account_balance_wallet</span>
                            <input type="text" name="banco_a" id="edit_banco_a" placeholder="Banco A">
                        </div>
                    </div>
                </div>

                <br>
                <!-- Cuenta B -->
                <h5>Cuenta B</h5>
                <div class="form-grid grid-3">
                    <div class="input-group">
                        <label for="edit_alias_b">Alias B</label>
                        <div class="input-icon">
                            <span class="material-icons">alternate_email</span>
                            <input type="text" name="alias_b" id="edit_alias_b" placeholder="Alias B">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_cbu_b">CBU B</label>
                        <div class="input-icon">
                            <span class="material-icons">account_balance</span>
                            <input type="text" name="cbu_b" id="edit_cbu_b" placeholder="CBU B">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_titular_b">Titular B</label>
                        <div class="input-icon">
                            <span class="material-icons">person</span>
                            <input type="text" name="titular_b" id="edit_titular_b" placeholder="Titular B">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_cuit_b">CUIT B</label>
                        <div class="input-icon">
                            <span class="material-icons">badge</span>
                            <input type="text" name="cuit_b" id="edit_cuit_b" placeholder="CUIT B">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_banco_b">Banco B</label>
                        <div class="input-icon">
                            <span class="material-icons">account_balance_wallet</span>
                            <input type="text" name="banco_b" id="edit_banco_b" placeholder="Banco B">
                        </div>
                    </div>
                </div>

                <br>
                <!-- Cuenta C -->
                <h5>Cuenta C</h5>
                <div class="form-grid grid-3">
                    <div class="input-group">
                        <label for="edit_alias_c">Alias C</label>
                        <div class="input-icon">
                            <span class="material-icons">alternate_email</span>
                            <input type="text" name="alias_c" id="edit_alias_c" placeholder="Alias C">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_cbu_c">CBU C</label>
                        <div class="input-icon">
                            <span class="material-icons">account_balance</span>
                            <input type="text" name="cbu_c" id="edit_cbu_c" placeholder="CBU C">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_titular_c">Titular C</label>
                        <div class="input-icon">
                            <span class="material-icons">person</span>
                            <input type="text" name="titular_c" id="edit_titular_c" placeholder="Titular C">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_cuit_c">CUIT C</label>
                        <div class="input-icon">
                            <span class="material-icons">badge</span>
                            <input type="text" name="cuit_c" id="edit_cuit_c" placeholder="CUIT C">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_banco_c">Banco C</label>
                        <div class="input-icon">
                            <span class="material-icons">account_balance_wallet</span>
                            <input type="text" name="banco_c" id="edit_banco_c" placeholder="Banco C">
                        </div>
                    </div>
                </div>

                <br>
                <hr>
                <br>

                <!-- Disciplina libre -->
                <h4>Disciplina libre</h4>
                <div class="input-group">
                    <label for="edit_disciplina_libre">Disciplina</label>
                    <div class="input-icon">
                        <span class="material-icons">sports</span>
                        <input type="text" name="disciplina_libre" id="edit_disciplina_libre" placeholder="Disciplina libre">
                    </div>
                </div>

                <!-- Botones -->
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
        let paginaActual = 1;

        function cargarUsuarios(pagina = 1) {
            const dni = document.getElementById("buscarCuit").value;
            const nombre = document.getElementById("buscarNombre").value;
            const nSocio = document.getElementById("buscarNSocio")?.value || "";

            fetch(`../../controllers/admin_altaUsuariosController.php?dni=${dni}&nombre=${nombre}&n_socio=${nSocio}&page=${pagina}`)
                .then(res => res.json())
                .then(data => {
                    const tabla = document.getElementById("tablaUsuarios");
                    tabla.innerHTML = "";

                    if (data.status === "success") {
                        if (data.data.length === 0) {
                            tabla.innerHTML = `<tr><td colspan="7">No hay resultados.</td></tr>`;
                            return;
                        }

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
        <button class="btn btn-icon btn-editar" title="Editar"><span class="material-icons" style="color: #2563eb;">edit</span></button>
        <button class="btn btn-icon btn-borrar" title="Borrar"><span class="material-icons" style="color: #dc2626;">delete</span></button>
    </td>
</tr>`;
                            tabla.innerHTML += fila;
                        });

                        renderPaginacion(data.page, data.pages);
                    } else {
                        tabla.innerHTML = `<tr><td colspan="7">Error al cargar usuarios.</td></tr>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                });
        }


        function renderPaginacion(pagina, totalPaginas) {
            const container = document.getElementById("paginacionUsuarios");
            if (!container) return;

            container.innerHTML = '';

            if (totalPaginas <= 1) return;

            const prevBtn = document.createElement("button");
            prevBtn.textContent = "Anterior";
            prevBtn.disabled = pagina === 1;
            prevBtn.onclick = () => {
                paginaActual--;
                cargarUsuarios(paginaActual);
            };

            const nextBtn = document.createElement("button");
            nextBtn.textContent = "Siguiente";
            nextBtn.disabled = pagina === totalPaginas;
            nextBtn.onclick = () => {
                paginaActual++;
                cargarUsuarios(paginaActual);
            };

            container.appendChild(prevBtn);

            const pageInfo = document.createElement("span");
            pageInfo.textContent = ` Página ${pagina} de ${totalPaginas} `;
            container.appendChild(pageInfo);

            container.appendChild(nextBtn);
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
        document.getElementById("buscarNSocio").addEventListener("input", cargarUsuarios);


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

        document.addEventListener("click", function(e) {
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

                            document.getElementById("edit_alias_b").value = u.bancarios.alias_b ?? '';
                            document.getElementById("edit_cbu_b").value = u.bancarios.cbu_b ?? '';
                            document.getElementById("edit_titular_b").value = u.bancarios.titular_b ?? '';
                            document.getElementById("edit_cuit_b").value = u.bancarios.cuit_b ?? '';
                            document.getElementById("edit_banco_b").value = u.bancarios.banco_b ?? '';

                            document.getElementById("edit_alias_c").value = u.bancarios.alias_c ?? '';
                            document.getElementById("edit_cbu_c").value = u.bancarios.cbu_c ?? '';
                            document.getElementById("edit_titular_c").value = u.bancarios.titular_c ?? '';
                            document.getElementById("edit_cuit_c").value = u.bancarios.cuit_c ?? '';
                            document.getElementById("edit_banco_c").value = u.bancarios.banco_c ?? '';

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
        document.getElementById("formEditarUsuario").addEventListener("submit", function(e) {
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