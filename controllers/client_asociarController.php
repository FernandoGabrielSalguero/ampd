<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/client_asociarModel.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

try {
    $model = new client_asociarModel();

    switch ($action) {
        case 'create':
            $required = ['nombre','email','telefono','dni'];
            foreach ($required as $f) {
                if (empty($input[$f])) throw new Exception("Falta el campo requerido: $f", 400);
            }
            $resp = $model->crearSocio([
                'first_name' => trim($input['nombre']),
                'email'      => trim($input['email']),
                'phone'      => trim($input['telefono']),
                'dni'        => preg_replace('/\D+/', '', $input['dni']),
                'cuit'       => isset($input['cuit']) ? preg_replace('/\D+/', '', $input['cuit']) : null,
                'cbu'        => isset($input['cbu']) ? preg_replace('/\D+/', '', $input['cbu']) : null,
                'alias'      => $input['alias'] ?? null,
                'titular'    => $input['titular_cuenta'] ?? null,
            ]);
            echo json_encode(['success'=>true, 'user_id'=>$resp['user_id']]);
            break;

        case 'list':
            $rows = $model->listarSocios([
                'search_nombre' => $input['search_nombre'] ?? '',
                'search_dni'    => isset($input['search_dni']) ? preg_replace('/\D+/', '', $input['search_dni']) : '',
            ]);
            echo json_encode(['success'=>true, 'rows'=>$rows]);
            break;

        case 'delete':
            $user_id = (int)($input['user_id'] ?? 0);
            if ($user_id <= 0) throw new Exception('user_id inválido', 400);
            $model->eliminarSocioTotal($user_id);
            echo json_encode(['success'=>true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success'=>false, 'message'=>'Acción no soportada']);
    }
} catch (Throwable $e) {
    http_response_code($e->getCode() >= 400 ? $e->getCode() : 500);
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
