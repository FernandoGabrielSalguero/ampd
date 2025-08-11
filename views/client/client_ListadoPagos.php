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
if (($user['role'] ?? '') !== 'Administrativo') {
    die("🚫 Acceso restringido: esta página es solo para usuarios Administrativo.");
}
$usuario = $user['username'] ?? 'Sin usuario';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>AMPD – Listado de pagos</title>

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <link rel="stylesheet" href="https://www.fernandosalguero.com/cdn/assets/css/framework.css">
    <script src="https://www.fernandosalguero.com/cdn/assets/javascript/framework.js" defer></script>

    <style>
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
            vertical-align: top;
        }

        .data-table tbody {
            display: block;
            max-height: 520px;
            overflow-y: auto;
        }

        .data-table thead,
        .data-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .badge.paid {
            background: #22c55e;
            color: #fff;
        }

        .badge.unpaid {
            background: #f59e0b;
            color: #fff;
        }

        .action-icon {
            cursor: pointer;
            margin-right: 10px;
            vertical-align: middle;
        }

        .action-icon:hover {
            opacity: .85;
            transform: translateY(-1px);
        }

        .icon-link {
            cursor: pointer;
            opacity: .9;
        }

        .icon-link.disabled {
            pointer-events: none;
            opacity: .4;
        }

        .muted {
            opacity: .7;
            font-size: .9em;
        }

        .modal .input-icon input,
        .modal .input-icon select {
            background: #f7f7fb;
        }

        /* ====== NUEVO: Acciones 2x2 con botones de color ====== */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .btn-action {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 0;
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 600;
            cursor: pointer;
            transition: transform .08s ease, opacity .12s ease, box-shadow .12s ease;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .06);
            white-space: nowrap;
        }

        .btn-action .material-icons {
            font-size: 20px;
        }

        /* Paleta (editables) */
        .btn-action.view {
            background: #e0f2fe;
            color: #0c4a6e;
        }

        .btn-action.edit {
            background: #ede9fe;
            color: #5b21b6;
        }

        .btn-action.delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-action.pay {
            background: #dcfce7;
            color: #166534;
        }

        .btn-action.download {
            background: #fef9c3;
            color: #854d0e;
        }

        .btn-action:hover {
            opacity: .95;
            transform: translateY(-1px);
        }

        .btn-action:active {
            transform: translateY(0);
        }

        /* Estado más compacto en celdas */
        .data-table td .badge {
            font-size: .85em;
            padding: 4px 8px;
            border-radius: 999px;
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
                <button class="btn-icon" onclick="toggleSidebar()"><span class="material-icons" id="collapseIcon">chevron_left</span></button>
            </div>
        </aside>

        <div class="main">
            <header class="navbar">
                <button class="btn-icon" onclick="toggleSidebar()"><span class="material-icons">menu</span></button>
                <div class="navbar-title">Listado de pagos</div>
            </header>

            <section class="content">
                <div class="card">
                    <h2>Hola 👋 <?= htmlspecialchars($usuario) ?></h2>
                    <p>Revisá las órdenes de pago, su estado y realizá acciones rápidas.</p>
                </div>

                <!-- Filtros -->
                <div class="card">
                    <h2>Filtrar</h2>
                    <form class="form-modern" id="form-filtros">
                        <div class="form-grid grid-2">
                            <div class="input-group">
                                <label for="search_nombre">Nombre del socio</label>
                                <div class="input-icon">
                                    <span class="material-icons">search</span>
                                    <input type="text" id="search_nombre" placeholder="Juan">
                                </div>
                            </div>
                            <div class="input-group">
                                <label for="search_dni">DNI</label>
                                <div class="input-icon">
                                    <span class="material-icons">search</span>
                                    <input type="text" id="search_dni" inputmode="numeric" maxlength="10" placeholder="DNI">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Tabla -->
                <div class="card tabla-card">
                    <h2>Órdenes de pago</h2>
                    <div class="tabla-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Cargado el</th>
                                    <th>Socio</th>
                                    <th>DNI</th>
                                    <th>Monto real</th>
                                    <th>Monto abonado</th>
                                    <th>Evento</th>
                                    <th>Estado</th>
                                    <th>Archivos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-pagos"></tbody>
                        </table>
                    </div>
                </div>

                <div class="alert-container" id="alertContainer"></div>
            </section>
        </div>
    </div>

    <!-- MODAL: Ver detalle -->
    <div id="modalDetalle" class="modal hidden">
        <div class="modal-content card">
            <h3>Detalle de la orden</h3>
            <pre id="detalleJson" style="white-space:pre-wrap"></pre>
            <div class="form-buttons">
                <button type="button" class="btn btn-cancelar" onclick="closeAppModal('modalDetalle')">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Modificar -->
    <div id="modalEditar" class="modal hidden">
        <div class="modal-content card">
            <h3>Modificar orden</h3>
            <form id="formEditar" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-grid grid-3">
                    <div class="input-group">
                        <label>Evento</label>
                        <div class="input-icon"><span class="material-icons">event</span>
                            <input type="text" name="event" id="edit_event">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Monto real</label>
                        <div class="input-icon"><span class="material-icons">payments</span>
                            <input type="number" name="contract_amount" id="edit_amount" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Sellado</label>
                        <div class="input-icon"><span class="material-icons">calculate</span>
                            <input type="number" name="stamp_amount" id="edit_stamp" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Imp. d/c %</label>
                        <div class="input-icon"><span class="material-icons">percent</span>
                            <input type="number" name="debit_credit_tax_rate" id="edit_taxr" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Retención %</label>
                        <div class="input-icon"><span class="material-icons">percent</span>
                            <input type="number" name="retention_rate" id="edit_retr" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Reemplazar PDF pedido</label>
                        <div class="input-icon"><span class="material-icons">picture_as_pdf</span>
                            <input type="file" name="pedido" accept="application/pdf">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Reemplazar PDF factura</label>
                        <div class="input-icon"><span class="material-icons">picture_as_pdf</span>
                            <input type="file" name="factura" accept="application/pdf">
                        </div>
                    </div>
                </div>
                <div class="form-buttons">
                    <button class="btn btn-aceptar" type="submit">Guardar</button>
                    <button type="button" class="btn btn-cancelar" onclick="closeAppModal('modalEditar')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: Pagar -->
    <div id="modalPagar" class="modal hidden">
        <div class="modal-content card">
            <h3>Registrar pago de factura</h3>
            <form id="formPagar" enctype="multipart/form-data">
                <input type="hidden" name="action" value="settle">
                <input type="hidden" name="id" id="pay_id">
                <div class="form-grid grid-2">
                    <div class="input-group">
                        <label>Fecha de pago</label>
                        <div class="input-icon"><span class="material-icons">schedule</span>
                            <input type="datetime-local" name="paid_at" id="pay_date">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Nº de transacción</label>
                        <div class="input-icon"><span class="material-icons">tag</span>
                            <input type="text" name="txn_number" id="pay_txn" required>
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Comprobante (PDF)</label>
                        <div class="input-icon"><span class="material-icons">picture_as_pdf</span>
                            <input type="file" name="receipt" accept="application/pdf">
                        </div>
                    </div>
                </div>
                <div class="form-buttons">
                    <button class="btn btn-aceptar" type="submit">Guardar</button>
                    <button type="button" class="btn btn-cancelar" onclick="closeAppModal('modalPagar')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        const API = '../../controllers/client_listadoPagosController.php';

        // ===== Fallback de modales si el framework no define openAppModal/closeAppModal =====
        if (typeof window.openAppModal !== 'function') {
            window.openAppModal = (id) => {
                const el = document.getElementById(id);
                if (el) el.classList.remove('hidden');
            };
        }
        if (typeof window.closeAppModal !== 'function') {
            window.closeAppModal = (id) => {
                const el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            };
        }

        // Utils
        const fmtMoney = n => new Intl.NumberFormat('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(Number(n || 0));

        function showAlert(type, message) {
            const c = document.getElementById('alertContainer');
            const el = document.createElement('div');
            el.className = `alert ${type}`;
            el.textContent = message;
            c.appendChild(el);
            setTimeout(() => el.remove(), 4000);
        }

        function badgePaid(isPaid) {
            return `<span class="badge ${isPaid ? 'paid' : 'unpaid'}">${isPaid ? 'Abonada' : 'Sin abonar'}</span>`;
        }

        // Cargar lista
        async function loadList() {
            const nombre = document.getElementById('search_nombre').value.trim();
            const dni = document.getElementById('search_dni').value.trim();
            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'list',
                        search_nombre: nombre,
                        search_dni: dni
                    })
                });

                const data = await res.json();
                const tbody = document.getElementById('tbody-pagos');
                tbody.innerHTML = '';

                (data.rows || []).forEach(r => {
                    const pedidoIcon = r.pedido_pdf_path ? `<span class="material-icons icon-link" title="Pedido" onclick="window.open('${r.pedido_pdf_path}','_blank')">description</span>` : `<span class="material-icons icon-link disabled" title="Sin pedido">description</span>`;
                    const facturaIcon = r.factura_pdf_path ? `<span class="material-icons icon-link" title="Factura" onclick="window.open('${r.factura_pdf_path}','_blank')">picture_as_pdf</span>` : `<span class="material-icons icon-link disabled" title="Sin factura">picture_as_pdf</span>`;

                    const actionsHtml = `
                        <div class="actions-grid">
                            <button type="button" class="btn-action view" title="Detalle" onclick="openDetalle(${r.id})">
                                <span class="material-icons">visibility</span><span>Detalle</span>
                            </button>
                            <button type="button" class="btn-action edit" title="Editar" onclick="openEditar(${r.id})">
                                <span class="material-icons">edit</span><span>Editar</span>
                            </button>
                            <button type="button" class="btn-action delete" title="Eliminar" onclick="deletePago(${r.id})">
                                <span class="material-icons">delete</span><span>Eliminar</span>
                            </button>
                            ${
                                !Number(r.is_paid)
                                ? `<button type="button" class="btn-action pay" title="Marcar pago" onclick="openPagar(${r.id})">
                                     <span class="material-icons">price_check</span><span>Pagar</span>
                                   </button>`
                                : `<button type="button" class="btn-action download" title="Descargar JPG" onclick="downloadJpg(${r.id})">
                                     <span class="material-icons">download</span><span>Descargar</span>
                                   </button>`
                            }
                        </div>
                    `;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>#${r.id}</td>
                        <td>${r.created_at || ''}</td>
                        <td>${r.first_name || ''}</td>
                        <td>${r.dni || ''}</td>
                        <td>$ ${fmtMoney(r.contract_amount)}</td>
                        <td>$ ${fmtMoney(r.total_to_user)}</td>
                        <td>${r.event || ''}</td>
                        <td>${badgePaid(!!Number(r.is_paid))}</td>
                        <td>${pedidoIcon} ${facturaIcon}</td>
                        <td>${actionsHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (e) {
                console.error(e);
                showAlert('danger', 'No se pudo cargar el listado');
            }
        }

        // Acciones
        async function openDetalle(id) {
            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get', id })
                });
                const data = await res.json();
                if (!res.ok || data.success === false) throw new Error(data.message || 'Error');
                document.getElementById('detalleJson').textContent = JSON.stringify(data.row, null, 2);
                openAppModal('modalDetalle');
            } catch (e) {
                console.error(e);
                showAlert('danger', e.message || 'No se pudo obtener el detalle');
            }
        }

        async function openEditar(id) {
            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get', id })
                });
                const data = await res.json();
                if (!res.ok || data.success === false) throw new Error(data.message || 'Error');

                const { row } = data;
                document.getElementById('edit_id').value = row.id;
                document.getElementById('edit_event').value = row.event || '';
                document.getElementById('edit_amount').value = row.contract_amount || 0;
                document.getElementById('edit_stamp').value = row.stamp_amount || 0;
                document.getElementById('edit_taxr').value = row.debit_credit_tax_rate || 0;
                document.getElementById('edit_retr').value = row.retention_rate || 0;
                openAppModal('modalEditar');
            } catch (e) {
                console.error(e);
                showAlert('danger', e.message || 'No se pudo abrir el editor');
            }
        }

        document.getElementById('formEditar').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            try {
                const res = await fetch(API, { method: 'POST', body: fd });
                const data = await res.json();
                if (!res.ok || data.success === false) throw new Error(data.message || 'Error');
                closeAppModal('modalEditar');
                showAlert('success', 'Orden actualizada');
                loadList();
            } catch (err) {
                console.error(err);
                showAlert('danger', err.message || 'No se pudo actualizar la orden');
            }
        });

        function openPagar(id) {
            try {
                document.getElementById('pay_id').value = id;
                // default now
                const now = new Date();
                const pad = n => String(n).padStart(2, '0');
                const local = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
                document.getElementById('pay_date').value = local;
                document.getElementById('pay_txn').value = '';
                openAppModal('modalPagar');
            } catch (e) {
                console.error(e);
                showAlert('danger', 'No pude abrir el modal de pago');
            }
        }

        document.getElementById('formPagar').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            try {
                const res = await fetch(API, { method: 'POST', body: fd });
                const data = await res.json();
                if (!res.ok || data.success === false) throw new Error(data.message || 'Error');
                closeAppModal('modalPagar');
                showAlert('success', 'Factura marcada como pagada');
                loadList();
            } catch (err) {
                console.error(err);
                showAlert('danger', err.message || 'No se pudo registrar el pago');
            }
        });

        async function deletePago(id) {
            if (!confirm('¿Eliminar la orden #' + id + '? Esta acción no se puede deshacer.')) return;
            try {
                const res = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id })
                });
                const data = await res.json();
                if (!res.ok || data.success === false) throw new Error(data.message || 'Error');
                showAlert('success', 'Orden eliminada');
                loadList();
            } catch (err) {
                console.error(err);
                showAlert('danger', err.message || 'No se pudo eliminar la orden');
            }
        }

        function downloadJpg(id) {
            // descarga directa por GET
            window.location.href = `${API}?action=downloadImage&id=${encodeURIComponent(id)}`;
        }

        // Filtros en vivo
        document.getElementById('search_nombre').addEventListener('input', () => loadList());
        document.getElementById('search_dni').addEventListener('input', () => loadList());

        // Init
        loadList();
    </script>
</body>

</html>
