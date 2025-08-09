<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/SessionManager.php';
require_once __DIR__ . '/../models/AdminUsuarioMasivoModel.php';

header('Content-Type: application/json; charset=utf-8');

SessionManager::start();
$user = SessionManager::getUser();
// Solo Super_admin
if (!$user || !isset($user['role']) || $user['role'] !== 'Super_admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$model  = new AdminUsuarioMasivoModel();

try {
    switch ($action) {
        case 'upload_csv':
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('No se recibió archivo o hubo un error de subida.');
            }
            $replace = isset($_POST['replace']) ? filter_var($_POST['replace'], FILTER_VALIDATE_BOOLEAN) : true;

            $fh = fopen($_FILES['file']['tmp_name'], 'r');
            if (!$fh) throw new RuntimeException('No se pudo leer el archivo.');

            // ===== Cabeceras con autodetección robusta de BOM y delimitador =====
            $first = fgets($fh);
            if ($first === false) {
                throw new RuntimeException('CSV vacío.');
            }

            // Quitar BOM si lo hay
            $first = preg_replace('/^\xEF\xBB\xBF/u', '', $first);

            // Detectar delimitador por ocurrencias (prefiere el de mayor conteo)
            $counts = [
                ';'  => substr_count($first, ';'),
                ','  => substr_count($first, ','),
                "\t" => substr_count($first, "\t"),
            ];
            arsort($counts);
            $delimiter = key($counts);
            if ($counts[$delimiter] === 0) {
                // fallback seguro
                $delimiter = ',';
            }

            // Reposicionar y leer cabeceras con el delimitador correcto
            rewind($fh);
            $headers = fgetcsv($fh, 0, $delimiter, '"');
            if ($headers === false) {
                throw new RuntimeException('CSV sin cabecera.');
            }
            if (isset($headers[0])) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/u', '', $headers[0]);
            }

            // Normalizar nombres de columna
            $headers = array_map(function ($h) {
                $h = trim((string)$h);
                $h = preg_replace('/\s+/', '_', $h);
                return strtolower($h);
            }, $headers);

            // (Opcional) Log para debug si algo falla en prod
            // error_log('CSV headers: ' . json_encode($headers, JSON_UNESCAPED_UNICODE));

            // Validar columnas requeridas
            $required = [
                'email',
                'first_name',
                'dni',
                'n_socio',
                'contact_phone',
                'cbu_a',
                'alias_a',
                'titular_a',
                'banco_a',
                'user_name',
                'pass',
                'cuit_a',
                'cbu_b',
                'alias_b',
                'titular_b',
                'banco_b',
                'cbu_c',
                'alias_c',
                'titular_c',
                'banco_c'
            ];
            foreach ($required as $r) {
                if (!in_array($r, $headers, true)) {
                    throw new InvalidArgumentException("Falta la columna requerida: {$r}");
                }
            }
            $idx = array_flip($headers);

            // ===== Helpers =====
            $norm = function ($v) {
                $v = trim((string)$v);
                if ($v === '' || preg_match('/^sin\s+/i', $v)) return null;
                return $v;
            };

            $rows   = [];
            $errors = [];
            $line   = 1;

            // ===== Lectura de filas =====
            while (($row = fgetcsv($fh, 0, $delimiter, '"')) !== false) {
                $line++;

                $dni    = $norm($row[$idx['dni']] ?? '');
                $nSocio = $norm($row[$idx['n_socio']] ?? '');
                $email  = $norm($row[$idx['email']] ?? '');
                $pass   = (string)($row[$idx['pass']] ?? '');

                if (!$dni || !preg_match('/^\d+$/', $dni)) {
                    $errors[] = "Línea {$line}: DNI inválido.";
                    continue;
                }
                if (!$nSocio || !preg_match('/^\d+$/', $nSocio)) {
                    $errors[] = "Línea {$line}: n_socio inválido.";
                    continue;
                }
                if ($pass === '') {
                    $errors[] = "Línea {$line}: pass vacío.";
                    continue;
                }

                // Crear usuario (si no existe) y asegurar rol 'socio'
                $userId = $model->createUserIfMissing($dni, $email, $pass);
                $model->ensureUserHasSocioRole($userId);

                // Map A/B/C -> slots 1/2/3
                $accounts = [
                    1 => [
                        'cbu'     => $norm($row[$idx['cbu_a']] ?? ''),
                        'alias'   => $norm($row[$idx['alias_a']] ?? ''),
                        'titular' => $norm($row[$idx['titular_a']] ?? ''),
                        'banco'   => $norm($row[$idx['banco_a']] ?? ''),
                        'cuit'    => $norm($row[$idx['cuit_a']] ?? ''),
                    ],
                    2 => [
                        'cbu'     => $norm($row[$idx['cbu_b']] ?? ''),
                        'alias'   => $norm($row[$idx['alias_b']] ?? ''),
                        'titular' => $norm($row[$idx['titular_b']] ?? ''),
                        'banco'   => $norm($row[$idx['banco_b']] ?? ''),
                        'cuit'    => null,
                    ],
                    3 => [
                        'cbu'     => $norm($row[$idx['cbu_c']] ?? ''),
                        'alias'   => $norm($row[$idx['alias_c']] ?? ''),
                        'titular' => $norm($row[$idx['titular_c']] ?? ''),
                        'banco'   => $norm($row[$idx['banco_c']] ?? ''),
                        'cuit'    => null,
                    ],
                ];

                foreach ($accounts as $slot => $a) {
                    if ($a['cbu'] || $a['alias'] || $a['titular'] || $a['banco'] || $a['cuit']) {
                        $rows[] = [
                            'dni'     => $dni,
                            'n_socio' => (int)$nSocio,
                            'slot'    => (int)$slot,
                            'cbu'     => $a['cbu'],
                            'alias'   => $a['alias'],
                            'titular' => $a['titular'],
                            'banco'   => $a['banco'],
                            'cuit'    => $a['cuit'],
                        ];
                    }
                }
            }
            fclose($fh);

            // UPSERT de cuentas bancarias
            $result = $model->upsertBatch($rows, $replace);

            echo json_encode([
                'ok' => true,
                'summary' => [
                    'rows_in_csv'          => $line - 1,
                    'bank_rows_to_process' => count($rows),
                    'inserted'             => $result['inserted'],
                    'updated'              => $result['updated'],
                    'errors'               => $errors
                ]
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no soportada']);
    }
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()]);
}
