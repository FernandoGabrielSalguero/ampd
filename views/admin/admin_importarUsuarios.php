<?php
// Seguridad básica (igual que otras páginas)
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar usuarios - AMPD</title>
    <link rel="stylesheet" href="https://www.fernandosalguero.com/cdn/assets/css/framework.css">
    <script src="https://www.fernandosalguero.com/cdn/assets/javascript/framework.js" defer></script>
    <style>
        .table-preview {
            overflow-x: auto;
            max-height: 400px;
        }
    </style>
</head>
<body>

<div class="layout">
    <div class="main">
        <header class="navbar">
            <button class="btn-icon" onclick="toggleSidebar()">
                <span class="material-icons">menu</span>
            </button>
            <div class="navbar-title">Importar usuarios</div>
        </header>

        <section class="content">
            <div class="card">
                <h2>Subir archivo CSV</h2>
                <form id="formImportar" class="form-modern">
                    <div class="input-group">
                        <label for="csv">Seleccionar archivo CSV (UTF-8, ;)</label>
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
    </div>
</div>

<script>
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
    .catch(err => {
        console.error(err);
        showAlert('error', 'Error al procesar el archivo.');
    });
});

document.getElementById("confirmarImport").addEventListener("click", function() {
    fetch("../../controllers/admin_importarUsuariosController.php", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ data: csvData })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            showAlert("success", data.message);
        } else {
            showAlert("error", data.message);
        }
    })
    .catch(err => {
        console.error(err);
        showAlert("error", "Error durante la importación.");
    });
});
</script>

</body>
</html>
