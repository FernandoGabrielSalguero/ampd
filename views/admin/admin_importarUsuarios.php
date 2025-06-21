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
                        <span class="material-icons" style="color: #5b21b6;">upload_file</span><span class="link-text">Pago Facturas</span>
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
                <div class="card">
                    <h2>Subir archivo CSV</h2>
                    <form id="formImportar" class="form-modern">
                        <div class="input-group">
                            <label for="csv">Seleccionar archivo CSV (UTF-8)</label>
                            <input type="file" id="csv" name="csv" accept=".csv" required>
                        </div>
                        <div class="form-buttons">
                            <button class="btn btn-aceptar" type="submit">Previsualizar</button>
                        </div>
                    </form>
                </div>

                <div class="card hidden" id="previewCard">
                    <h2>Vista previa</h2>
                    <div class="table-preview">
                        <table class="data-table" id="previewTable"></table>
                    </div>
                    <div class="form-buttons">
                        <button class="btn btn-aceptar" id="confirmarImport">Confirmar importación</button>
                    </div>
                </div>

                <div class="alert-container" id="alertContainer"></div>
            </section>

            <!-- Modal de Progreso -->
            <div id="modalProgreso" class="modal hidden">
                <div class="modal-content" style="max-width: 500px;">
                    <h3>Importando registros...</h3>
                    <p id="progresoInfo">Iniciando...</p>
                    <progress id="barraProgreso" value="0" max="100" style="width: 100%;"></progress>
                    <div id="progresoResumen" style="margin-top: 10px; font-size: 14px;"></div>
                    <div style="text-align: right; margin-top: 15px;">
                        <button id="cerrarModalProgreso" class="btn btn-cancelar hidden">Cerrar</button>
                    </div>
                </div>
            </div>

            <!-- Reporte de errores -->
            <div id="listaErrores" class="card hidden" style="margin-top: 20px;">
                <h3>Errores encontrados</h3>
                <ul id="listaErroresUl" style="font-size: 14px; line-height: 1.5; max-height: 300px; overflow-y: auto;"></ul>
                <div style="text-align: right; margin-top: 10px;">
                    <button class="btn btn-cancelar" id="descargarErrores">Descargar errores</button>
                </div>
            </div>

        </div>
    </div>
    <!-- Spinner Global -->
    <!-- <script src="../../views/partials/spinner-global.js"></script> -->

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
        let csvData = [];

        document.getElementById("formImportar").addEventListener("submit", function(e) {
            e.preventDefault();
            const input = document.getElementById("csv");
            const file = input.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append("csv", file);

            fetch("../../controllers/admin_importarUsuariosController.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        csvData = data.data;
                        const tabla = document.getElementById("previewTable");
                        tabla.innerHTML = "";

                        if (csvData.length > 0) {
                            const headers = Object.keys(csvData[0]);
                            tabla.innerHTML += "<thead><tr>" + headers.map(h => `<th>${h}</th>`).join("") + "</tr></thead>";
                            tabla.innerHTML += "<tbody>" + csvData.map(row =>
                                "<tr>" + headers.map(h => `<td>${row[h]}</td>`).join("") + "</tr>"
                            ).join("") + "</tbody>";
                            document.getElementById("previewCard").classList.remove("hidden");
                        } else {
                            showAlert('error', 'El archivo no contiene datos válidos.');
                        }
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(async err => {
                    const errorText = await err.text?.(); // Si fetch lanza un error y hay respuesta
                    console.error("Error en fetch:", errorText);
                    showAlert("error", "Error inesperado: " + errorText.slice(0, 300));
                });
        });

        // Confirmar importación
        document.getElementById("confirmarImport").addEventListener("click", async function() {
            const total = csvData.length;
            if (!total) return;

            // Mostrar modal
            const modal = document.getElementById("modalProgreso");
            const barra = document.getElementById("barraProgreso");
            const info = document.getElementById("progresoInfo");
            const resumen = document.getElementById("progresoResumen");
            modal.classList.remove("hidden");

            // Inicializar contadores
            let insertados = 0;
            let actualizados = 0;
            let errores = 0;
            let procesados = 0;
            const chunkSize = 10;
            const inicio = Date.now();

            // Procesar en bloques de 10
            for (let i = 0; i < total; i += chunkSize) {
                const chunk = csvData.slice(i, i + chunkSize);

                try {
                    const res = await fetch("../../controllers/admin_importarUsuariosController.php", {
                        method: "PUT",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            data: chunk
                        })
                    });

                    const text = await res.text();
                    let result;
                    try {
                        result = JSON.parse(text);
                        if (result.status === "success") {
                            insertados += result.insertados || 0;
                            actualizados += result.actualizados || 0;
                            errores += (result.errores || []).length;
                        } else {
                            errores += chunk.length;
                            console.warn("Error backend:", result.message);
                        }
                    } catch (parseErr) {
                        errores += chunk.length;
                        console.error("❌ Respuesta no válida JSON:", text);
                        result = null; // prevenir uso posterior
                    }

                    if (result && result.errores && result.errores.length > 0) {
                        console.warn("Errores detectados:");
                        console.warn(result.errores);
                    }


                } catch (err) {
                    console.error("Error en bloque", err);
                    errores += chunk.length;
                }

                // Actualizar progreso
                procesados = Math.min(i + chunk.length, total);
                barra.value = Math.round((procesados / total) * 100);
                const tiempoTranscurrido = Math.round((Date.now() - inicio) / 1000);
                const tiempoEstimado = Math.round((tiempoTranscurrido / procesados) * total);
                const tiempoRestante = Math.max(0, tiempoEstimado - tiempoTranscurrido);

                info.innerText = `Procesados: ${procesados} / ${total}`;
                resumen.innerHTML = `
                ✅ Insertados: ${insertados} <br>
                🔁 Actualizados: ${actualizados} <br>
                ⚠️ Errores: ${errores} <br>
                ⏱ Tiempo estimado: ${tiempoEstimado}s <br>
                🕒 Tiempo transcurrido: ${tiempoTranscurrido}s <br>
                ⌛ Estimado restante: ${tiempoRestante}s
                `;

                // Mostrar resumen de errores si existen
                if (errores > 0) {
                    console.warn("Errores detectados:");
                    console.warn(result.errores || []);
                }

                // Mostrar errores al finalizar todo
                const lista = document.getElementById("listaErroresUl");
                const cardErrores = document.getElementById("listaErrores");
                lista.innerHTML = "";

                let erroresTotales = [];

                try {
                    csvData.forEach((fila, i) => {
                        // Este resultado parcial es por bloque
                        if (fila._error) {
                            erroresTotales.push(fila._error);
                        }
                    });
                } catch (e) {
                    console.warn("No se pudieron mapear errores por fila.");
                }

                if (errores > 0 && result && Array.isArray(result.errores)) {
                    result.errores.forEach(err => {
                        const li = document.createElement("li");
                        li.innerHTML = `🟠 Fila <strong>${err.fila}</strong> — DNI <strong>${err.dni}</strong>: ${err.error}`;
                        lista.appendChild(li);
                    });

                    cardErrores.classList.remove("hidden");
                }
            }

            info.innerText = "✅ Proceso finalizado.";
            document.getElementById("cerrarModalProgreso").classList.remove("hidden");


        });

        document.getElementById("cerrarModalProgreso").addEventListener("click", () => {
            document.getElementById("modalProgreso").classList.add("hidden");
        });
    </script>
</body>

</html>