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
// Datos de facturación
$cuit = $_POST['cuit'] ?? '';
$nom_titular = $_POST['nom_titular'] ?? '';
$cbu = $_POST['cbu'] ?? '';
$cvu = $_POST['cvu'] ?? '';
$alias = $_POST['alias'] ?? '';
$activo = 1; // siempre activo al completar datos

// Validación mínima
if (!$cuit || !$nom_titular || !$cbu) {
    die("Faltan datos de facturación obligatorios.");
}

$stmt2 = $pdo->prepare("
    INSERT INTO facturacion (usuario_id, cuit, nom_titular, cbu, cvu, alias, activo)
    VALUES (:usuario_id, :cuit, :nom_titular, :cbu, :cvu, :alias, :activo)
    ON DUPLICATE KEY UPDATE
        cuit = VALUES(cuit),
        nom_titular = VALUES(nom_titular),
        cbu = VALUES(cbu),
        cvu = VALUES(cvu),
        alias = VALUES(alias),
        activo = VALUES(activo)
");

$stmt2->execute([
    'usuario_id' => $idUsuario,
    'cuit' => $cuit,
    'nom_titular' => $nom_titular,
    'cbu' => $cbu,
    'cvu' => $cvu,
    'alias' => $alias,
    'activo' => $activo
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
