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
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>AMPD – Pago Facturas</title>

  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link rel="stylesheet" href="https://www.fernandosalguero.com/cdn/assets/css/framework.css">
  <script src="https://www.fernandosalguero.com/cdn/assets/javascript/framework.js" defer></script>

  <style>
    .grid-3-2 { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
    @media (max-width:980px){ .grid-3-2{ grid-template-columns:repeat(2,1fr);} }
    @media (max-width:640px){ .grid-3-2{ grid-template-columns:1fr;} }
    .muted{ font-size:.9em; opacity:.8; margin-left:6px;}
    .total-box{ font-size:1.4em; font-weight:700; }
    .file-hint{ font-size:.85em; opacity:.7;}
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
        <li onclick="location.href='client_dashboard.php'"><span class="material-icons" style="color:#5b21b6;">home</span><span class="link-text">Inicio</span></li>
        <li onclick="location.href='client_asociar.php'"><span class="material-icons" style="color:#5b21b6;">person_add</span><span class="link-text">Registrar Socio</span></li>
        <li onclick="location.href='client_pagoFacturas.php'"><span class="material-icons" style="color:#5b21b6;">attach_money</span><span class="link-text">Pago Facturas</span></li>
        <li onclick="location.href='client_ListadoPagos.php'"><span class="material-icons" style="color:#5b21b6;">assignment_turned_in</span><span class="link-text">Listado de pagos</span></li>
        <li onclick="location.href='../../../logout.php'"><span class="material-icons" style="color:red;">logout</span><span class="link-text">Salir</span></li>
      </ul>
    </nav>
    <div class="sidebar-footer">
      <button class="btn-icon" onclick="toggleSidebar()"><span class="material-icons" id="collapseIcon">chevron_left</span></button>
    </div>
  </aside>

  <div class="main">
    <header class="navbar">
      <button class="btn-icon" onclick="toggleSidebar()"><span class="material-icons">menu</span></button>
      <div class="navbar-title">Pago Facturas</div>
    </header>

    <section class="content">
      <div class="card">
        <h2>Hola 👋 <?= htmlspecialchars($usuario) ?></h2>
        <p>Generá y registrá pagos al socio. Completá con DNI y el sistema autocompleta el resto.</p>
      </div>

      <div class="card">
        <h2>Nuevo pago</h2>
        <form class="form-modern" id="form-pago" method="post" enctype="multipart/form-data" novalidate>
          <div class="form-grid grid-3-2">
            <!-- DNI -->
            <div class="input-group">
              <label for="dni">DNI</label>
              <div class="input-icon">
                <span class="material-icons">badge</span>
                <input type="text" id="dni" name="dni" inputmode="numeric" pattern="^\d{6,10}$" maxlength="10" placeholder="Solo números" required>
              </div>
            </div>

            <!-- Nombre -->
            <div class="input-group">
              <label for="nombre">Nombre</label>
              <div class="input-icon">
                <span class="material-icons">person</span>
                <input type="text" id="nombre" name="nombre" placeholder="Se autocompleta por DNI">
              </div>
            </div>

            <!-- Teléfono -->
            <div class="input-group">
              <label for="telefono">Teléfono</label>
              <div class="input-icon">
                <span class="material-icons">phone</span>
                <input type="tel" id="telefono" name="telefono" placeholder="Se autocompleta por DNI">
              </div>
            </div>

            <!-- CUIT / CBU / Alias Beneficiario -->
            <div class="input-group">
              <label for="cuit_ben">CUIT Beneficiario</label>
              <div class="input-icon">
                <span class="material-icons">assignment_ind</span>
                <input type="text" id="cuit_ben" name="cuit_ben" inputmode="numeric" pattern="^\d{11}$" maxlength="11" placeholder="Se autocompleta">
              </div>
            </div>
            <div class="input-group">
              <label for="cbu_ben">CBU Beneficiario</label>
              <div class="input-icon">
                <span class="material-icons">account_balance</span>
                <input type="text" id="cbu_ben" name="cbu_ben" inputmode="numeric" pattern="^\d{22}$" maxlength="22" placeholder="Se autocompleta">
              </div>
            </div>
            <div class="input-group">
              <label for="alias_ben">Alias Beneficiario</label>
              <div class="input-icon">
                <span class="material-icons">alternate_email</span>
                <input type="text" id="alias_ben" name="alias_ben" placeholder="Se autocompleta">
              </div>
            </div>

            <!-- Evento -->
            <div class="input-group">
              <label for="evento">Evento</label>
              <div class="input-icon">
                <span class="material-icons">event</span>
                <input type="text" id="evento" name="evento" placeholder="Descripción del evento" required>
              </div>
            </div>

            <!-- Monto -->
            <div class="input-group">
              <label for="monto">Monto real del contrato</label>
              <div class="input-icon">
                <span class="material-icons">payments</span>
                <input type="number" id="monto" name="monto" min="0" step="0.01" placeholder="0.00" required>
              </div>
            </div>

            <!-- Razón social -->
            <div class="input-group">
              <label for="dest_entity">Razón social destinatario</label>
              <div class="input-icon">
                <span class="material-icons">apartment</span>
                <select id="dest_entity" name="dest_entity" required></select>
              </div>
            </div>

            <!-- CUIT destinatario -->
            <div class="input-group">
              <label for="dest_cuit">CUIT destinatario</label>
              <div class="input-icon">
                <span class="material-icons">business</span>
                <input type="text" id="dest_cuit" name="dest_cuit" inputmode="numeric" pattern="^\d{11}$" maxlength="11" placeholder="Se completa por la razón social" readonly>
              </div>
            </div>

            <!-- Sellado -->
            <div class="input-group">
              <label for="sellado">Sellado <span id="selladoPct" class="muted"></span></label>
              <div class="input-icon">
                <span class="material-icons">calculate</span>
                <input type="number" id="sellado" name="sellado" min="0" step="0.01" placeholder="0.00">
              </div>
            </div>

            <!-- Impuesto D/C -->
            <div class="input-group">
              <label for="impuesto_dc">Imp. débito/crédito <span id="impuestoMonto" class="muted"></span></label>
              <div class="input-icon">
                <span class="material-icons">percent</span>
                <select id="impuesto_dc" name="impuesto_dc" required></select>
              </div>
            </div>

            <!-- Retención -->
            <div class="input-group">
              <label for="retencion">Retención <span id="retencionMonto" class="muted"></span></label>
              <div class="input-icon">
                <span class="material-icons">percent</span>
                <select id="retencion" name="retencion" required></select>
              </div>
            </div>

            <!-- Total -->
            <div class="input-group">
              <label>Total a pagarle al socio</label>
              <div class="input-icon">
                <span class="material-icons">request_quote</span>
                <input type="text" id="total_pagar" name="total_pagar" class="total-box" readonly>
              </div>
            </div>

            <!-- CUOTA (si no está paga) -->
            <div id="cuotaBox" class="card soft hidden" style="margin:6px 0;padding:12px;">
              <div class="alert warning" style="margin-bottom:10px;">
                Este socio no tiene la cuota <span id="cuotaYearTxt"></span> paga.
              </div>

              <div class="form-grid grid-2">
                <div class="input-group">
                  <label class="checkbox" style="display:flex;gap:8px;align-items:center;">
                    <input type="checkbox" id="cuota_aplicar">
                    <span>Descontar cuota del pago y marcar la cuota <span id="cuotaYearTxt2"></span> como paga</span>
                  </label>
                </div>

                <div class="input-group">
                  <label for="cuota_monto">Monto de la cuota <span id="cuotaPct" class="muted"></span></label>
                  <div class="input-icon">
                    <span class="material-icons">confirmation_number</span>
                    <input type="number" id="cuota_monto" name="cuota_monto" min="0" step="0.01" placeholder="0.00" disabled>
                  </div>
                </div>
              </div>
            </div>

            <!-- PDFs -->
            <div class="input-group">
              <label for="pdf_pedido">Archivo pedido (PDF)</label>
              <div class="input-icon">
                <span class="material-icons">picture_as_pdf</span>
                <input type="file" id="pdf_pedido" name="pdf_pedido" accept="application/pdf">
              </div>
              <div class="file-hint">Formato PDF. Máx 10MB.</div>
            </div>

            <div class="input-group">
              <label for="pdf_factura">Archivo factura (PDF)</label>
              <div class="input-icon">
                <span class="material-icons">picture_as_pdf</span>
                <input type="file" id="pdf_factura" name="pdf_factura" accept="application/pdf">
              </div>
              <div class="file-hint">Formato PDF. Máx 10MB.</div>
            </div>
          </div>

          <div class="form-buttons">
            <button class="btn btn-aceptar" type="submit">
              <span class="material-icons" style="vertical-align:middle">save</span>
              <span>Guardar pago</span>
            </button>
          </div>
        </form>
      </div>

      <div class="alert-container" id="alertContainer"></div>
    </section>
  </div>
</div>

<script src="../../views/partials/spinner-global.js"></script>
<script>
const API = '../../controllers/client_pagoFacturasController.php';

const fmt = n => isFinite(n)
  ? new Intl.NumberFormat('es-AR',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(n))
  : '0,00';

function showAlert(type, message) {
  const container = document.getElementById('alertContainer');
  const el = document.createElement('div');
  el.className = `alert ${type}`;
  el.textContent = message;
  container.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

// Estado
let ENTITIES=[], TAXES=[], RETS=[], CURRENT_USER=null;

// Bootstrap
async function bootstrap() {
  try {
    const res = await fetch(API, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'bootstrap'})
    });
    const data = await res.json();

    ENTITIES = data.entities || [];
    TAXES    = data.taxes || [];
    RETS     = data.retentions || [];

    // Razón social
    const selEnt = document.getElementById('dest_entity');
    selEnt.innerHTML = '<option value="">Seleccionar…</option>' +
      ENTITIES.map(e => `<option value="${e.id}" data-cuit="${e.cuit}">${e.name}</option>`).join('');
    selEnt.addEventListener('change', () => {
      const opt = selEnt.options[selEnt.selectedIndex];
      document.getElementById('dest_cuit').value = opt?.dataset?.cuit || '';
    });

    // Impuesto D/C (mostrar con 2 decimales)
    const selTax = document.getElementById('impuesto_dc');
    selTax.innerHTML = TAXES.map(t => {
      const v = parseFloat(t.value);
      return `<option value="${t.value}" ${t.is_favorite ? 'selected':''}>${v.toFixed(2)}%</option>`;
    }).join('');
    selTax.addEventListener('change', recompute);

    // Retención (mostrar con 2 decimales)
    const selRet = document.getElementById('retencion');
    selRet.innerHTML = RETS.map(r => {
      const v = parseFloat(r.value);
      return `<option value="${r.value}" ${r.is_favorite ? 'selected':''}>${v.toFixed(2)}%</option>`;
    }).join('');
    selRet.addEventListener('change', recompute);

  } catch (err) {
    console.error(err);
    showAlert('danger','No se pudieron cargar las listas');
  }
}

