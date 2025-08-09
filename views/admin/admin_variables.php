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
                    <!-- ============== TARJETA 1: Débito/Crédito ============== -->
                    <div class="card">
                        <h3>Impuesto al débito y crédito</h3>

                        <form id="form-dc" class="form-grid" style="margin-top: 12px;">
                            <div class="form-group" style="max-width:320px;">
                                <label>Valor</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input type="text" name="value" placeholder="Ej: 1,2000" required>
                                </div>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-aceptar">Crear</button>
                            </div>
                        </form>

                        <div class="tabla-wrapper" style="margin-top: 16px;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Valor</th>
                                        <th>Creado</th>
                                        <th>Actualizado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-dc"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ============== TARJETA 2: Retención ============== -->
                    <div class="card">
                        <h3>Retención</h3>

                        <form id="form-ret" class="form-grid" style="margin-top: 12px;">
                            <div class="form-group" style="max-width:320px;">
                                <label>Valor</label>
                                <div class="input-icon">
                                    <span class="material-icons">payments</span>
                                    <input type="text" name="value" placeholder="Ej: 3,5000" required>
                                </div>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-aceptar">Crear</button>
                            </div>
                        </form>

                        <div class="tabla-wrapper" style="margin-top: 16px;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Valor</th>
                                        <th>Creado</th>
                                        <th>Actualizado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-ret"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ============== TARJETA 3: Entidades de facturación ============== -->
                    <div class="card">
                        <h3>Entidades de facturación</h3>

                        <form id="form-bill" class="form-grid" style="margin-top: 12px;">
                            <div class="form-grid grid-2" style="width:100%;max-width:720px;">
                                <div class="form-group">
                                    <label>Nombre</label>
                                    <div class="input-icon">
                                        <span class="material-icons">apartment</span>
                                        <input type="text" name="name" placeholder="Razón social" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>CUIT</label>
                                    <div class="input-icon">
                                        <span class="material-icons">badge</span>
                                        <input type="text" name="cuit" placeholder="CUIT (números o con guiones)" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-aceptar">Crear</button>
                            </div>
                        </form>

                        <div class="tabla-wrapper" style="margin-top: 16px;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>CUIT</th>
                                        <th>Creado</th>
                                        <th>Actualizado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-bill"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ====== MODALES ====== -->
                <!-- Editar valor: Débito/Crédito -->
                <div id="modalEditDC" class="modal hidden">
                    <div class="modal-content">
                        <h3>Editar valor (Débito/Crédito)</h3>
                        <form id="formEditDC">
                            <input type="hidden" name="id" id="edit_dc_id">
                            <div class="form-group" style="margin-top: 10px;">
                                <label>Valor</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input type="text" name="value" id="edit_dc_value" required>
                                </div>
                            </div>
                            <div class="form-buttons" style="margin-top: 18px;">
                                <button type="submit" class="btn btn-aceptar">Guardar</button>
                                <button type="button" class="btn btn-cancelar" onclick="closeModal('modalEditDC')">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Eliminar Débito/Crédito -->
                <div id="modalDelDC" class="modal hidden">
                    <div class="modal-content">
                        <h3>¿Eliminar valor?</h3>
                        <p>Esta acción no se puede deshacer.</p>
                        <input type="hidden" id="del_dc_id">
                        <div class="form-buttons" style="margin-top: 18px;">
                            <button class="btn btn-aceptar" onclick="confirmDelDC()">Eliminar</button>
                            <button class="btn btn-cancelar" onclick="closeModal('modalDelDC')">Cancelar</button>
                        </div>
                    </div>
                </div>

                <!-- Editar valor: Retención -->
                <div id="modalEditRet" class="modal hidden">
                    <div class="modal-content">
                        <h3>Editar valor (Retención)</h3>
                        <form id="formEditRet">
                            <input type="hidden" name="id" id="edit_ret_id">
                            <div class="form-group" style="margin-top: 10px;">
                                <label>Valor</label>
                                <div class="input-icon">
                                    <span class="material-icons">payments</span>
                                    <input type="text" name="value" id="edit_ret_value" required>
                                </div>
                            </div>
                            <div class="form-buttons" style="margin-top: 18px;">
                                <button type="submit" class="btn btn-aceptar">Guardar</button>
                                <button type="button" class="btn btn-cancelar" onclick="closeModal('modalEditRet')">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Eliminar Retención -->
                <div id="modalDelRet" class="modal hidden">
                    <div class="modal-content">
                        <h3>¿Eliminar valor?</h3>
                        <p>Esta acción no se puede deshacer.</p>
                        <input type="hidden" id="del_ret_id">
                        <div class="form-buttons" style="margin-top: 18px;">
                            <button class="btn btn-aceptar" onclick="confirmDelRet()">Eliminar</button>
                            <button class="btn btn-cancelar" onclick="closeModal('modalDelRet')">Cancelar</button>
                        </div>
                    </div>
                </div>

                <!-- Editar Entidad -->
                <div id="modalEditBill" class="modal hidden">
                    <div class="modal-content">
                        <h3>Editar entidad de facturación</h3>
                        <form id="formEditBill">
                            <input type="hidden" name="id" id="edit_bill_id">
                            <div class="form-grid grid-2" style="margin-top: 10px;">
                                <div class="form-group">
                                    <label>Nombre</label>
                                    <div class="input-icon">
                                        <span class="material-icons">apartment</span>
                                        <input type="text" name="name" id="edit_bill_name" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>CUIT</label>
                                    <div class="input-icon">
                                        <span class="material-icons">badge</span>
                                        <input type="text" name="cuit" id="edit_bill_cuit" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-buttons" style="margin-top: 18px;">
                                <button type="submit" class="btn btn-aceptar">Guardar</button>
                                <button type="button" class="btn btn-cancelar" onclick="closeModal('modalEditBill')">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Eliminar Entidad -->
                <div id="modalDelBill" class="modal hidden">
                    <div class="modal-content">
                        <h3>¿Eliminar entidad?</h3>
                        <p>Esta acción no se puede deshacer.</p>
                        <input type="hidden" id="del_bill_id">
                        <div class="form-buttons" style="margin-top: 18px;">
                            <button class="btn btn-aceptar" onclick="confirmDelBill()">Eliminar</button>
                            <button class="btn btn-cancelar" onclick="closeModal('modalDelBill')">Cancelar</button>
                        </div>
                    </div>
                </div>

                <!-- Contenedor de alertas -->
                <div class="alert-container" id="alertContainer"></div>

            </section>
        </div>
    </div>



    <!-- script de funcionalidades -->
    <script>
        const API = '/controllers/AdminVariablesController.php';

        async function apiList(type) {
            const r = await fetch(`${API}?type=${type}&action=list`, {
                credentials: 'same-origin'
            });
            return parseJsonOrThrow(r);
        }
        async function apiPost(type, action, payload) {
            const r = await fetch(`${API}?type=${type}&action=${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(payload),
                credentials: 'same-origin'
            });
            return parseJsonOrThrow(r);
        }

        // Helper defensivo: si viene HTML (404, PHP notice, etc.) lo muestra como error legible
        async function parseJsonOrThrow(response) {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch {
                throw new Error(`Respuesta no-JSON del servidor (HTTP ${response.status}).\n${text.slice(0, 200)}…`);
            }
        }

        // ===== Utilidades =====
        function showAlert(tipo, mensaje) {
            const contenedor = document.getElementById('alertContainer');
            const alerta = document.createElement('div');
            alerta.className = `toast ${tipo === 'success' ? 'success' : tipo === 'error' ? 'error' : 'info'}`;
            alerta.textContent = mensaje;
            // Fallback si tu página no trae el contenedor de toastify:
            if (!document.getElementById('toast-container')) {
                const tc = document.createElement('div');
                tc.id = 'toast-container';
                document.body.appendChild(tc);
            }
            document.getElementById('toast-container').appendChild(alerta);
            setTimeout(() => alerta.remove(), 4800);
        }

        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        const fmt = n => Number(n).toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 4
        });

        // ====== API helpers ======
        async function apiList(type) {
            const r = await fetch(`${API}?type=${type}&action=list`, {
                credentials: 'same-origin'
            });
            return r.json();
        }
        async function apiPost(type, action, payload) {
            const r = await fetch(`${API}?type=${type}&action=${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(payload),
                credentials: 'same-origin'
            });
            return r.json();
        }

        // ====== Cargar tablas ======
        async function cargarDC() {
            const tbody = document.getElementById('tabla-dc');
            tbody.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';
            try {
                const res = await apiList('debit_credit_tax');
                if (!res.ok) throw new Error(res.error || 'Error al listar');
                tbody.innerHTML = '';
                (res.data || []).forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
        <td>${row.id}</td>
        <td>${fmt(row.value)}</td>
        <td>${row.created_at ?? ''}</td>
        <td>${row.updated_at ?? ''}</td>
        <td>
          <button class="btn-icon" data-tooltip="Editar" onclick="editarDC(${row.id}, '${row.value}')">
            <i class="material-icons">edit</i>
          </button>
          <button class="btn-icon" data-tooltip="Eliminar" onclick="eliminarDC(${row.id})">
            <i class="material-icons" style="color:red;">delete</i>
          </button>
        </td>
      `;
                    tbody.appendChild(tr);
                });
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="5" style="color:red;">${e.message}</td></tr>`;
            }
        }

        async function cargarRet() {
            const tbody = document.getElementById('tabla-ret');
            tbody.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';
            try {
                const res = await apiList('retention');
                if (!res.ok) throw new Error(res.error || 'Error al listar');
                tbody.innerHTML = '';
                (res.data || []).forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
        <td>${row.id}</td>
        <td>${fmt(row.value)}</td>
        <td>${row.created_at ?? ''}</td>
        <td>${row.updated_at ?? ''}</td>
        <td>
          <button class="btn-icon" data-tooltip="Editar" onclick="editarRet(${row.id}, '${row.value}')">
            <i class="material-icons">edit</i>
          </button>
          <button class="btn-icon" data-tooltip="Eliminar" onclick="eliminarRet(${row.id})">
            <i class="material-icons" style="color:red;">delete</i>
          </button>
        </td>
      `;
                    tbody.appendChild(tr);
                });
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="5" style="color:red;">${e.message}</td></tr>`;
            }
        }

        async function cargarBill() {
            const tbody = document.getElementById('tabla-bill');
            tbody.innerHTML = '<tr><td colspan="6">Cargando...</td></tr>';
            try {
                const res = await apiList('billing_entity');
                if (!res.ok) throw new Error(res.error || 'Error al listar');
                tbody.innerHTML = '';
                (res.data || []).forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
        <td>${row.id}</td>
        <td>${row.name}</td>
        <td>${row.cuit}</td>
        <td>${row.created_at ?? ''}</td>
        <td>${row.updated_at ?? ''}</td>
        <td>
          <button class="btn-icon" data-tooltip="Editar" onclick="editarBill(${row.id}, ${JSON.stringify(row.name)}, ${JSON.stringify(row.cuit)})">
            <i class="material-icons">edit</i>
          </button>
          <button class="btn-icon" data-tooltip="Eliminar" onclick="eliminarBill(${row.id})">
            <i class="material-icons" style="color:red;">delete</i>
          </button>
        </td>
      `;
                    tbody.appendChild(tr);
                });
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="6" style="color:red;">${e.message}</td></tr>`;
            }
        }

        // ====== Crear (submit formularios) ======
        document.getElementById('form-dc').addEventListener('submit', async (e) => {
            e.preventDefault();
            const value = e.target.value.value.trim();
            try {
                const res = await apiPost('debit_credit_tax', 'create', {
                    value
                });
                if (!res.ok) throw new Error(res.error || 'Error al crear');
                e.target.reset();
                showAlert('success', 'Valor creado correctamente');
                cargarDC();
            } catch (err) {
                showAlert('error', err.message);
            }
        });

        document.getElementById('form-ret').addEventListener('submit', async (e) => {
            e.preventDefault();
            const value = e.target.value.value.trim();
            try {
                const res = await apiPost('retention', 'create', {
                    value
                });
                if (!res.ok) throw new Error(res.error || 'Error al crear');
                e.target.reset();
                showAlert('success', 'Valor creado correctamente');
                cargarRet();
            } catch (err) {
                showAlert('error', err.message);
            }
        });

        document.getElementById('form-bill').addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = e.target.name.value.trim();
            const cuit = e.target.cuit.value.trim();
            try {
                const res = await apiPost('billing_entity', 'create', {
                    name,
                    cuit
                });
                if (!res.ok) throw new Error(res.error || 'Error al crear');
                e.target.reset();
                showAlert('success', 'Entidad creada correctamente');
                cargarBill();
            } catch (err) {
                showAlert('error', err.message);
            }
        });

        // ====== Editar (abrir modales) ======
        function editarDC(id, value) {
            document.getElementById('edit_dc_id').value = id;
            document.getElementById('edit_dc_value').value = value;
            openModal('modalEditDC');
        }

        function editarRet(id, value) {
            document.getElementById('edit_ret_id').value = id;
            document.getElementById('edit_ret_value').value = value;
            openModal('modalEditRet');
        }

        function editarBill(id, name, cuit) {
            document.getElementById('edit_bill_id').value = id;
            document.getElementById('edit_bill_name').value = name;
            document.getElementById('edit_bill_cuit').value = cuit;
            openModal('modalEditBill');
        }

        // ====== Guardar edición ======
        document.getElementById('formEditDC').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('edit_dc_id').value;
            const value = document.getElementById('edit_dc_value').value.trim();
            try {
                const res = await apiPost('debit_credit_tax', 'update', {
                    id,
                    value
                });
                if (!res.ok) throw new Error(res.error || 'Error al actualizar');
                closeModal('modalEditDC');
                showAlert('success', 'Valor actualizado');
                cargarDC();
            } catch (err) {
                showAlert('error', err.message);
            }
        });

        document.getElementById('formEditRet').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('edit_ret_id').value;
            const value = document.getElementById('edit_ret_value').value.trim();
            try {
                const res = await apiPost('retention', 'update', {
                    id,
                    value
                });
                if (!res.ok) throw new Error(res.error || 'Error al actualizar');
                closeModal('modalEditRet');
                showAlert('success', 'Valor actualizado');
                cargarRet();
            } catch (err) {
                showAlert('error', err.message);
            }
        });

        document.getElementById('formEditBill').addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('edit_bill_id').value;
            const name = document.getElementById('edit_bill_name').value.trim();
            const cuit = document.getElementById('edit_bill_cuit').value.trim();
            try {
                const res = await apiPost('billing_entity', 'update', {
                    id,
                    name,
                    cuit
                });
                if (!res.ok) throw new Error(res.error || 'Error al actualizar');
                closeModal('modalEditBill');
                showAlert('success', 'Entidad actualizada');
                cargarBill();
            } catch (err) {
                showAlert('error', err.message);
            }
        });

        // ====== Eliminar (abrir modales) ======
        function eliminarDC(id) {
            document.getElementById('del_dc_id').value = id;
            openModal('modalDelDC');
        }

        function eliminarRet(id) {
            document.getElementById('del_ret_id').value = id;
            openModal('modalDelRet');
        }

        function eliminarBill(id) {
            document.getElementById('del_bill_id').value = id;
            openModal('modalDelBill');
        }

        // ====== Confirmar eliminar ======
        async function confirmDelDC() {
            const id = document.getElementById('del_dc_id').value;
            try {
                const res = await apiPost('debit_credit_tax', 'delete', {
                    id
                });
                if (!res.ok) throw new Error(res.error || 'Error al eliminar');
                closeModal('modalDelDC');
                showAlert('success', 'Eliminado correctamente');
                cargarDC();
            } catch (err) {
                showAlert('error', err.message);
            }
        }
        async function confirmDelRet() {
            const id = document.getElementById('del_ret_id').value;
            try {
                const res = await apiPost('retention', 'delete', {
                    id
                });
                if (!res.ok) throw new Error(res.error || 'Error al eliminar');
                closeModal('modalDelRet');
                showAlert('success', 'Eliminado correctamente');
                cargarRet();
            } catch (err) {
                showAlert('error', err.message);
            }
        }
        async function confirmDelBill() {
            const id = document.getElementById('del_bill_id').value;
            try {
                const res = await apiPost('billing_entity', 'delete', {
                    id
                });
                if (!res.ok) throw new Error(res.error || 'Error al eliminar');
                closeModal('modalDelBill');
                showAlert('success', 'Entidad eliminada');
                cargarBill();
            } catch (err) {
                showAlert('error', err.message);
            }
        }

        // ====== Inicial ======
        document.addEventListener('DOMContentLoaded', () => {
            cargarDC();
            cargarRet();
            cargarBill();
        });
    </script>



    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>