<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/SessionManager.php';
require_once __DIR__ . '/../models/AdminVariablesModel.php';

SessionManager::start();
$user = SessionManager::getUser();
if (!$user || !isset($user['role']) || $user['role'] !== 'Super_admin') {
    http_response_code(403);
    echo json_encode(['error'=>'Acceso denegado']);
    exit;
}

$variablesModel = new VariablesModel();

// Acepto JSON body
$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$action = $_GET['action'] ?? $body['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'get_values':
            echo json_encode([
                'debit_credit'  => $variablesModel->getDebitCreditTax(),
                'retention'     => $variablesModel->getRetention(),
                'billing_entity'=> $variablesModel->getBillingEntity(),
            ]);
            break;

        case 'save_debit_credit_tax':
            $value = $body['value'] ?? $_POST['value'] ?? null;
            if ($value === null || !is_numeric($value) || $value < 0) {
                throw new InvalidArgumentException('Valor inválido para impuesto Débito/Crédito.');
            }
            $v = round((float)$value, 4);
            $saved = $variablesModel->saveDebitCreditTax($v);
            echo json_encode(['ok'=>true,'data'=>$saved]);
            break;

        case 'save_retention':
            $value = $body['value'] ?? $_POST['value'] ?? null;
            if ($value === null || !is_numeric($value) || $value < 0) {
                throw new InvalidArgumentException('Valor inválido para retención.');
            }
            $v = round((float)$value, 4);
            $saved = $variablesModel->saveRetention($v);
            echo json_encode(['ok'=>true,'data'=>$saved]);
            break;

        case 'save_billing_entity':
            $name = trim($body['name'] ?? $_POST['name'] ?? '');
            $cuit = trim($body['cuit'] ?? $_POST['cuit'] ?? '');
            if ($name === '') {
                throw new InvalidArgumentException('El nombre es obligatorio.');
            }
            if (!preg_match('/^\d{7,20}$/', $cuit)) {
                throw new InvalidArgumentException('CUIT inválido. Solo dígitos (7 a 20).');
            }
            $saved = $variablesModel->saveBillingEntity($name, $cuit);
            echo json_encode(['ok'=>true,'data'=>$saved]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error'=>'Acción no soportada']);
    }
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['error'=>$e->getMessage()]);
}