// DNI -> autocompletar
async function cargarPorDNI() {
  const dni = (document.getElementById('dni').value || '').replace(/\D+/g,'');
  if (!dni) return;
  try {
    const res = await fetch(API, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({action:'getByDni', dni})
    });
    const data = await res.json();
    if (!data.success) {
      showAlert('warning', data.message || 'No encontrado');
      return;
    }
    CURRENT_USER = data.user;

    document.getElementById('nombre').value   = data.user.first_name || '';
    document.getElementById('telefono').value = data.user.phone || '';
    document.getElementById('cuit_ben').value = data.user.cuit || '';
    document.getElementById('cbu_ben').value  = data.user.cbu || '';
    document.getElementById('alias_ben').value= data.user.alias || '';

    // Cuota
    const cuotaBox = document.getElementById('cuotaBox');
    const year = data.user.year;
    document.getElementById('cuotaYearTxt').textContent  = year;
    document.getElementById('cuotaYearTxt2').textContent = year;

    const chk = document.getElementById('cuota_aplicar');
    const inp = document.getElementById('cuota_monto');
    chk.checked = false; inp.value = ''; inp.disabled = true;

    if (data.user.fee_paid) cuotaBox.classList.add('hidden');
    else cuotaBox.classList.remove('hidden');

    recompute();
  } catch (err) {
    console.error(err);
    showAlert('danger','Error al buscar DNI');
  }
}

