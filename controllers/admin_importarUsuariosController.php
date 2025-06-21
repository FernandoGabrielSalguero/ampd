<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/admin_importarUsuariosModel.php';

header('Content-Type: application/json');

try {
    $model = new AdminImportarUsuariosModel($pdo);

    // 1. Previsualizar CSV
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv'])) {
        $csvFile = $_FILES['csv']['tmp_name'];
        $rows = [];

        if (($handle = fopen($csvFile, "r")) !== false) {
            $headers = fgetcsv($handle, 10000, ";");
            while (($data = fgetcsv($handle, 10000, ";")) !== false) {
                $rows[] = array_combine($headers, $data);
            }
            fclose($handle);
        }

        echo json_encode(['status' => 'success', 'data' => $rows]);
        exit;
    }

    // 2. Importar CSV
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $input = json_decode(file_get_contents("php://input"), true);
        if (!isset($input['data']) || !is_array($input['data'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
            exit;
        }

        $errores = [];
        $insertados = 0;
        $actualizados = 0;

        foreach ($input['data'] as $i => $row) {
            try {
                // Le pedimos al modelo que nos diga si fue insertado o actualizado
                $resultado = $model->importarFila($row);
                if ($resultado === 'insertado') {
                    $insertados++;
                } elseif ($resultado === 'actualizado') {
                    $actualizados++;
                }
            } catch (Exception $e) {
                $errores[] = [
                    'fila' => $i + 1,
                    'dni' => $row['dni'] ?? '',
                    'error' => $e->getMessage()
                ];
            }
        }

        echo json_encode([
            'status' => 'success',
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'errores' => $errores
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
