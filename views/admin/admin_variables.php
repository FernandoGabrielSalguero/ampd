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

$usuario = $user['username'] ?? 'Sin usuario';
$email   = $user['email'] ?? 'Sin email';
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
        .hint {
            font-size: .9rem;
            color: #64748b;
            margin-top: .25rem
        }

        .ok {
            color: #16a34a
        }

        .err {
            color: #dc2626
        }
    </style>
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
                    <li class="active" onclick="location.href='admin_variables.php'">
                        <span class="material-icons" style="color: #5b21b6;">tune</span><span class="link-text">Variables de entorno</span>
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
                <div class="navbar-title">Variables de entorno</div>
            </header>

            <section class="content">
                <div class="card">
                    <h2>Hola 👋 <?= htmlspecialchars($usuario) ?></h2>
                    <p>Acá administramos las variables globales de AMPD.</p>
                    <div id="flash" class="hint"></div>
                </div>

                <div class="card-grid grid-2">

                    <!-- Impuesto débito/crédito -->
                    <div class="card">
                        <form id="form-debit-credit" class="form-modern">
                            <div class="input-group">
                                <h4>Impuesto al débito y crédito</h4>
                                <label for="dc_value">Porcentaje (%)</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input id="dc_value" name="value" type="number" step="0.0001" min="0" placeholder="1.20">
                                </div>
                                <div class="hint">Se guarda con 4 decimales. Ej: 1.2000</div>
                            </div>
                            <div class="form-buttons">
                                <button class="btn btn-aceptar" type="submit">Guardar impuesto</button>
                            </div>
                        </form>
                        <ul id="list-debit-credit" class="list-modern" style="margin-top:.75rem"></ul>
                    </div>

                    <!-- Retención -->
                    <div class="card">
                        <form id="form-retention" class="form-modern">
                            <div class="input-group">
                                <h4>Retención</h4>
                                <label for="ret_value">Porcentaje (%)</label>
                                <div class="input-icon">
                                    <span class="material-icons">percent</span>
                                    <input id="ret_value" name="value" type="number" step="0.0001" min="0" placeholder="3">
                                </div>
                                <div class="hint">Se guarda con 4 decimales. Ej: 3.0000</div>
                            </div>
                            <div class="form-buttons">
                                <button class="btn btn-aceptar" type="submit">Guardar retención</button>
                            </div>
                            <ul id="list-retention" class="list-modern" style="margin-top:.75rem"></ul>
                        </form>
                    </div>

                    <!-- Entidad de facturación -->
                    <div class="card">
                        <form id="form-billing" class="form-modern">
                            <div class="input-group">
                                <h4>Entidad de facturación</h4>
                                <label for="be_name">Nombre</label>
                                <div class="input-icon">
                                    <span class="material-icons">business</span>
                                    <input id="be_name" name="name" type="text" placeholder="Asociación Mendocina por la Danza">
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="be_cuit">CUIT</label>
                                <div class="input-icon">
                                    <span class="material-icons">badge</span>
                                    <input id="be_cuit" name="cuit" type="text" inputmode="numeric" placeholder="30711234567" maxlength="20">
                                </div>
                                <div class="hint">Se valida unicidad por CUIT.</div>
                            </div>
                            <div class="form-buttons">
                                <button class="btn btn-aceptar" type="submit">Guardar entidad</button>
                            </div>
                        </form>
                        <ul id="list-billing" class="list-modern" style="margin-top:.75rem"></ul>
                    </div>

                </div>
                <!-- Alert -->
                <div class="alert-container" id="alertContainer"></div>
            </section>
        </div>
    </div>

    <script src="../../views/partials/spinner-global.js"></script>