// Recalcular
function recompute() {
  const monto   = parseFloat((document.getElementById('monto').value   || '0').replace(',','.')) || 0;
  const sellado = parseFloat((document.getElementById('sellado').value || '0').replace(',','.')) || 0;
  const taxPct  = parseFloat(document.getElementById('impuesto_dc').value) || 0;
  const retPct  = parseFloat(document.getElementById('retencion').value) || 0;

  const taxAmt  = monto * (taxPct/100);
  const retAmt  = monto * (retPct/100);

  const applyFee = document.getElementById('cuota_aplicar')?.checked;
  const feeAmt   = applyFee ? (parseFloat((document.getElementById('cuota_monto').value || '0').replace(',','.')) || 0) : 0;

  const total = monto - sellado - taxAmt - retAmt - feeAmt;

  // etiquetas
  const pctSell = monto > 0 ? (sellado*100/monto) : 0;
  document.getElementById('selladoPct').textContent   = pctSell > 0 ? `(${fmt(pctSell)}%)` : '';
  document.getElementById('impuestoMonto').textContent= taxPct > 0 ? `(${fmt(taxAmt)} ARS)` : '';
  document.getElementById('retencionMonto').textContent= retPct > 0 ? `(${fmt(retAmt)} ARS)` : '';

  const feePct = monto > 0 ? (feeAmt*100/monto) : 0;
  const cuotaPctEl = document.getElementById('cuotaPct');
  if (applyFee && feeAmt > 0) cuotaPctEl.textContent = `(${fmt(feePct)}%)`;
  else cuotaPctEl.textContent = '';

  document.getElementById('total_pagar').value = fmt(total);
}

