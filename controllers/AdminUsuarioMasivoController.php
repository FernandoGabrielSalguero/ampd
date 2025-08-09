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

            $replace      = isset($_POST['replace']) ? filter_var($_POST['replace'], FILTER_VALIDATE_BOOLEAN) : true;
            $debugHeaders = isset($_POST['debug_headers']) && $_POST['debug_headers'] === '1';

            // Abrir archivo tal cual (CSV)
            $tmpForRead = $_FILES['file']['tmp_name'];
            $fh = fopen($tmpForRead, 'r');
            if (!$fh) throw new RuntimeException('No se pudo leer el archivo.');

            // ===== Autodetección de BOM + delimitador =====
            $first = fgets($fh);
            if ($first === false) throw new RuntimeException('CSV vacío.');
            $firstNoBom = preg_replace('/^\xEF\xBB\xBF/u', '', $first);

            $counts = [
                ';'  => substr_count($firstNoBom, ';'),
                ','  => substr_count($firstNoBom, ','),
                "\t" => substr_count($firstNoBom, "\t"),
            ];
            arsort($counts);
            $delimiter = key($counts);
            if ($counts[$delimiter] === 0) $delimiter = ','; // fallback

            rewind($fh);
            $headers = fgetcsv($fh, 0, $delimiter, '"');
            if ($headers === false) throw new RuntimeException('CSV sin cabecera.');
            if (isset($headers[0])) $headers[0] = preg_replace('/^\xEF\xBB\xBF/u', '', $headers[0]);

            // --- Limpieza y normalización de cabeceras (robusta contra BOM y NBSP) ---
            $cleanHeader = function ($h) {
                $s = (string)$h;

                // 1) Quitar BOM por bytes (EF BB BF) al inicio
                if (strncmp($s, "\xEF\xBB\xBF", 3) === 0) {
                    $s = substr($s, 3);
                }
                // 2) Quitar BOM como U+FEFF por si vino como carácter
                $s = preg_replace('/^\x{FEFF}/u', '', $s);

                // 3) Quitar NBSP (0xC2 0xA0) que Excel a veces mete
                $s = str_replace("\xC2\xA0", ' ', $s);

                // 4) Normalización igual que antes
                $s = trim($s);
                $s = preg_replace('/\s+/', '_', $s);
                $s = strtolower($s);

                return $s;
            };

            $normalizedHeaders = array_map($cleanHeader, $headers);


            // ======= DEBUG OPCIONAL: devolver cabeceras detectadas =======
            if ($debugHeaders) {
                $delimTxt = ($delimiter === "\t" ? 'TAB' : $delimiter);
                // armamos un mensaje plano para que salga en el alert()
                $msg = "Cabeceras detectadas\n" .
                    "delimiter: {$delimTxt}\n" .
                    "raw: " . implode(' | ', array_map(function ($h) {
                        return (string)$h;
                    }, $headers)) . "\n" .
                    "norm: " . implode(' | ', $normalizedHeaders);

                http_response_code(422); // así tu JS lo trata como error y muestra el alert
                echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
                exit;
            }
            // =============================================================


            // Validación de columnas necesarias
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
                if (!in_array($r, $normalizedHeaders, true)) {
                    throw new InvalidArgumentException("Falta la columna requerida: {$r}");
                }
            }
            $idx = array_flip($normalizedHeaders);

            // Helper
            $norm = function ($v) {
                $v = trim((string)$v);
                if ($v === '' || preg_match('/^sin\s+/i', $v)) return null;
                return $v;
            };

            $rows = [];
            $errors = [];
            $line = 1;

            // Leer filas
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

                // Usuario + rol
                $userId = $model->createUserIfMissing($dni, $email, $pass);
                $model->ensureUserHasSocioRole($userId);

                // Cuentas A/B/C -> slots 1/2/3
                $accounts = [
                    1 => [
                        'cbu' => $norm($row[$idx['cbu_a']] ?? ''),
                        'alias' => $norm($row[$idx['alias_a']] ?? ''),
                        'titular' => $norm($row[$idx['titular_a']] ?? ''),
                        'banco' => $norm($row[$idx['banco_a']] ?? ''),
                        'cuit' => $norm($row[$idx['cuit_a']] ?? ''),
                    ],
                    2 => [
                        'cbu' => $norm($row[$idx['cbu_b']] ?? ''),
                        'alias' => $norm($row[$idx['alias_b']] ?? ''),
                        'titular' => $norm($row[$idx['titular_b']] ?? ''),
                        'banco' => $norm($row[$idx['banco_b']] ?? ''),
                        'cuit' => null,
                    ],
                    3 => [
                        'cbu' => $norm($row[$idx['cbu_c']] ?? ''),
                        'alias' => $norm($row[$idx['alias_c']] ?? ''),
                        'titular' => $norm($row[$idx['titular_c']] ?? ''),
                        'banco' => $norm($row[$idx['banco_c']] ?? ''),
                        'cuit' => null,
                    ],
                ];

                foreach ($accounts as $slot => $a) {
                    if ($a['cbu'] || $a['alias'] || $a['titular'] || $a['banco'] || $a['cuit']) {
                        $rows[] = [
                            'dni' => $dni,
                            'n_socio' => (int)$nSocio,
                            'slot' => (int)$slot,
                            'cbu' => $a['cbu'],
                            'alias' => $a['alias'],
                            'titular' => $a['titular'],
                            'banco' => $a['banco'],
                            'cuit' => $a['cuit'],
                        ];
                    }
                }
            }
            fclose($fh);

            $result = $model->upsertBatch($rows, $replace);

            echo json_encode([
                'ok' => true,
                'summary' => [
                    'rows_in_csv' => $line - 1,
                    'bank_rows_to_process' => count($rows),
                    'inserted' => $result['inserted'],
                    'updated'  => $result['updated'],
                    'errors'   => $errors
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
