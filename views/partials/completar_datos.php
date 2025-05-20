<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header('Location: /index.php');
    exit;
}

$nombre = $_SESSION['nombre'] ?? '';
$usuario = $_SESSION['usuario'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Completar Datos</title>

    <!-- Íconos de Material Design -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />

    <!-- Tu framework personalizado -->
    <link rel="stylesheet" href="https://www.fernandosalguero.com/cdn/assets/css/framework.css">
    <script src="https://www.fernandosalguero.com/cdn/assets/javascript/framework.js" defer></script>
    <script src="https://www.fernandosalguero.com/cdn/assets/javascript/spinner-global.js"></script>
</head>

<body>

        <section class="content">
            <div class="container mt-4">
                <div class="card p-4">
                    <h2>📝 Completar Datos</h2>

                    <form class="form-modern" action="guardar_datos.php" method="POST">

                        <!-- Datos Personales -->
                        <div class="input-group">
                            <label>Nombre</label>
                            <div class="input-icon">
                                <span class="material-icons">person</span>
                                <input type="text" name="nombre" placeholder="Nombre" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Apellido</label>
                            <div class="input-icon">
                                <span class="material-icons">person</span>
                                <input type="text" name="apellido" placeholder="Apellido" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>DNI</label>
                            <div class="input-icon">
                                <span class="material-icons">badge</span>
                                <input type="text" name="dni" placeholder="DNI" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Correo electrónico</label>
                            <div class="input-icon">
                                <span class="material-icons">mail</span>
                                <input type="email" name="correo" placeholder="Correo" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Teléfono</label>
                            <div class="input-icon">
                                <span class="material-icons">call</span>
                                <input type="text" name="telefono" placeholder="Teléfono" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Fecha de nacimiento</label>
                            <div class="input-icon">
                                <span class="material-icons">calendar_today</span>
                                <input type="date" name="fecha_nacimiento" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Dirección</label>
                            <div class="input-icon">
                                <span class="material-icons">home</span>
                                <input type="text" name="direccion" placeholder="Dirección" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Contraseña nueva</label>
                            <div class="input-icon">
                                <span class="material-icons">lock</span>
                                <input type="password" name="nueva_contrasena" placeholder="Contraseña" required>
                            </div>
                        </div>

                        <hr>

                        <!-- Facturación -->
                        <h3>💳 Datos de Facturación</h3>

                        <div class="input-group">
                            <label>CUIT</label>
                            <div class="input-icon">
                                <span class="material-icons">account_balance</span>
                                <input type="text" name="cuit" placeholder="CUIT" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Nombre del titular</label>
                            <div class="input-icon">
                                <span class="material-icons">person_outline</span>
                                <input type="text" name="nom_titular" placeholder="Titular de cuenta" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>CBU</label>
                            <div class="input-icon">
                                <span class="material-icons">credit_card</span>
                                <input type="text" name="cbu" placeholder="CBU" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label>CVU</label>
                            <div class="input-icon">
                                <span class="material-icons">credit_score</span>
                                <input type="text" name="cvu" placeholder="CVU (opcional)">
                            </div>
                        </div>

                        <div class="input-group">
                            <label>Alias</label>
                            <div class="input-icon">
                                <span class="material-icons">alternate_email</span>
                                <input type="text" name="alias" placeholder="Alias bancario (opcional)">
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-aceptar">Enviar</button>
                            <button type="button" class="btn btn-cancelar" onclick="window.history.back()">Cancelar</button>
                        </div>

                        <!-- Área para mostrar mensajes -->
                        <?php if (isset($_GET['status'])): ?>
                            <div class="alert <?= $_GET['status'] === 'ok' ? 'alert-success' : 'alert-error' ?>">
                                <?= htmlspecialchars($_GET['msg'] ?? '') ?>
                            </div>
                        <?php endif; ?>

                    </form>
                </div>
            </div>
        </section>


    <!-- Spinner Global -->
    <script src="../../views/partials/spinner-global.js"></script>
</body>

</html>