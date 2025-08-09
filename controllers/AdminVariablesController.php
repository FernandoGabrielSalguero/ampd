<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../../core/SessionManager.php';
SessionManager::start();

$user = SessionManager::getUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Sesión expirada']);
    exit;
}
if (!isset($user['role']) || $user['role'] !== 'Super_admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso restringido']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// 🔹 Conexión a la base de datos
require_once __DIR__ . '/../../config.php'; // esto carga $pdo global

require_once __DIR__ . '/AdminVariablesModel.php';
$model = new AdminVariablesModel($pdo);

// Helpers
function decimalFromInput(?string $s): float {
    $s = trim((string)$s);
    $s = str_replace(['.', ','], ['.', '.'], str_replace('.', '', $s)); 
    if (!is_numeric($s)) {
        throw new InvalidArgumentException('Valor numérico inválido');
    }
    return (float)$s;
}

function validateCuit(string $cuit): bool {
    $cuit = preg_replace('/\D+/', '', $cuit);
    return strlen($cuit) >= 8 && strlen($cuit) <= 20;
}

// Router
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$type   = $_GET['type']   ?? $_POST['type']   ?? null;

try {
    if (!$type) {
        throw new InvalidArgumentException('Falta parámetro "type".');
    }

    switch ($type) {
        case 'debit_credit_tax':
            if ($method === 'GET' && $action === 'list') {
                echo json_encode(['ok' => true, 'data' => $model->listDebitCreditTax()]);
                exit;
            }
            if ($method === 'POST' && $action === 'create') {
                $value = decimalFromInput($_POST['value'] ?? null);
                $id = $model->createDebitCreditTax($value);
                echo json_encode(['ok' => true, 'id' => $id]);
                exit;
            }
            if ($method === 'POST' && $action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $value = decimalFromInput($_POST['value'] ?? null);
                $model->updateDebitCreditTax($id, $value);
                echo json_encode(['ok' => true]);
                exit;
            }
            if ($method === 'POST' && $action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $model->deleteDebitCreditTax($id);
                echo json_encode(['ok' => true]);
                exit;
            }
            break;

        case 'retention':
            if ($method === 'GET' && $action === 'list') {
                echo json_encode(['ok' => true, 'data' => $model->listRetention()]);
                exit;
            }
            if ($method === 'POST' && $action === 'create') {
                $value = decimalFromInput($_POST['value'] ?? null);
                $id = $model->createRetention($value);
                echo json_encode(['ok' => true, 'id' => $id]);
                exit;
            }
            if ($method === 'POST' && $action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $value = decimalFromInput($_POST['value'] ?? null);
                $model->updateRetention($id, $value);
                echo json_encode(['ok' => true]);
                exit;
            }
            if ($method === 'POST' && $action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $model->deleteRetention($id);
                echo json_encode(['ok' => true]);
                exit;
            }
            break;

        case 'billing_entity':
            if ($method === 'GET' && $action === 'list') {
                echo json_encode(['ok' => true, 'data' => $model->listBillingEntities()]);
                exit;
            }
            if ($method === 'POST' && $action === 'create') {
                $name = trim((string)($_POST['name'] ?? ''));
                $cuit = trim((string)($_POST['cuit'] ?? ''));
                if ($name === '' || $cuit === '' || !validateCuit($cuit)) {
                    throw new InvalidArgumentException('Nombre/CUIT inválidos.');
                }
                $id = $model->createBillingEntity($name, $cuit);
                echo json_encode(['ok' => true, 'id' => $id]);
                exit;
            }
            if ($method === 'POST' && $action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                $cuit = trim((string)($_POST['cuit'] ?? ''));
                if ($id <= 0 || $name === '' || $cuit === '' || !validateCuit($cuit)) {
                    throw new InvalidArgumentException('Datos inválidos para actualizar.');
                }
                $model->updateBillingEntity($id, $name, $cuit);
                echo json_encode(['ok' => true]);
                exit;
            }
            if ($method === 'POST' && $action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $model->deleteBillingEntity($id);
                echo json_encode(['ok' => true]);
                exit;
            }
            break;

        default:
            throw new InvalidArgumentException('Tipo no soportado.');
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
