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
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$model = new VariablesModel();

// JSON body
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?: [];
$action = $_GET['action'] ?? $body['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        /* ===== Lecturas ===== */
        case 'get_values':
            echo json_encode([
                'debit_credit'   => $model->getDebitCreditTax(),
                'retention'      => $model->getRetention(),
                'billing_entity' => $model->getBillingEntity(),
            ]);
            break;

        case 'list_debit_credit':
            $limit = (int)($body['limit'] ?? $_GET['limit'] ?? 50);
            echo json_encode($model->listDebitCreditHistory($limit));
            break;

        case 'list_retention':
            $limit = (int)($body['limit'] ?? $_GET['limit'] ?? 50);
            echo json_encode($model->listRetentionHistory($limit));
            break;

        case 'list_billing_entities':
            $limit = (int)($body['limit'] ?? $_GET['limit'] ?? 50);
            echo json_encode($model->listBillingEntities($limit));
            break;

        /* ===== Escrituras ===== */
        case 'save_debit_credit_tax':
            $value = $body['value'] ?? $_POST['value'] ?? null;
            if ($value === null || !is_numeric($value) || $value < 0) {
                throw new InvalidArgumentException('Valor inválido para impuesto Débito/Crédito.');
            }
            $v = round((float)$value, 4);
            $saved = $model->saveDebitCreditTax($v);
            echo json_encode(['ok' => true, 'data' => $saved]);
            break;

        case 'save_retention':
            $value = $body['value'] ?? $_POST['value'] ?? null;
            if ($value === null || !is_numeric($value) || $value < 0) {
                throw new InvalidArgumentException('Valor inválido para retención.');
            }
            $v = round((float)$value, 4);
            $saved = $model->saveRetention($v);
            echo json_encode(['ok' => true, 'data' => $saved]);
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
            $saved = $model->saveBillingEntity($name, $cuit);
            echo json_encode(['ok' => true, 'data' => $saved]);
            break;

        /* ===== Favoritos ===== */
        case 'favorite_debit_credit':
            $id = (int)($body['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('ID inválido.');
            $model->setFavoriteDebitCredit($id);
            echo json_encode(['ok'=>true]);
            break;

        case 'favorite_retention':
            $id = (int)($body['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('ID inválido.');
            $model->setFavoriteRetention($id);
            echo json_encode(['ok'=>true]);
            break;

        case 'favorite_billing':
            $id = (int)($body['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('ID inválido.');
            $model->setFavoriteBillingEntity($id);
            echo json_encode(['ok'=>true]);
            break;

        /* ===== Eliminar ===== */
        case 'delete_debit_credit':
            $id = (int)($body['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('ID inválido.');
            $ok = $model->deleteDebitCredit($id);
            echo json_encode(['ok'=>$ok]);
            break;

        case 'delete_retention':
            $id = (int)($body['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('ID inválido.');
            $ok = $model->deleteRetention($id);
            echo json_encode(['ok'=>$ok]);
            break;

        case 'delete_billing':
            $id = (int)($body['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) throw new InvalidArgumentException('ID inválido.');
            $ok = $model->deleteBillingEntity($id);
            echo json_encode(['ok'=>$ok]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no soportada']);
    }
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()]);
}
