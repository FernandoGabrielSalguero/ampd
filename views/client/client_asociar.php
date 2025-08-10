<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../core/SessionManager.php';
SessionManager::start();

$user = SessionManager::getUser();
if (!$user) {
    header("Location: /index.php?expired=1");
    exit;
}
if (!isset($user['role']) || $user['role'] !== 'Administrativo') {
    die("🚫 Acceso restringido: esta página es solo para usuarios Administrativo.");
}
$usuario = $user['username'] ?? 'Sin usuario';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AMPD</title>

    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <!-- Framework Success -->
    <link rel="stylesheet" href="https://www.fernandosalguero.com/cdn/assets/css/framework.css">
    <script src="https://www.fernandosalguero.com/cdn/assets/javascript/framework.js" defer></script>

    <style>
        /* Tabla con scroll interno y 5 filas visibles */
        .tabla-card .tabla-wrapper {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #fff;
            box-shadow: 0 2px 0 rgba(0, 0, 0, .04);
        }

        .data-table thead th,
        .data-table tbody td {
            padding: 12px 14px;
            white-space: nowrap;
        }

        .data-table tbody {
            display: block;
            max-height: 320px;
            /* ~8-10 filas según fuente; ajustable */
            overflow-y: auto;
        }

        .data-table thead,
        .data-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .data-table tbody tr:hover {
            background: #fafafa;
        }

        .acciones .btn {
            margin-right: 6px;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper .material-icons {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            opacity: .7;
        }

        .input-icon-wrapper input {
            padding-left: 36px !important;
        }
    </style>
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
                        <span class="material-icons" style="color:#5b21b6;">home</span><span class="link-text">Inicio</span>
                    </li>
                    <li onclick="location.href='client_asociar.php'">
                        <span class="material-icons" style="color:#5b21b6;">person_add</span><span class="link-text">Registrar Socio</span>
                    </li>
                    <li onclick="location.href='client_pagoFacturas.php'">
                        <span class="material-icons" style="color:#5b21b6;">attach_money</span><span class="link-text">Pago Facturas</span>
                    </li>
                    <li onclick="location.href='client_ListadoPagos.php'">
                        <span class="material-icons" style="color:#5b21b6;">assignment_turned_in</span><span class="link-text">Listado de pagos</span>
                    </li>
                    <li onclick="location.href='../../../logout.php'">
                        <span class="material-icons" style="color:red;">logout</span><span class="link-text">Salir</span>
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
                <button class="btn-icon" onclick="toggleSidebar()"><span class="material-icons">menu</span></button>
                <div class="navbar-title">Registrar Socio</div>
            </header>

            <section class="content">
                <div class="card">
                    <h2>Hola 👋 <?= htmlspecialchars($usuario) ?></h2>
                    <p>En esta página vas a poder registrar un nuevo socio y gestionar sus datos.</p>
                </div>

                <!-- Formulario: Alta de socio -->
                <div class="card">
                    <h2>Formulario para dar de alta un nuevo socio</h2>

                    <form class="form-modern" id="form-alta" method="post" novalidate>
                        <div class="form-grid grid-4">
                            <!-- Nombre -->
                            <div class="input-group">
                                <label for="nombre">Nombre completo</label>
                                <div class="input-icon">
                                    <span class="material-icons">badge</span>
                                    <input type="text" id="nombre" name="nombre" placeholder="Juan Pérez" required autocomplete="name" />
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="input-group">
                                <label for="email">Correo electrónico</label>
                                <div class="input-icon">
                                    <span class="material-icons">alternate_email</span>
                                    <input type="email" id="email" name="email" placeholder="usuario@correo.com" required autocomplete="email" />
                                </div>
                            </div>

                            <!-- Teléfono -->
                            <div class="input-group">
                                <label for="telefono">Teléfono</label>
                                <div class="input-icon">
                                    <span class="material-icons">phone</span>
                                    <input type="tel" id="telefono" name="telefono" placeholder="+54 9 387 ..." required autocomplete="tel" />
                                </div>
                            </div>

                            <!-- DNI -->
                            <div class="input-group">
                                <label for="dni">DNI</label>
                                <div class="input-icon">
                                    <span class="material-icons">credit_card</span>
                                    <input type="text" id="dni" name="dni" inputmode="numeric" pattern="^\d{6,10}$" maxlength="10" placeholder="Solo números" required />
                                </div>
                            </div>

                            <!-- CUIT -->
                            <div class="input-group">
                                <label for="cuit">CUIT</label>
                                <div class="input-icon">
                                    <span class="material-icons">assignment_ind</span>
                                    <input type="text" id="cuit" name="cuit" inputmode="numeric" pattern="^\d{11}$" maxlength="11" placeholder="11 dígitos" />
                                </div>
                            </div>

                            <!-- CBU -->
                            <div class="input-group">
                                <label for="cbu">CBU</label>
                                <div class="input-icon">
                                    <span class="material-icons">account_balance</span>
                                    <input type="text" id="cbu" name="cbu" inputmode="numeric" pattern="^\d{22}$" maxlength="22" placeholder="22 dígitos" />
                                </div>
                            </div>

                            <!-- Alias -->
                            <div class="input-group">
                                <label for="alias">Alias</label>
                                <div class="input-icon">
                                    <span class="material-icons">alternate_email</span>
                                    <input type="text" id="alias" name="alias" placeholder="mi.alias.banco" />
                                </div>
                            </div>

                            <!-- Titular -->
                            <div class="input-group">
                                <label for="titular_cuenta">Titular de la cuenta</label>
                                <div class="input-icon">
                                    <span class="material-icons">person</span>
                                    <input type="text" id="titular_cuenta" name="titular_cuenta" placeholder="Nombre del titular" />
                                </div>
                            </div>

                        </div>

                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">
                                <span class="material-icons" style="vertical-align:middle">save</span>
                                <span>Asociar</span>
                            </button>
                        </div>
                    </form>
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

    <!-- spinner global -->
    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        const API = '../../controllers/client_asociarController.php';

        // Helpers UI
        function showAlert(type, message) {
            // tipos: success | warning | danger | info
            const container = document.getElementById('alertContainer');
            const el = document.createElement('div');
            el.className = `alert ${type}`;
            el.textContent = message;
            container.appendChild(el);
            setTimeout(() => el.remove(), 4000);
        }

        function badge(html, kind) {
            const cls = kind === 'success' ? 'badge success' : (kind === 'warning' ? 'badge warning' : 'badge');
            return `<span class="${cls}">${html}</span>`;
        }

        // Alta socio
        document.getElementById('form-alta').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.submitter || e.target.querySelector('button[type="submit"]');
            btn.disabled = true;

            const payload = {
                action: 'create',
                nombre: document.getElementById('nombre').value.trim(),
                email: document.getElementById('email').value.trim(),
                telefono: document.getElementById('telefono').value.trim(),
                dni: document.getElementById('dni').value.trim(),
                cuit: document.getElementById('cuit').value.trim(),
                cbu: document.getElementById('cbu').value.trim(),
                alias: document.getElementById('alias').value.trim(),
                titular_cuenta: document.getElementById('titular_cuenta').value.trim()
            };

            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Error al guardar');
                showAlert('success', 'Socio creado correctamente');
                e.target.reset();
                await cargarSocios(); // refresca tabla
            } catch (err) {
                console.error(err);
                showAlert('danger', err.message);
            } finally {
                btn.disabled = false;
            }
        });

        // Filtros: al tipear, recargo
        document.getElementById('search_nombre').addEventListener('input', cargarSocios);
        document.getElementById('search_dni').addEventListener('input', cargarSocios);

        async function cargarSocios() {
            const filtros = {
                action: 'list',
                search_nombre: document.getElementById('search_nombre').value.trim(),
                search_dni: document.getElementById('search_dni').value.trim()
            };
            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(filtros)
                });
                const {
                    success,
                    rows
                } = await res.json();
                if (!success) throw new Error('No se pudo obtener el listado');
                pintarTabla(rows || []);
            } catch (err) {
                console.error(err);
                showAlert('danger', 'Error al cargar socios');
            }
        }

        function pintarTabla(rows) {
            const tb = document.getElementById('tbody-socios');
            tb.innerHTML = '';
            for (const r of rows) {
                const estadoBancario = r.has_bank ? badge('Cuentas registradas', 'success') : badge('Sin datos bancarios', 'warning');
                const estadoCuotas = r.fee_paid ? badge(`Cuota ${r.fee_year} paga`, 'success') : badge('Cuota no paga', 'warning');

                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td>${r.first_name || '-'}</td>
            <td>${r.dni || '-'}</td>
            <td>${estadoBancario}</td>
            <td>${estadoCuotas}</td>
<td class="acciones">
<button class="btn" data-action="edit" data-id="${r.user_id}">
    <span class="material-icons">edit</span>
</button>
<button class="btn success" data-action="pay" data-id="${r.user_id}">
    <span class="material-icons">payments</span>
</button>
<button class="btn danger" data-action="delete" data-id="${r.user_id}">
    <span class="material-icons">delete</span>
</button>
</td>
        `;
                tb.appendChild(tr);
            }
        }

        // Delegación eventos botones acciones
        document.getElementById('tbody-socios').addEventListener('click', async (e) => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const action = btn.dataset.action;
            const user_id = btn.dataset.id;

            if (action === 'delete') {
                if (!confirm('¿Eliminar este usuario y sus datos asociados?')) return;
                try {
                    const res = await fetch(API, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            user_id
                        })
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.message || 'No se pudo eliminar');
                    showAlert('success', 'Usuario eliminado');
                    await cargarSocios();
                } catch (err) {
                    console.error(err);
                    showAlert('danger', err.message);
                }
            } else if (action === 'edit') {
                // En esta entrega dejamos el hook listo; en la próxima te paso modal/edición completa.
                showAlert('info', 'Edición: pronto activamos el formulario completo de edición.');
            }
        });

        // Primera carga
        cargarSocios();

        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // hook botones de acciones
        document.getElementById('tbody-socios').addEventListener('click', async (e) => {
            const btn = e.target.closest('button[data-action]');
            if (!btn) return;
            const action = btn.dataset.action;
            const user_id = btn.dataset.id;

            if (action === 'delete') {
                document.getElementById('delete_user_id').value = user_id;
                openModal('modalEliminar');
            }
            if (action === 'edit') {
                // pedir datos
                try {
                    const res = await fetch(API, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'get',
                            user_id
                        })
                    });
                    const {
                        success,
                        row
                    } = await res.json();
                    if (!success) throw new Error('No se pudo cargar el usuario');
                    // setear campos
                    document.getElementById('edit_user_id').value = row.user_id;
                    document.getElementById('edit_dni').value = row.dni || '';
                    document.getElementById('edit_email').value = row.email || '';
                    document.getElementById('edit_role').value = row.role_name || 'Socio';
                    document.getElementById('edit_nombre').value = row.first_name || '';
                    document.getElementById('edit_telefono').value = row.phone || '';
                    document.getElementById('edit_cuit').value = row.cuit || '';
                    document.getElementById('edit_cbu').value = row.cbu || '';
                    document.getElementById('edit_alias').value = row.alias || '';
                    document.getElementById('edit_titular').value = row.titular || '';
                    openModal('modalEditar');
                } catch (err) {
                    console.error(err);
                    showAlert('danger', err.message);
                }
            }
            if (action === 'pay') {
                document.getElementById('pay_user_id').value = user_id;
                document.getElementById('pay_year').value = (new Date()).getFullYear();
                document.getElementById('pay_fecha').value = '';
                openModal('modalPago');
            }
        });

        // Confirmación eliminar
        document.getElementById('btnConfirmDelete').addEventListener('click', async () => {
            const user_id = document.getElementById('delete_user_id').value;
            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        user_id
                    })
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'No se pudo eliminar');
                showAlert('success', 'Usuario eliminado');
                closeModal('modalEliminar');
                await cargarSocios();
            } catch (err) {
                console.error(err);
                showAlert('danger', err.message);
            }
        });

        // Submit edición
        document.getElementById('formEditarSocio').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                action: 'update',
                user_id: document.getElementById('edit_user_id').value,
                dni: document.getElementById('edit_dni').value.trim(),
                email: document.getElementById('edit_email').value.trim(),
                role: document.getElementById('edit_role').value,
                first_name: document.getElementById('edit_nombre').value.trim(),
                phone: document.getElementById('edit_telefono').value.trim(),
                cuit: document.getElementById('edit_cuit').value.trim(),
                cbu: document.getElementById('edit_cbu').value.trim(),
                alias: document.getElementById('edit_alias').value.trim(),
                titular: document.getElementById('edit_titular').value.trim()
            };
            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'No se pudo actualizar');
                showAlert('success', 'Datos actualizados');
                closeModal('modalEditar');
                await cargarSocios();
            } catch (err) {
                console.error(err);
                showAlert('danger', err.message);
            }
        });

        // Submit pago
        document.getElementById('formPago').addEventListener('submit', async (e) => {
            e.preventDefault();
            const payload = {
                action: 'pay',
                user_id: document.getElementById('pay_user_id').value,
                year: parseInt(document.getElementById('pay_year').value, 10),
                paid_at: document.getElementById('pay_fecha').value || null
            };
            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'No se pudo registrar el pago');
                showAlert('success', 'Pago registrado');
                closeModal('modalPago');
                await cargarSocios();
            } catch (err) {
                console.error(err);
                showAlert('danger', err.message);
            }
        });


        // Debug de sesión si querés
        // console.log(<?php //echo json_encode($_SESSION); 
                        ?>);
    </script>

    <!-- MODAL: Editar usuario -->
    <div id="modalEditar" class="modal hidden">
        <div class="modal-content card">
            <h3>Editar Socio</h3>
            <form id="formEditarSocio">
                <input type="hidden" id="edit_user_id" name="user_id">

                <div class="form-grid grid-3">
                    <!-- Users -->
                    <div class="input-group">
                        <label for="edit_dni">DNI (login)</label>
                        <div class="input-icon">
                            <span class="material-icons">credit_card</span>
                            <input type="text" id="edit_dni" name="dni" required inputmode="numeric" pattern="^\d{6,10}$" maxlength="10">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_email">Email</label>
                        <div class="input-icon">
                            <span class="material-icons">alternate_email</span>
                            <input type="email" id="edit_email" name="email" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_role">Rol</label>
                        <div class="input-icon">
                            <span class="material-icons">security</span>
                            <select id="edit_role" name="role" required>
                                <option value="Socio">Socio</option>
                                <option value="Administrativo">Administrativo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Profile -->
                    <div class="input-group">
                        <label for="edit_nombre">Nombre</label>
                        <div class="input-icon">
                            <span class="material-icons">badge</span>
                            <input type="text" id="edit_nombre" name="first_name" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_telefono">Teléfono</label>
                        <div class="input-icon">
                            <span class="material-icons">phone</span>
                            <input type="tel" id="edit_telefono" name="phone">
                        </div>
                    </div>

                    <!-- Bank -->
                    <div class="input-group">
                        <label for="edit_cuit">CUIT</label>
                        <div class="input-icon">
                            <span class="material-icons">assignment_ind</span>
                            <input type="text" id="edit_cuit" name="cuit" inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_cbu">CBU</label>
                        <div class="input-icon">
                            <span class="material-icons">account_balance</span>
                            <input type="text" id="edit_cbu" name="cbu" inputmode="numeric" pattern="^\d{22}$" maxlength="22">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_alias">Alias</label>
                        <div class="input-icon">
                            <span class="material-icons">alternate_email</span>
                            <input type="text" id="edit_alias" name="alias">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="edit_titular">Titular</label>
                        <div class="input-icon">
                            <span class="material-icons">person</span>
                            <input type="text" id="edit_titular" name="titular">
                        </div>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-aceptar">Guardar</button>
                    <button type="button" class="btn btn-cancelar" onclick="closeModal('modalEditar')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Alta de pago -->
    <div id="modalPago" class="modal hidden">
        <div class="modal-content card">
            <h3>Registrar pago de cuota</h3>
            <form id="formPago">
                <input type="hidden" id="pay_user_id" name="user_id">
                <div class="form-grid grid-2">
                    <div class="input-group">
                        <label for="pay_year">Año</label>
                        <div class="input-icon">
                            <span class="material-icons">event</span>
                            <input type="number" id="pay_year" name="year" min="2000" max="2100" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="pay_fecha">Fecha de pago</label>
                        <div class="input-icon">
                            <span class="material-icons">schedule</span>
                            <input type="datetime-local" id="pay_fecha" name="paid_at">
                        </div>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn btn-aceptar">Registrar</button>
                    <button type="button" class="btn btn-cancelar" onclick="closeModal('modalPago')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Confirmar eliminación -->
    <div id="modalEliminar" class="modal hidden">
        <div class="modal-content card">
            <h3>Eliminar socio</h3>
            <p>¿Seguro que deseas eliminar este usuario y toda su información asociada?</p>
            <input type="hidden" id="delete_user_id">
            <div class="form-buttons">
                <button type="button" class="btn danger" id="btnConfirmDelete">Eliminar</button>
                <button type="button" class="btn btn-cancelar" onclick="closeModal('modalEliminar')">Cancelar</button>
            </div>
        </div>
    </div>

</body>

</html>