<script>
    const ctrlUrl = '../../controllers/AdminVariablesController.php';

    function flash(msg, ok = true) {
        const el = document.getElementById('flash');
        if (!el) return;
        el.textContent = msg;
        el.classList.remove('ok', 'err');
        el.classList.add(ok ? 'ok' : 'err');
        setTimeout(() => { el.textContent = ''; }, 5000);
    }

    function showAlert(msg) {
        alert(msg);
    }

    async function api(action, payload = {}, method = 'POST') {
        const opts = {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: method === 'POST' ? JSON.stringify({ action, ...payload }) : null
        };
        const res = await fetch(`${ctrlUrl}?t=${Date.now()}`, opts);
        if (!res.ok) throw new Error('Error HTTP ' + res.status);
        const data = await res.json();
        if (!data || data.error) throw new Error(data?.error || 'Error desconocido');
        return data;
    }

    // Carga inicial (solo al abrir la página)
    async function cargarValoresIniciales() {
        try {
            const data = await api('get_values', {}, 'POST');
            if (data.debit_credit?.value != null) {
                document.getElementById('dc_value').value = data.debit_credit.value;
            }
            if (data.retention?.value != null) {
                document.getElementById('ret_value').value = data.retention.value;
            }
            if (data.billing_entity) {
                document.getElementById('be_name').value = data.billing_entity.name || '';
                document.getElementById('be_cuit').value = data.billing_entity.cuit || '';
            }
        } catch (e) {
            console.error(e);
            flash('No se pudieron cargar los valores actuales.', false);
        }
    }

    /* ---------- Helpers UI ---------- */
    function iconBtn({ title, icon, onclick, active = false, colorActive = '#f59e0b' }) {
        const style = active ? `style="color:${colorActive}"` : '';
        return `
            <button class="btn-icon" title="${title}" onclick="${onclick}">
                <span class="material-icons" ${style}>${icon}</span>
            </button>
        `;
    }

    function fmt4(n) {
        const v = Number(n);
        return isFinite(v) ? v.toFixed(4) : n;
    }

    function resetForm(formId) {
        const form = document.getElementById(formId);
        if (form) form.reset();
    }

    /* ---------- Render de listas ---------- */
    async function renderLists() {
        await Promise.all([renderDebitCredit(), renderRetention(), renderBilling()]);
    }

    async function renderDebitCredit() {
        try {
            const items = await api('list_debit_credit', {}, 'POST');
            const ul = document.getElementById('list-debit-credit');
            if (!ul) return;
            ul.innerHTML = items.map(row => `
                <li class="list-item flex-row space-between">
                    <div>
                        <strong>${fmt4(row.value)}%</strong>
                        <span class="hint"> · id ${row.id} · ${row.created_at ?? ''}</span>
                    </div>
                    <div class="actions">
                        ${iconBtn({
                            title: 'Favorito',
                            icon: row.is_favorite == 1 ? 'star' : 'star_border',
                            onclick: `setFav('debit', ${row.id})`,
                            active: row.is_favorite == 1
                        })}
                        ${iconBtn({
                            title: 'Eliminar',
                            icon: 'delete',
                            onclick: `delItem('debit', ${row.id})`
                        })}
                    </div>
                </li>
            `).join('');
        } catch (e) {
            console.error(e);
            flash('No se pudo cargar el historial de Débito/Crédito.', false);
        }
    }

    async function renderRetention() {
        try {
            const items = await api('list_retention', {}, 'POST');
            const ul = document.getElementById('list-retention');
            if (!ul) return;
            ul.innerHTML = items.map(row => `
                <li class="list-item flex-row space-between">
                    <div>
                        <strong>${fmt4(row.value)}%</strong>
                        <span class="hint"> · id ${row.id} · ${row.created_at ?? ''}</span>
                    </div>
                    <div class="actions">
                        ${iconBtn({
                            title: 'Favorito',
                            icon: row.is_favorite == 1 ? 'star' : 'star_border',
                            onclick: `setFav('ret', ${row.id})`,
                            active: row.is_favorite == 1
                        })}
                        ${iconBtn({
                            title: 'Eliminar',
                            icon: 'delete',
                            onclick: `delItem('ret', ${row.id})`
                        })}
                    </div>
                </li>
            `).join('');
        } catch (e) {
            console.error(e);
            flash('No se pudo cargar el historial de Retención.', false);
        }
    }

    async function renderBilling() {
        try {
            const items = await api('list_billing_entities', {}, 'POST');
            const ul = document.getElementById('list-billing');
            if (!ul) return;
            ul.innerHTML = items.map(row => `
                <li class="list-item flex-row space-between">
                    <div>
                        <strong>${row.name}</strong> — CUIT ${row.cuit}
                        <span class="hint"> · id ${row.id} · ${row.created_at ?? ''}</span>
                    </div>
                    <div class="actions">
                        ${iconBtn({
                            title: 'Favorito',
                            icon: row.is_favorite == 1 ? 'star' : 'star_border',
                            onclick: `setFav('bill', ${row.id})`,
                            active: row.is_favorite == 1
                        })}
                        ${iconBtn({
                            title: 'Eliminar',
                            icon: 'delete',
                            onclick: `delItem('bill', ${row.id})`
                        })}
                    </div>
                </li>
            `).join('');
        } catch (e) {
            console.error(e);
            flash('No se pudo cargar el historial de Entidades.', false);
        }
    }

    /* ---------- Acciones: favorito / eliminar ---------- */
    async function setFav(kind, id) {
        const map = {
            debit: 'favorite_debit_credit',
            ret: 'favorite_retention',
            bill: 'favorite_billing'
        };
        try {
            await api(map[kind], { id });
            flash('Favorito actualizado.');
            showAlert('success', 'Favorito actualizado correctamente.');
            await renderLists();
        } catch (e) {
            console.error(e);
            flash(e.message, false);
        }
    }

    async function delItem(kind, id) {
        if (!confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')) return;
        const map = {
            debit: 'delete_debit_credit',
            ret: 'delete_retention',
            bill: 'delete_billing'
        };
        try {
            await api(map[kind], { id });
            flash('Registro eliminado.');
            showAlert('success', 'Registro eliminado correctamente.');
            await renderLists();
        } catch (e) {
            console.error(e);
            flash(e.message, false);
        }
    }

    /* ---------- Submits (guardan + limpian el formulario + refrescan lista) ---------- */
    document.getElementById('form-debit-credit').addEventListener('submit', async (e) => {
        e.preventDefault();
        const value = parseFloat(document.getElementById('dc_value').value);
        if (isNaN(value) || value < 0) return flash('Ingrese un porcentaje válido para Débito/Crédito.', false);
        try {
            await api('save_debit_credit_tax', { value });
            flash('Impuesto guardado.');
            showAlert('success', 'Nueva variable de Débito/Crédito creada correctamente.');
            resetForm('form-debit-credit');                 // limpiar formulario
            await renderDebitCredit();                      // actualizar historial
            // Nota: NO recargamos valores en el input para evitar confusión
        } catch (err) {
            console.error(err);
            flash(err.message, false);
        }
    });

    document.getElementById('form-retention').addEventListener('submit', async (e) => {
        e.preventDefault();
        const value = parseFloat(document.getElementById('ret_value').value);
        if (isNaN(value) || value < 0) return flash('Ingrese un porcentaje válido para Retención.', false);
        try {
            await api('save_retention', { value });
            flash('Retención guardada.');
            showAlert('success', 'Nueva variable de Retención creada correctamente.');
            resetForm('form-retention');                    // limpiar formulario
            await renderRetention();                        // actualizar historial
        } catch (err) {
            console.error(err);
            flash(err.message, false);
        }
    });

    document.getElementById('form-billing').addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('be_name').value.trim();
        const cuit = document.getElementById('be_cuit').value.trim();
        if (!name) return flash('El nombre es obligatorio.', false);
        if (!/^\d{7,20}$/.test(cuit)) return flash('CUIT inválido. Solo dígitos (7 a 20).', false);
        try {
            await api('save_billing_entity', { name, cuit });
            flash('Entidad de facturación guardada.');
            showAlert('success', 'Nueva entidad de facturación creada correctamente.');
            resetForm('form-billing');                      // limpiar formulario
            await renderBilling();                          // actualizar historial
        } catch (err) {
            console.error(err);
            flash(err.message, false);
        }
    });

    /* ---------- Init ---------- */
    cargarValoresIniciales();  // solo al entrar en la pantalla
    renderLists();
</script>



    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>