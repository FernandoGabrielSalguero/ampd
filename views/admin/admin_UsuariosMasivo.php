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
                    <li onclick="location.href='admin_UsuariosMasivo.php'">
                        <span class="material-icons" style="color: #5b21b6;">person</span><span class="link-text">Usuarios Masivos</span>
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
                    <p>En esta página, vamos a cargar masivamente los usuarios de la asociación</p>
                </div>

                <!-- carga masiva de usuarios (rol Socio) y sus cuentas bancarias desde CSV. -->
                <div class="card">
                    <h3>Subir CSV de usuarios</h3>
                    <p class="hint">Formato UTF‑8 con cabecera. Columnas esperadas: <code>email, first_name, dni, n_socio, contact_phone, cbu_a, alias_a, titular_a, banco_a, user_name, pass, cuit_a, cbu_b, alias_b, titular_b, banco_b, cbu_c, alias_c, titular_c, banco_c</code></p>

                    <form id="form-csv" class="form-modern" enctype="multipart/form-data">
                        <div class="input-group">
                            <label>Archivo CSV</label>
                            <div class="input-icon">
                                <span class="material-icons">upload_file</span>
                                <input id="csv_file" name="file" type="file" accept=".csv" required>
                            </div>
                        </div>
                        <div class="input-group" style="display:flex;align-items:center;gap:.5rem;">
                            <input id="replace" name="replace" type="checkbox" checked>
                            <label for="replace">Reemplazar existentes (DNI + cuenta A/B/C)</label>
                        </div>

                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Procesar CSV</button>
                        </div>
                    </form>

                    <div id="upload-result" class="hint" style="margin-top:1rem"></div>
                </div>

            </section>
        </div>

        <!-- Alert -->
        <div class="alert-container" id="alertContainer"></div>
    </div>

    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        const bulkCtrl = '../../controllers/AdminUsuarioMasivoController.php';

        document.getElementById('form-csv').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInput = document.getElementById('csv_file');
            if (!fileInput.files.length) return alert('Seleccioná un archivo CSV.');

            const fd = new FormData();
            fd.append('action', 'upload_csv');
            fd.append('file', fileInput.files[0]);
            fd.append('replace', document.getElementById('replace').checked ? '1' : '0');
            fd.append('debug_headers', '1');

            const resEl = document.getElementById('upload-result');
            resEl.textContent = 'Procesando...';

            try {
                const resp = await fetch(`${bulkCtrl}`, {
                    method: 'POST',
                    body: fd
                });
                const isJson = (resp.headers.get('content-type') || '').includes('application/json');
                if (!resp.ok) {
                    const errMsg = isJson ? (await resp.json()).error : await resp.text();
                    throw new Error(errMsg || ('HTTP ' + resp.status));
                }
                const data = isJson ? await resp.json() : {};
                if (!data.ok) throw new Error(data.error || 'Error desconocido');

                const s = data.summary;
                let html = `
      <div>Filas en CSV: <strong>${s.rows_in_csv}</strong></div>
      <div>Filas bancarias procesadas (A/B/C): <strong>${s.bank_rows_to_process}</strong></div>
      <div>Insertados: <strong>${s.inserted}</strong> · Actualizados: <strong>${s.updated}</strong></div>
    `;
                if (s.errors && s.errors.length) {
                    html += `<div style="color:#dc2626;margin-top:.5rem">Errores:<br>${s.errors.map(x=>'- '+x).join('<br>')}</div>`;
                }
                resEl.innerHTML = html;
                alert('Carga masiva finalizada.');
            } catch (err) {
                console.error(err);
                resEl.innerHTML = `<span style="color:#dc2626">Error: ${err.message}</span>`;
                alert('Ocurrió un error al procesar el CSV:\n' + err.message);
            }
        });
    </script>


    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>