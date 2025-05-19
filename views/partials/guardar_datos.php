<?php
session_start();
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['id_real'])) {
    die("Sesión no válida.");
}

$idUsuario = $_SESSION['id_real'];
$dni = $_POST['dni'] ?? '';
$correo = $_POST['correo'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$fecha_nacimiento = $_POST['fecha_nacimiento'] ?? '';
$direccion = $_POST['direccion'] ?? '';
$nuevaContrasena = $_POST['nueva_contrasena'] ?? '';

if (!$dni || !$correo || !$telefono || !$fecha_nacimiento || !$direccion || !$nuevaContrasena) {
    die("Faltan datos obligatorios.");
}

// Hash de contraseña
$hash = password_hash($nuevaContrasena, PASSWORD_DEFAULT);

// Insertar o actualizar user_info
$stmt1 = $pdo->prepare("INSERT INTO user_info (usuario_id, dni, correo, tel, fecha_nacimiento, direccion)
                        VALUES (:id, :dni, :correo, :tel, :fecha_nacimiento, :direccion)
                        ON DUPLICATE KEY UPDATE dni=:dni, correo=:correo, tel=:tel, fecha_nacimiento=:fecha_nacimiento, direccion=:direccion");

$stmt1->execute([
    'id' => $idUsuario,
    'dni' => $dni,
    'correo' => $correo,
    'tel' => $telefono,
    'fecha_nacimiento' => $fecha_nacimiento,
    'direccion' => $direccion
]);

// Actualizar contraseña en usuarios
$stmt2 = $pdo->prepare("UPDATE usuarios SET contrasena = :contrasena WHERE id = :id");
$stmt2->execute([
    'contrasena' => $hash,
    'id' => $idUsuario
]);

// Redirigir al dashboard correspondiente
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
