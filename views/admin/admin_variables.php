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
                    <!-- Tarjeta 1: Débito/Crédito -->
                    <div class="card">
                        <h3>Variable de entorno Impuesto al débito y crédito</h3>
                        <form id="form-dc" class="form-inline gap-2 mt-2">
                            <input type="text" class="input" name="value" placeholder="Valor (ej. 1,2000)" required>
                            <button class="btn btn-primary">Crear</button>
                        </form>
                        <div id="list-dc" class="mt-3"></div>
                    </div>

                    <!-- Tarjeta 2: Retención -->
                    <div class="card">
                        <h3>Variable de entorno Retención</h3>
                        <form id="form-ret" class="form-inline gap-2 mt-2">
                            <input type="text" class="input" name="value" placeholder="Valor (ej. 3,5000)" required>
                            <button class="btn btn-primary">Crear</button>
                        </form>
                        <div id="list-ret" class="mt-3"></div>
                    </div>

                    <!-- Tarjeta 3: Entidad de facturación -->
                    <div class="card">
                        <h3>Variable de entorno Entidad facturación</h3>
                        <form id="form-bill" class="form-grid mt-2">
                            <div class="grid grid-2 gap-2">
                                <input type="text" class="input" name="name" placeholder="Nombre" required>
                                <input type="text" class="input" name="cuit" placeholder="CUIT" required>
                            </div>
                            <button class="btn btn-primary mt-2">Crear</button>
                        </form>
                        <div id="list-bill" class="mt-3"></div>
                    </div>
                </div>


            </section>
        </div>
    </div>



    <!-- script de funcionalidades -->
    <script>
        const API = 'AdminVariablesController.php';

        // ==== Funciones comunes ====
        const fmt = n => Number(n).toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 4
        });

        function renderTable(container, rows, cols, editHandler, deleteHandler) {
            if (!rows.length) {
                container.innerHTML = '<p class="text-gray-500">No hay registros</p>';
                return;
            }
            const table = document.createElement('table');
            table.className = 'table';
            table.innerHTML = `
    <thead>
      <tr>
        ${cols.map(c => `<th>${c.label}</th>`).join('')}
        <th style="width:120px">Acciones</th>
      </tr>
    </thead>
    <tbody>
      ${rows.map(r => `
        <tr>
          ${cols.map(c => `<td>${c.format ? c.format(r[c.key]) : r[c.key]}</td>`).join('')}
          <td>
            <button class="btn btn-sm" data-edit="${r.id}">Editar</button>
            <button class="btn btn-sm btn-danger" data-del="${r.id}">Borrar</button>
          </td>
        </tr>
      `).join('')}
    </tbody>
  `;
            container.replaceChildren(table);

            table.querySelectorAll('[data-edit]').forEach(btn => {
                btn.onclick = () => editHandler(btn.getAttribute('data-edit'));
            });
            table.querySelectorAll('[data-del]').forEach(btn => {
                btn.onclick = () => deleteHandler(btn.getAttribute('data-del'));
            });
        }

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

        // ==== Tarjeta 1: Débito/Crédito ====
        const formDC = document.getElementById('form-dc');
        const listDC = document.getElementById('list-dc');
        async function loadDC() {
            const res = await apiList('debit_credit_tax');
            if (!res.ok) {
                listDC.textContent = res.error || 'Error';
                return;
            }
            renderTable(listDC, res.data, [{
                    key: 'id',
                    label: 'ID'
                },
                {
                    key: 'value',
                    label: 'Valor',
                    format: fmt
                }
            ], async (id) => {
                const row = res.data.find(r => String(r.id) === String(id));
                const nuevo = prompt('Nuevo valor:', fmt(row.value));
                if (nuevo !== null) {
                    const r = await apiPost('debit_credit_tax', 'update', {
                        id,
                        value: nuevo
                    });
                    if (r.ok) loadDC();
                    else alert(r.error || 'Error');
                }
            }, async (id) => {
                if (confirm('¿Borrar registro?')) {
                    const r = await apiPost('debit_credit_tax', 'delete', {
                        id
                    });
                    if (r.ok) loadDC();
                    else alert(r.error || 'Error');
                }
            });
        }
        formDC.onsubmit = async e => {
            e.preventDefault();
            const v = formDC.value.value.trim();
            if (!v) return;
            const res = await apiPost('debit_credit_tax', 'create', {
                value: v
            });
            if (res.ok) {
                formDC.reset();
                loadDC();
            } else alert(res.error || 'Error');
        };

        // ==== Tarjeta 2: Retención ====
        const formRet = document.getElementById('form-ret');
        const listRet = document.getElementById('list-ret');
        async function loadRet() {
            const res = await apiList('retention');
            if (!res.ok) {
                listRet.textContent = res.error || 'Error';
                return;
            }
            renderTable(listRet, res.data, [{
                    key: 'id',
                    label: 'ID'
                },
                {
                    key: 'value',
                    label: 'Valor',
                    format: fmt
                }
            ], async (id) => {
                const row = res.data.find(r => String(r.id) === String(id));
                const nuevo = prompt('Nuevo valor:', fmt(row.value));
                if (nuevo !== null) {
                    const r = await apiPost('retention', 'update', {
                        id,
                        value: nuevo
                    });
                    if (r.ok) loadRet();
                    else alert(r.error || 'Error');
                }
            }, async (id) => {
                if (confirm('¿Borrar registro?')) {
                    const r = await apiPost('retention', 'delete', {
                        id
                    });
                    if (r.ok) loadRet();
                    else alert(r.error || 'Error');
                }
            });
        }
        formRet.onsubmit = async e => {
            e.preventDefault();
            const v = formRet.value.value.trim();
            if (!v) return;
            const res = await apiPost('retention', 'create', {
                value: v
            });
            if (res.ok) {
                formRet.reset();
                loadRet();
            } else alert(res.error || 'Error');
        };

        // ==== Tarjeta 3: Entidades de facturación ====
        const formBill = document.getElementById('form-bill');
        const listBill = document.getElementById('list-bill');
        async function loadBill() {
            const res = await apiList('billing_entity');
            if (!res.ok) {
                listBill.textContent = res.error || 'Error';
                return;
            }
            renderTable(listBill, res.data, [{
                    key: 'id',
                    label: 'ID'
                },
                {
                    key: 'name',
                    label: 'Nombre'
                },
                {
                    key: 'cuit',
                    label: 'CUIT'
                }
            ], async (id) => {
                const row = res.data.find(r => String(r.id) === String(id));
                const nuevoNombre = prompt('Nuevo nombre:', row.name);
                if (nuevoNombre === null) return;
                const nuevoCuit = prompt('Nuevo CUIT:', row.cuit);
                if (nuevoCuit === null) return;
                const r = await apiPost('billing_entity', 'update', {
                    id,
                    name: nuevoNombre,
                    cuit: nuevoCuit
                });
                if (r.ok) loadBill();
                else alert(r.error || 'Error');
            }, async (id) => {
                if (confirm('¿Borrar registro?')) {
                    const r = await apiPost('billing_entity', 'delete', {
                        id
                    });
                    if (r.ok) loadBill();
                    else alert(r.error || 'Error');
                }
            });
        }
        formBill.onsubmit = async e => {
            e.preventDefault();
            const name = formBill.name.value.trim();
            const cuit = formBill.cuit.value.trim();
            if (!name || !cuit) return;
            const res = await apiPost('billing_entity', 'create', {
                name,
                cuit
            });
            if (res.ok) {
                formBill.reset();
                loadBill();
            } else alert(res.error || 'Error');
        };

        // ==== Carga inicial ====
        document.addEventListener('DOMContentLoaded', () => {
            loadDC();
            loadRet();
            loadBill();
        });
    </script>


    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>