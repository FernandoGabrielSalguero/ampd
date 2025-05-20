<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['id_real'])) {
    die("Sesión no válida.");
}

$idUsuario = $_SESSION['id_real'];

// Datos personales
$nombre = $_POST['nombre'] ?? '';
$apellido = $_POST['apellido'] ?? '';
$dni = $_POST['dni'] ?? '';
$correo = $_POST['correo'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$nuevaContrasena = $_POST['nueva_contrasena'] ?? '';

// Datos de facturación
$cuit = $_POST['cuit'] ?? '';
$nom_titular = $_POST['nom_titular'] ?? '';
$cbu = $_POST['cbu'] ?? '';
$cvu = $_POST['cvu'] ?? '';
$alias = $_POST['alias'] ?? '';
$activo = 1;

if (
    !$nombre || !$apellido || !$dni || !$correo || !$telefono || !$fecha_nacimiento || !$direccion || !$nuevaContrasena ||
    !$cuit || !$nom_titular || !$cbu
) {
    die("Faltan datos obligatorios.");
}

// Hashear contraseña
$hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

// Insertar o actualizar user_info
$stmt1 = $pdo->prepare("
    INSERT INTO user_info (usuario_id, nombre, apellido, dni, correo, tel, fecha_nacimiento, direccion)
    VALUES (:id, :nombre, :apellido, :dni, :correo, :tel, :fecha_nacimiento, :direccion)
    ON DUPLICATE KEY UPDATE
        nombre = VALUES(nombre),
        apellido = VALUES(apellido),
        dni = VALUES(dni),
        correo = VALUES(correo),
        tel = VALUES(tel),
        fecha_nacimiento = VALUES(fecha_nacimiento),
        direccion = VALUES(direccion)
");
$stmt1->execute([
    'id' => $idUsuario,
    'nombre' => $nombre,
    'apellido' => $apellido,
    'dni' => $dni,
    'correo' => $correo,
    'tel' => $telefono,
    'fecha_nacimiento' => $fecha_nacimiento,
    'direccion' => $direccion
]);

// Insertar o actualizar facturación
$stmt2 = $pdo->prepare("
    INSERT INTO facturacion (usuario_id, cuit, nom_titular, cbu, cvu, alias, activo)
    VALUES (:id, :cuit, :nom_titular, :cbu, :cvu, :alias, :activo)
    ON DUPLICATE KEY UPDATE
        cuit = VALUES(cuit),
        nom_titular = VALUES(nom_titular),
        cbu = VALUES(cbu),
        cvu = VALUES(cvu),
        alias = VALUES(alias),
        activo = VALUES(activo)
");
$stmt2->execute([
    'id' => $idUsuario,
    'cuit' => $cuit,
    'nom_titular' => $nom_titular,
    'cbu' => $cbu,
    'cvu' => $cvu,
    'alias' => $alias,
    'activo' => $activo
]);

// Actualizar contraseña
$stmt3 = $pdo->prepare("UPDATE usuarios SET contrasena = :hash WHERE id = :id");
$stmt3->execute([
    'hash' => $hash,
    'id' => $idUsuario
]);

// Redirigir según rol
$rol = $_SESSION['rol'] ?? 'asociado';
switch ($rol) {
    case 'admin':
        header('Location: /views/admin/admin_dashboard.php');
        break;
    case 'asistente':
        header('Location: /views/asistente/asistente_dashboard.php');
        break;
    case 'asociado':
    default:
        header('Location: /views/asociado/asociado_dashboard.php');
        break;
}
exit;
