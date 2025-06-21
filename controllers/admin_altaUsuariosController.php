<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config.php'; // Ruta a config.php

header('Content-Type: application/json');

try {
    // Validar datos obligatorios
    if (!isset($_POST['user_nombre'], $_POST['user_dni'], $_POST['user_correo'], $_POST['user_telefono'])) {
        throw new Exception('Faltan campos obligatorios');
    }

    // Recibir variables del form
    $nombre = $_POST['user_nombre'];
    $dni = $_POST['user_dni'];
    $correo = $_POST['user_correo'];
    $telefono = $_POST['user_telefono'];
    $usuario = $dni;
    $contrasenaHash = password_hash($dni, PASSWORD_BCRYPT);

    // Insertar en tabla usuarios
    $stmt = $pdo->prepare("INSERT INTO usuarios (usuario, contrasena, nombre, correo, telefono, dni) 
                            VALUES (:usuario, :contrasena, :nombre, :correo, :telefono, :dni)");
    $stmt->execute([
        ':usuario' => $usuario,
        ':contrasena' => $contrasenaHash,
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':telefono' => $telefono,
        ':dni' => $dni
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Usuario creado correctamente.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
