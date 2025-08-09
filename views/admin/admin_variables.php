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

                <div class="card-grid grid-2">
                    <div class="card">
                            <strong>Variable de entorno Impuesto al débito y crédito</strong>
                            <form class="form-grid grid-4" id="form-publicacion" enctype="multipart/form-data">
                                <!-- Título -->
                                <div class="input-group">
                                    <label for="titulo">Título</label>
                                    <div class="input-icon">
                                        <span class="material-icons">title</span>
                                        <input type="text" name="titulo" id="titulo" required>
                                    </div>
                                </div>

                                <!-- Subtítulo -->
                                <div class="input-group">
                                    <label for="subtitulo">Subtítulo</label>
                                    <div class="input-icon">
                                        <span class="material-icons">subtitles</span>
                                        <input type="text" name="subtitulo" id="subtitulo" required>
                                    </div>
                                </div>

                                <!-- Autor -->
                                <div class="input-group">
                                    <label for="autor">Autor</label>
                                    <div class="input-icon">
                                        <span class="material-icons">person</span>
                                        <input type="text" name="autor" id="autor" required>
                                    </div>
                                </div>

                                <!-- Categoría -->
                                <div class="input-group">
                                    <label for="categoria_id">Categoría</label>
                                    <div class="input-icon">
                                        <span class="material-icons">category</span>
                                        <select name="categoria_id" id="select-categoria" required>
                                            <option value="">Seleccionar categoría</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Subcategoría -->
                                <div class="input-group">
                                    <label for="subcategoria_id">Subcategoría</label>
                                    <div class="input-icon">
                                        <span class="material-icons">category</span>
                                        <select name="subcategoria_id" id="select-subcategoria" required disabled>
                                            <option value="">Seleccionar subcategoría</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Archivo -->
                                <div class="input-group">
                                    <label for="archivo">Archivo</label>
                                    <div class="input-icon">
                                        <span class="material-icons">attach_file</span>
                                        <input type="file" name="archivo" id="archivo" accept=".pdf" required>
                                    </div>
                                </div>

                                <!-- Descripción -->
                                <div class="input-group" style="grid-column: span 4;">
                                    <label for="descripcion">Descripción</label>
                                    <textarea name="descripcion" id="descripcion" rows="4"
                                        placeholder="Descripción de la publicación..." required></textarea>
                                </div>

                                <!-- Botón guardar -->
                                <div style="grid-column: span 4; text-align: right;">
                                    <button type="submit" class="btn btn-disabled" id="btn-guardar" disabled>Guardar publicación</button>
                                </div>
                            </form>
                    </div>
                    <div class="card">
                        <h3>Variable de entorno Retención</h3>
                        <p>Contenido 2</p>
                    </div>
                    <div class="card">
                        <h3>Variable de entorno Entidad facturación</h3>
                        <p>Contenido 3</p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="../../views/partials/spinner-global.js"></script>

    <script>
        console.log(<?php echo json_encode($_SESSION); ?>);
    </script>
</body>

</html>