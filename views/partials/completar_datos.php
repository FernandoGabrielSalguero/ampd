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
</head>

<body>
    <h2>Hola <?= htmlspecialchars($nombre) ?>, por favor completá tus datos</h2>
    <form action="guardar_datos.php" method="POST">
        <input type="text" name="dni" placeholder="DNI" required>
        <input type="email" name="correo" placeholder="Correo" required>
        <input type="text" name="telefono" placeholder="Teléfono" required>
        <input type="date" name="fecha_nacimiento" required>
        <input type="text" name="direccion" placeholder="Dirección" required>
        <input type="password" name="nueva_contrasena" placeholder="Nueva contraseña" required>

        <hr>
        <h3>Datos de Facturación</h3>

        <input type="text" name="cuit" placeholder="CUIT" required>
        <input type="text" name="nom_titular" placeholder="Nombre del Titular" required>
        <input type="text" name="cbu" placeholder="CBU" required>
        <input type="text" name="cvu" placeholder="CVU (opcional)">
        <input type="text" name="alias" placeholder="Alias (opcional)">
        <button type="submit">Guardar</button>
    </form>
</body>

</html>