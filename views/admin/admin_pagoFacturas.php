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
                    <!-- <li onclick="location.href='admin_dashboard.php'">
                        <span class="material-icons" style="color: #5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='admin_altaUsuarios.php'">
                        <span class="material-icons" style="color: #5b21b6;">person</span><span class="link-text">Alta usuarios</span>
                    </li> -->
                    <li onclick="location.href='admin_pagoFacturas.php'">
                        <span class="material-icons" style="color: #5b21b6;">upload_file</span><span class="link-text">Pago Facturas</span>
                    </li>
                    <!-- <li onclick="location.href='admin_Eventos.php'">
                        <span class="material-icons" style="color: #5b21b6;">nightlife</span><span class="link-text">Eventos</span>
                    </li>
                    <li onclick="location.href='admin_sucripciones.php'">
                        <span class="material-icons" style="color: #5b21b6;">assignment</span><span class="link-text">Suscripciones</span>
                    </li>
                    <li onclick="location.href='admin_consumoInternos.php'">
                        <span class="material-icons" style="color: #5b21b6;">shopping_cart</span><span class="link-text">Consumos internos</span>
                    </li> -->
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

                <div class="card">
                    <h2>Nuevo pago de evento</h2>
                    <form class="form-modern" action="../../controllers/admin_PagoEventoController.php" method="POST" enctype="multipart/form-data" id="formPagoEvento">
                        <div class="form-grid grid-4">

                            <div class="input-group">
                                <label for="nombre_completo_beneficiario">Nombre completo</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="text" name="nombre_completo_beneficiario" id="nombre_completo_beneficiario" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="cuit_beneficiario">CUIT Beneficiario</label>
                                <div class="input-icon">
                                    <span class="material-icons">badge</span>
                                    <input type="text" name="cuit_beneficiario" id="cuit_beneficiario" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="cbu_beneficiario">CBU Beneficiario</label>
                                <div class="input-icon">
                                    <span class="material-icons">credit_card</span>
                                    <input type="text" name="cbu_beneficiario" id="cbu_beneficiario" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="alias_beneficiario">Alias Beneficiario</label>
                                <div class="input-icon">
                                    <span class="material-icons">alternate_email</span>
                                    <input type="text" name="alias_beneficiario" id="alias_beneficiario">
                                </div>
                            </div>



                            <div class="input-group">
                                <label for="telefono_beneficiario">Teléfono</label>
                                <div class="input-icon">
                                    <span class="material-icons">call</span>
                                    <input type="text" name="telefono_beneficiario" id="telefono_beneficiario">
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="evento">Evento</label>
                                <div class="input-icon">
                                    <span class="material-icons">event</span>
                                    <input type="text" name="evento" id="evento" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="monto">Monto</label>
                                <div class="input-icon">
                                    <span class="material-icons">attach_money</span>
                                    <input type="number" step="0.01" name="monto" id="monto" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="monto">Número de orden</label>
                                <div class="input-icon">
                                    <span class="material-icons">number</span>
                                    <input type="number" step="0.01" name="monto" id="monto" required>
                                </div>
                            </div>


                            <div class="input-group">
                                <label for="sellado">Sellado (%)</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input type="number" step="0.01" name="sellado" id="sellado" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="impuesto_cheque">Impuesto al Cheque (%)</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input type="number" step="0.01" name="impuesto_cheque" id="impuesto_cheque" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="retencion">Retención (%)</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input type="number" step="0.01" name="retencion" id="retencion" required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="total_despues_impuestos">Total Final</label>
                                <div class="input-icon">
                                    <span class="material-icons">calculate</span>
                                    <input type="text" name="total_despues_impuestos" id="total_despues_impuestos" readonly required>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="pedido">Archivo Pedido</label>
                                <div class="input-icon">
                                    <span class="material-icons">upload_file</span>
                                    <input type="file" name="pedido" id="pedido" accept="application/pdf">
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="factura">Archivo Factura</label>
                                <div class="input-icon">
                                    <span class="material-icons">upload_file</span>
                                    <input type="file" name="factura" id="factura" accept="application/pdf">
                                </div>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <button type="submit" class="btn btn-aceptar">
                                <span class="material-icons">save</span> Guardar pago
                            </button>
                        </div>

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
                    <h2>Listado de pagos de eventos</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Beneficiario</th>
                                    <th>Evento</th>
                                    <th>Monto</th>
                                    <th>Sellado</th>
                                    <th>Impuesto Cheque</th>
                                    <th>Retención</th>
                                    <th>Total</th>
                                    <th>Comprobantes</th>
                                </tr>
                            </thead>
                            <tbody id="tablaPagoFacturas">
                                <!-- Rellenado con JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            fetch('../../controllers/admin_PagoEventoController.php?ajax=1')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('tablaPagoFacturas');
                    tbody.innerHTML = '';

                    data.forEach(pago => {
                        const fila = document.createElement('tr');

                        fila.innerHTML = `
                    <td>${pago.id}</td>
                    <td>${pago.beneficiario}</td>
                    <td>${pago.contrato}</td>
                    <td>$${parseFloat(pago.monto).toFixed(2)}</td>
                    <td>${pago.retencion ?? '-'}</td>
                    <td>${pago.fecha_solicitud}</td>
                    <td>${pago.estado}</td>
                    <td>
                        <a href="${pago.pedido}" target="_blank">Pedido</a> | 
                        <a href="${pago.factura}" target="_blank">Factura</a>
                    </td>
                `;

                        tbody.appendChild(fila);
                    });
                })
                .catch(error => {
                    console.error('Error al cargar pagos:', error);
                });
        });


        function calcularTotal() {
            const monto = parseFloat(document.getElementById('monto').value) || 0;
            const sellado = parseFloat(document.getElementById('sellado').value) || 0;
            const cheque = parseFloat(document.getElementById('impuesto_cheque').value) || 0;
            const ret = parseFloat(document.getElementById('retencion').value) || 0;

            const total = monto - (monto * sellado / 100) - (monto * cheque / 100) - (monto * ret / 100);
            document.getElementById('total_despues_impuestos').value = total.toFixed(2);
        }

        ['monto', 'sellado', 'impuesto_cheque', 'retencion'].forEach(id => {
            document.getElementById(id).addEventListener('input', calcularTotal);
        });

        document.addEventListener("DOMContentLoaded", function() {
            fetch('../../controllers/admin_PagoEventoController.php?ajax=1')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('tablaPagoFacturas');
                    tbody.innerHTML = '';

                    data.forEach(pago => {
                        const fila = document.createElement('tr');
                        fila.innerHTML = `
                    <td>${pago.id_}</td>
                    <td>${pago.fecha}</td>
                    <td>${pago.nombre_completo_beneficiario}</td>
                    <td>${pago.evento}</td>
                    <td>$${parseFloat(pago.monto).toFixed(2)}</td>
                    <td>${pago.sellado}%</td>
                    <td>${pago.impuesto_cheque}%</td>
                    <td>${pago.retencion}%</td>
                    <td><strong>$${parseFloat(pago.total_despues_impuestos).toFixed(2)}</strong></td>
                    <td>
                        <a href="${pago.pedido}" target="_blank">📄 Pedido</a> |
                        <a href="${pago.factura}" target="_blank">📄 Factura</a>
                    </td>
                `;
                        tbody.appendChild(fila);
                    });
                })
                .catch(err => {
                    console.error('Error al cargar pagos:', err);
                });
        });


        document.getElementById('formPagoEvento').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);

            fetch(form.action, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(text => {
                    if (text.trim() === 'ok') {
                        alert('✅ Pago guardado correctamente');
                        form.reset();
                        document.getElementById('total_despues_impuestos').value = '';
                        cargarTablaPagos();
                    } else {
                        alert('❌ Hubo un problema al guardar:\n' + text);
                        console.error('Respuesta:', text);
                    }
                })
        });

        // Función separada para reutilizar
        function cargarTablaPagos() {
            fetch('../../controllers/admin_PagoEventoController.php?ajax=1')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('tablaPagoFacturas');
                    tbody.innerHTML = '';
                    data.forEach(pago => {
                        const fila = document.createElement('tr');
                        fila.innerHTML = `
                    <td>${pago.id_}</td>
                    <td>${pago.fecha}</td>
                    <td>${pago.nombre_completo_beneficiario}</td>
                    <td>${pago.evento}</td>
                    <td>$${parseFloat(pago.monto).toFixed(2)}</td>
                    <td>${pago.sellado}%</td>
                    <td>${pago.impuesto_cheque}%</td>
                    <td>${pago.retencion}%</td>
                    <td><strong>$${parseFloat(pago.total_despues_impuestos).toFixed(2)}</strong></td>
                    <td>
                        <a href="${pago.pedido}" target="_blank">📄 Pedido</a> |
                        <a href="${pago.factura}" target="_blank">📄 Factura</a>
                    </td>
                `;
                        tbody.appendChild(fila);
                    });
                });
        }
    </script>

    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>