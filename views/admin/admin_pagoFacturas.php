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
                <div class="navbar-title">Inicio</div>
            </header>

            <!-- 📦 CONTENIDO -->
            <section class="content">

                <!-- Bienvenida -->
                <div class="card">
                    <h2>Hola 👋</h2>
                    <p>En esta página, vamos a tener KPI.</p>
                </div>

                <!-- Formulario para cargar un nuevo pago -->
                <div class="card">
                    <h2>Nuevo pago de evento</h2>
                    <form class="form-modern" action="../../controllers/admin_pagoFacturasController.php" method="POST" enctype="multipart/form-data" id="formPagoEvento">
                        <div class="form-grid grid-4">

                            <!-- DNI para buscar beneficiario -->
                            <div class="input-group">
                                <label for="dni_beneficiario">DNI del Beneficiario</label>
                                <div class="input-icon">
                                    <span class="material-icons">badge</span>
                                    <input type="text" name="dni_beneficiario" id="dni_beneficiario" required>
                                </div>
                            </div>

                            <!-- Nombre completo -->
                            <div class="input-group">
                                <label for="nombre_completo_beneficiario">Nombre completo</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="text" name="nombre_completo_beneficiario" id="nombre_completo_beneficiario" required readonly>
                                </div>
                            </div>

                            <!-- Campo oculto -->
                            <input type="hidden" name="usuario_id" id="usuario_id">

                            <!-- Selector de cuenta -->
                            <div class="input-group" id="selectorCuentaContainer" style="display: none;">
                                <label for="selectorCuenta">Seleccionar cuenta bancaria</label>
                                <select id="selectorCuenta" class="input" style="width: 100%;">
                                    <!-- Opciones dinámicas -->
                                </select>
                            </div>

                            <!-- Datos bancarios -->
                            <div class="input-group">
                                <label for="cuit_beneficiario">CUIT Beneficiario</label>
                                <div class="input-icon">
                                    <span class="material-icons">badge</span>
                                    <input type="text" name="cuit_beneficiario" id="cuit_beneficiario" required readonly>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="cbu_beneficiario">CBU Beneficiario</label>
                                <div class="input-icon">
                                    <span class="material-icons">credit_card</span>
                                    <input type="text" name="cbu_beneficiario" id="cbu_beneficiario" required readonly>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="alias_beneficiario">Alias Beneficiario</label>
                                <div class="input-icon">
                                    <span class="material-icons">alternate_email</span>
                                    <input type="text" name="alias_beneficiario" id="alias_beneficiario" readonly>
                                </div>
                            </div>

                            <!-- Fecha del evento -->
                            <div class="input-group">
                                <label for="fecha_evento">Fecha del Evento</label>
                                <div class="input-icon">
                                    <span class="material-icons">event</span>
                                    <input type="date" name="fecha_evento" id="fecha_evento" required>
                                </div>
                            </div>

                            <!-- Número de orden -->
                            <div class="input-group">
                                <label for="numero_orden">Número de orden</label>
                                <div class="input-icon">
                                    <span class="material-icons">confirmation_number</span>
                                    <input type="text" name="numero_orden" id="numero_orden" required>
                                </div>
                            </div>

                            <!-- Monto -->
                            <div class="input-group">
                                <label for="monto">Monto</label>
                                <div class="input-icon">
                                    <span class="material-icons">attach_money</span>
                                    <input type="number" step="0.01" name="monto" id="monto" required>
                                </div>
                            </div>

                            <!-- Descuento cuota (si aplica) -->
                            <div class="input-group" id="grupo_descuento_cuota" style="display: none;">
                                <label for="descuento_cuota">Descuento por Cuota Anual</label>
                                <div class="input-icon">
                                    <span class="material-icons">remove_circle</span>
                                    <input type="number" step="0.01" name="descuento_cuota" id="descuento_cuota" value="0.00">
                                </div>
                            </div>

                            <!-- Teléfono -->
                            <div class="input-group">
                                <label for="telefono_beneficiario">Teléfono</label>
                                <div class="input-icon">
                                    <span class="material-icons">call</span>
                                    <input type="text" name="telefono_beneficiario" id="telefono_beneficiario">
                                </div>
                            </div>

                            <!-- cargado por -->
                            <div class="input-group">
                                <label for="cargado_por_nombre">Cargado por</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="text" name="cargado_por_nombre" id="cargado_por_nombre" readonly>
                                </div>
                            </div>

                            <!-- Evento (nombre) -->
                            <div class="input-group">
                                <label for="evento">Evento</label>
                                <div class="input-icon">
                                    <span class="material-icons">event</span>
                                    <input type="text" name="evento" id="evento" required>
                                </div>
                            </div>

                            <!-- Sellado -->
                            <div class="input-group">
                                <label for="sellado">Sellado (%)</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input type="number" step="0.01" name="sellado" id="sellado" required>
                                </div>
                            </div>

                            <!-- Impuesto al cheque -->
                            <div class="input-group">
                                <label for="impuesto_cheque">Impuesto al Cheque (%)</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input type="number" step="0.01" name="impuesto_cheque" id="impuesto_cheque" required>
                                </div>
                            </div>

                            <!-- Retención -->
                            <div class="input-group">
                                <label for="retencion">Retención (%)</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input type="number" step="0.01" name="retencion" id="retencion" required>
                                </div>
                            </div>

                            <!-- Total final -->
                            <div class="input-group">
                                <label for="total_despues_impuestos">Total Final</label>
                                <div class="input-icon">
                                    <span class="material-icons">calculate</span>
                                    <input type="text" name="total_despues_impuestos" id="total_despues_impuestos" readonly required>
                                </div>
                            </div>

                            <!-- Pedido PDF -->
                            <div class="input-group">
                                <label for="pedido">Archivo Pedido</label>
                                <div class="input-icon">
                                    <span class="material-icons">upload_file</span>
                                    <input type="file" name="pedido" id="pedido" accept="application/pdf">
                                </div>
                            </div>

                            <!-- Factura PDF -->
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

                <!-- Alert -->
                <div class="alert-container" id="alertContainer"></div>

            </section>

        </div>
    </div>
    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        // ================================
        // 🔄 Cargar tabla de pagos al iniciar
        // ================================
        document.addEventListener("DOMContentLoaded", function() {
            cargarTablaPagos();
            document.getElementById('cargado_por_nombre').value = <?= json_encode($_SESSION['nombre']) ?>;

        });

        // ================================
        // 📊 Función: cargar tabla de pagos
        // ================================
        function cargarTablaPagos() {
            fetch('../../controllers/admin_pagoFacturasController.php?ajax=1')
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
                .catch(error => {
                    console.error('Error al cargar pagos:', error);
                });
        }

        // ================================
        // 🧮 Función: calcular total final
        // ================================
        function calcularTotal() {
            const monto = parseFloat(document.getElementById('monto').value) || 0;
            const sellado = parseFloat(document.getElementById('sellado').value) || 0;
            const cheque = parseFloat(document.getElementById('impuesto_cheque').value) || 0;
            const ret = parseFloat(document.getElementById('retencion').value) || 0;
            const cuota = parseFloat(document.getElementById('descuento_cuota')?.value) || 0;

            const total = monto -
                (monto * sellado / 100) -
                (monto * cheque / 100) -
                (monto * ret / 100) -
                cuota;

            document.getElementById('total_despues_impuestos').value = total.toFixed(2);
        }

        // Escuchar cambios en campos relacionados con el cálculo
        ['monto', 'sellado', 'impuesto_cheque', 'retencion', 'descuento_cuota'].forEach(id => {
            document.getElementById(id).addEventListener('input', calcularTotal);
        });

        // ================================
        // 📤 Envío del formulario (guardar pago)
        // ================================
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
                        showAlert('success', 'Pago guardado correctamente');
                        form.reset();
                        document.getElementById('total_despues_impuestos').value = '';
                        cargarTablaPagos();
                    } else {
                        showAlert('error', 'Hubo un problema al guardar:\n' + text);
                        console.error('Respuesta:', text);
                    }
                });
        });

        // ================================
        // 🔍 Buscar beneficiario por DNI
        // ================================
        document.getElementById('dni_beneficiario').addEventListener('blur', function() {
            const dni = this.value.trim();
            if (!dni) return;

            fetch(`../../controllers/admin_pagoFacturasController.php?buscarDni=${dni}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        showAlert('error', 'Verificá que el DNI esté registrado y tenga cuentas bancarias.');
                        document.getElementById('nombre_completo_beneficiario').value = '';
                        document.getElementById('usuario_id').value = '';
                        return;
                    }

                    // 👉 Nombre y usuario_id
                    document.getElementById('nombre_completo_beneficiario').value = data.nombre;
                    document.getElementById('usuario_id').value = data.usuario_id;
                    document.getElementById('telefono_beneficiario').value = data.telefono || '';

                    // 👉 Cuentas bancarias
                    const cuentas = data.cuentas;
                    const selector = document.getElementById('selectorCuenta');
                    const container = document.getElementById('selectorCuentaContainer');

                    selector.innerHTML = '';
                    if (cuentas.length > 1) {
                        container.style.display = 'block';
                        cuentas.forEach((cuenta, i) => {
                            const option = document.createElement('option');
                            option.value = i;
                            option.textContent = `${cuenta.banco} - ${cuenta.alias}`;
                            selector.appendChild(option);
                        });
                        selector.onchange = () => autocompletarCuenta(cuentas[selector.value]);
                        autocompletarCuenta(cuentas[0]);
                    } else if (cuentas.length === 1) {
                        container.style.display = 'none';
                        autocompletarCuenta(cuentas[0]);
                    }

                    // 👉 Verificación de cuota anual
                    const grupo = document.getElementById('grupo_descuento_cuota');
                    if (data.cuota_pagada) {
                        showAlert('success', `El socio ${data.nombre} tiene la cuota ${new Date().getFullYear()} pagada.`);
                        grupo.style.display = 'none';
                        document.getElementById('descuento_cuota').value = 0;
                    } else {
                        showAlert('error', `El socio ${data.nombre}, NO tiene la cuota ${new Date().getFullYear()} pagada. Podés descontarla en esta operación.`);
                        grupo.style.display = 'block';
                        document.getElementById('descuento_cuota').value = '';
                    }

                    calcularTotal(); // recalcular total si aplica cuota
                })
                .catch(err => {
                    console.error('Error consultando el DNI:', err);
                    showAlert('error', 'Error al buscar el beneficiario.');
                });
        });

        // ================================
        // 🧩 Función: autocompletar campos bancarios
        // ================================
        function autocompletarCuenta(cuenta) {
            if (!cuenta) return;
            document.getElementById('cbu_beneficiario').value = cuenta.cbu;
            document.getElementById('alias_beneficiario').value = cuenta.alias;
            document.getElementById('cuit_beneficiario').value = cuenta.cuit;
        }
    </script>


    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>