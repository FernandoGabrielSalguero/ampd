<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../models/admin_altaUsuariosModel.php';

header('Content-Type: application/json');

try {
    $model = new AdminAltaUsuariosModel($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Si viene con detalle=1&id=xx => obtener info extendida
        if (isset($_GET['detalle']) && $_GET['detalle'] == 1 && isset($_GET['id'])) {
            $id = $_GET['id'];

            // Query a cada tabla asociada
            $stmt = $pdo->prepare("SELECT * FROM user_info WHERE usuario_id = ?");
            $stmt->execute([$id]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'user_direccion' => '',
                'user_localidad' => '',
                'user_fecha_nacimiento' => ''
            ];
            $stmt = $pdo->prepare("SELECT disciplina_id FROM user_disciplinas WHERE usuario_id = ?");
            $stmt->execute([$id]);
            $disciplinas = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $stmt = $pdo->prepare("SELECT disciplina FROM user_disciplina WHERE usuario_id = ?");
            $stmt->execute([$id]);
            $disciplinaLibre = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'disciplina' => ''
            ];
            $stmt = $pdo->prepare("SELECT * FROM user_bancarios WHERE usuario_id = ?");
            $stmt->execute([$id]);
            $bancarios = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'alias_a' => '',
                'cbu_a' => '',
                'titular_a' => '',
                'cuit_a' => '',
                'banco_a' => ''
            ];
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'info' => $info,
                    'disciplinas' => $disciplinas,
                    'disciplinaLibre' => $disciplinaLibre,
                    'bancarios' => $bancarios
                ]
            ]);
            exit;
        }

        // Si no se pidió detalle, seguir con la búsqueda normal
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

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['id'] ?? null;

        if (!$id) {
            throw new Exception("ID no recibido");
        }

        $model->eliminarUsuario($id);
        echo json_encode(['status' => 'success', 'message' => 'Usuario eliminado.']);
        exit;
    }

    // Método no permitido
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
