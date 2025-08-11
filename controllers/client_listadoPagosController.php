<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../models/client_listadoPagosModel.php';
require_once __DIR__ . '/../core/SessionManager.php';
SessionManager::start();

$u = SessionManager::getUser();
if (!$u || ($u['role'] ?? '') !== 'Administrativo') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$raw    = file_get_contents('php://input');
$ctype  = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = stripos($ctype, 'application/json') !== false;

$input  = $isJson ? (json_decode($raw, true) ?? []) : $_POST;
$action = $_GET['action'] ?? ($input['action'] ?? '');

$model = new client_listadoPagosModel();

try {
    // Acción especial: descarga JPG (GET)
    if ($action === 'downloadImage') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) throw new Exception('ID inválido', 400);
        $model->createJpegForPayment($id); // imprime y sale
    }

    header('Content-Type: application/json; charset=utf-8');

    switch ($action) {
        case 'list': {
                $f = [
                    'search_nombre' => trim($input['search_nombre'] ?? ''),
                    'search_dni'    => trim($input['search_dni'] ?? '')
                ];
                echo json_encode($model->list($f));
                break;
            }

        case 'get': {
                $id = (int)($input['id'] ?? 0);
                if ($id <= 0) throw new Exception('ID inválido', 400);
                echo json_encode($model->get($id));
                break;
            }

        case 'update': {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception('ID inválido', 400);
                $data = $_POST;
                unset($data['action'], $data['id']);
                $files = [
                    'pedido'  => $_FILES['pedido']  ?? null,
                    'factura' => $_FILES['factura'] ?? null
                ];
                $model->update($id, $data, $files);
                echo json_encode(['success' => true]);
                break;
            }

        case 'delete': {
                $id = (int)($input['id'] ?? 0);
                if ($id <= 0) throw new Exception('ID inválido', 400);
                $model->delete($id);
                echo json_encode(['success' => true]);
                break;
            }

        case 'settle': {
                // multipart (por comprobante)
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) throw new Exception('ID inválido', 400);
                $paid_at = $_POST['paid_at'] ?? date('Y-m-d H:i:s');
                $txn     = trim($_POST['txn_number'] ?? '');
                if ($txn === '') throw new Exception('Número de transacción requerido', 422);
                $receipt = $_FILES['receipt'] ?? null;
                $model->settle($id, $paid_at, $txn, $receipt);
                echo json_encode(['success' => true]);
                break;
            }

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no soportada']);
    }
} catch (Throwable $e) {
    $code = (int)$e->getCode();
    if ($code < 400 || $code > 599) $code = 500;
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
