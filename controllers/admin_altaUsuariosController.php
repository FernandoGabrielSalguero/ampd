<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../models/admin_altaUsuariosModel.php';

header('Content-Type: application/json');

try {
    $model = new AdminAltaUsuariosModel($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $filtroDNI = $_GET['dni'] ?? '';
        $filtroNombre = $_GET['nombre'] ?? '';
        $usuarios = $model->obtenerUsuarios($filtroDNI, $filtroNombre);
        echo json_encode(['status' => 'success', 'data' => $usuarios]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['user_nombre'], $_POST['user_dni'], $_POST['user_correo'], $_POST['user_telefono'])) {
            throw new Exception('Faltan campos obligatorios');
        }

        $model->crearUsuario(
            $_POST['user_nombre'],
            $_POST['user_dni'],
            $_POST['user_correo'],
            $_POST['user_telefono']
        );

        echo json_encode(['status' => 'success', 'message' => 'Usuario creado correctamente.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