// Listeners
document.getElementById('dni').addEventListener('change', cargarPorDNI);
['monto','sellado'].forEach(id => document.getElementById(id).addEventListener('input', recompute));
document.getElementById('cuota_aplicar').addEventListener('change', () => {
  const enabled = document.getElementById('cuota_aplicar').checked;
  const inp = document.getElementById('cuota_monto');
  inp.disabled = !enabled;
  if (!enabled) inp.value = '';
  recompute();
});
document.getElementById('cuota_monto').addEventListener('input', recompute);

// Submit
document.getElementById('form-pago').addEventListener('submit', async (e) => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action','create');

  if (CURRENT_USER?.user_id) fd.append('user_id', CURRENT_USER.user_id);

  // cuota
  const feeApply = document.getElementById('cuota_aplicar').checked;
  const feeAmt   = feeApply ? (parseFloat((document.getElementById('cuota_monto').value || '0').replace(',','.')) || 0) : 0;
  fd.append('fee_amount', feeAmt.toString());
  fd.append('fee_year', (CURRENT_USER?.year || new Date().getFullYear()).toString());
  fd.append('fee_mark_paid', (feeApply && feeAmt > 0) ? '1' : '0');

  try {
    const res  = await fetch(API, { method:'POST', body: fd });
    const data = await res.json();
    if (!res.ok || !data.success) throw new Error(data.message || 'No se pudo guardar el pago');

    showAlert('success','Pago registrado correctamente');
    e.target.reset();
    CURRENT_USER = null;
    document.getElementById('dest_cuit').value = '';
    document.getElementById('selladoPct').textContent = '';
    document.getElementById('impuestoMonto').textContent = '';
    document.getElementById('retencionMonto').textContent = '';
    document.getElementById('cuotaPct').textContent = '';
    document.getElementById('total_pagar').value = '';
  } catch (err) {
    console.error(err);
    showAlert('danger', err.message);
  }
});

// Arranque
bootstrap().then(recompute);
</script>
</body>
</html>
