<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../models/client_pagoFacturasModel.php';
require_once __DIR__ . '/../../core/SessionManager.php';
SessionManager::start();

$adminUser = SessionManager::getUser();
$admin_id = $adminUser['id'] ?? null;

/**
 * Lee el body crudo y decide si viene JSON o multipart/form-data.
 */
$raw   = file_get_contents('php://input');
$ctype = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = stripos($ctype, 'application/json') !== false;

$input  = $isJson ? (json_decode($raw, true) ?? []) : $_POST;
$action = $input['action'] ?? '';

/** Normaliza números con coma/punto a float */
$toFloat = function ($v) {
    if ($v === null) return 0.0;
    if (is_numeric($v)) return (float)$v;
    $v = preg_replace('/[^\d,\.\-]/', '', (string)$v);
    $v = str_replace(',', '.', $v);
    return is_numeric($v) ? (float)$v : 0.0;
};

try {
    $model = new client_pagoFacturasModel();

    switch ($action) {
        case 'bootstrap': {
            $out = $model->bootstrapLists();
            echo json_encode($out);
            break;
        }

        case 'getByDni': {
            $dni = preg_replace('/\D+/', '', $input['dni'] ?? '');
            if (!$dni) throw new Exception('DNI requerido', 400);
            $user = $model->getUserByDni($dni);
            echo json_encode(['success' => true, 'user' => $user]);
            break;
        }

        case 'payFee': {
            $user_id = (int)($input['user_id'] ?? 0);
            $year    = (int)($input['year'] ?? date('Y'));
            if ($user_id <= 0) throw new Exception('user_id inválido', 400);
            $model->registrarPagoCuota($user_id, $year, null);
            echo json_encode(['success' => true]);
            break;
        }

        case 'create': {
            // multipart/form-data (por PDFs)
            $data = [
                'user_id'     => (int)($_POST['user_id'] ?? 0),
                'dni'         => preg_replace('/\D+/', '', $_POST['dni'] ?? ''),
                'nombre'      => trim($_POST['nombre'] ?? ''),
                'telefono'    => trim($_POST['telefono'] ?? ''),
                'cuit_ben'    => preg_replace('/\D+/', '', $_POST['cuit_ben'] ?? ''),
                'cbu_ben'     => preg_replace('/\D+/', '', $_POST['cbu_ben'] ?? ''),
                'alias_ben'   => trim($_POST['alias_ben'] ?? ''),
                'evento'      => trim($_POST['evento'] ?? ''),
                'monto'       => $toFloat($_POST['monto'] ?? 0),
                'dest_entity' => (int)($_POST['dest_entity'] ?? 0),
                'dest_cuit'   => preg_replace('/\D+/', '', $_POST['dest_cuit'] ?? ''),
                'sellado'     => $toFloat($_POST['sellado'] ?? 0),
                'impuesto_dc' => (float)($_POST['impuesto_dc'] ?? 0),
                'retencion'   => (float)($_POST['retencion'] ?? 0),
                'created_by'  => (int)$admin_id
            ];
            $files = [
                'pedido'  => $_FILES['pdf_pedido'] ?? null,
                'factura' => $_FILES['pdf_factura'] ?? null
            ];

            $resp = $model->crearPago($data, $files);
            echo json_encode(['success' => true, 'payment_id' => $resp['payment_id']]);
            break;
        }

        default: {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
        }
    }
} catch (Throwable $e) {
    $code = (int)$e->getCode();
    if ($code < 400 || $code > 599) $code = 500;
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
