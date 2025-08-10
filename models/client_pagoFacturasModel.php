<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

class client_pagoFacturasModel
{
    private $conn;

    public function __construct()
    {
        global $pdo;
        $this->conn = $pdo;
    }

    /* ==== Bootstrap de selects ==== */
    public function bootstrapLists(): array
    {
        $entities = $this->conn->query("SELECT id, name, cuit FROM env_billing_entities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $taxes    = $this->conn->query("SELECT id, value, is_favorite FROM env_debit_credit_tax ORDER BY is_favorite DESC, value ASC")->fetchAll(PDO::FETCH_ASSOC);
        $rets     = $this->conn->query("SELECT id, value, is_favorite FROM env_retention ORDER BY is_favorite DESC, value ASC")->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'entities' => $entities, 'taxes' => $taxes, 'retentions' => $rets];
    }

    /* ==== User por DNI + estado de cuota ==== */
    public function getUserByDni(string $dni): array
    {
        $sql = "SELECT U.id AS user_id, U.user_name AS dni, U.email,
                       UP.first_name, UP.contact_phone AS phone
                FROM users U
                LEFT JOIN user_profile UP ON UP.user_id = U.id
                WHERE U.user_name = :dni
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':dni' => $dni]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u) throw new Exception('No existe el socio con ese DNI', 404);

        $sqlB = "SELECT cuit, cbu, alias, titular FROM user_bank_accounts WHERE n_socio = :uid AND slot = 1 LIMIT 1";
        $stmtB = $this->conn->prepare($sqlB);
        $stmtB->execute([':uid' => $u['user_id']]);
        $bank = $stmtB->fetch(PDO::FETCH_ASSOC) ?: ['cuit' => null, 'cbu' => null, 'alias' => null, 'titular' => null];

        $year = (int)date('Y');
        $stmtF = $this->conn->prepare("SELECT paid_at FROM membership_fees WHERE user_id = ? AND year = ? LIMIT 1");
        $stmtF->execute([$u['user_id'], $year]);
        $fee  = $stmtF->fetch(PDO::FETCH_ASSOC);
        $paid = $fee && !empty($fee['paid_at']);

        return [
            'user_id'    => (int)$u['user_id'],
            'dni'        => $u['dni'],
            'email'      => $u['email'],
            'first_name' => $u['first_name'] ?? '',
            'phone'      => $u['phone'] ?? '',
            'cuit'       => $bank['cuit'] ?? '',
            'cbu'        => $bank['cbu'] ?? '',
            'alias'      => $bank['alias'] ?? '',
            'titular'    => $bank['titular'] ?? '',
            'fee_paid'   => (bool)$paid,
            'year'       => $year
        ];
    }

    /* ==== Crear pago + subir archivos + actualizar datos faltantes ==== */
    public function crearPago(array $d, array $files): array
    {
        if (!$d['dni'])         throw new Exception('DNI requerido', 422);
        if (!$d['evento'])      throw new Exception('Evento requerido', 422);
        if ($d['monto'] <= 0)   throw new Exception('Monto inválido', 422);
        if (!$d['dest_entity']) throw new Exception('Seleccione razón social destinatario', 422);

        if (empty($d['user_id'])) {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE user_name = ?");
            $stmt->execute([$d['dni']]);
            $d['user_id'] = (int)$stmt->fetchColumn();
            if (!$d['user_id']) throw new Exception('Socio inexistente para ese DNI', 404);
        }

        $stmt = $this->conn->prepare("SELECT name, cuit FROM env_billing_entities WHERE id = ?");
        $stmt->execute([$d['dest_entity']]);
        $ent = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ent) throw new Exception('Razón social inválida', 422);

        $monto   = round((float)$d['monto'], 2);
        $sellado = round(max(0, (float)$d['sellado']), 2);
        $taxPct  = (float)$d['impuesto_dc'];
        $retPct  = (float)$d['retencion'];
        $taxAmt  = round($monto * ($taxPct / 100), 2);
        $retAmt  = round($monto * ($retPct / 100), 2);
        $total   = round($monto - $sellado - $taxAmt - $retAmt, 2);

        $this->conn->beginTransaction();
        try {
            // PERFIL: upsert suave
            $stmt = $this->conn->prepare("SELECT first_name, contact_phone FROM user_profile WHERE user_id = ?");
            $stmt->execute([$d['user_id']]);
            $prof = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($prof) {
                $fn = $prof['first_name'];
                $ph = $prof['contact_phone'];
                if (!$fn && !empty($d['nombre'])) {
                    $this->conn->prepare("UPDATE user_profile SET first_name = ? WHERE user_id = ?")
                        ->execute([$d['nombre'], $d['user_id']]);
                }
                if (($ph === null || $ph === '') && !empty($d['telefono'])) {
                    $this->conn->prepare("UPDATE user_profile SET contact_phone = ? WHERE user_id = ?")
                        ->execute([$d['telefono'], $d['user_id']]);
                }
            } else {
                $this->conn->prepare("
                    INSERT INTO user_profile (user_id, first_name, last_name, birth_date, contact_phone)
                    VALUES (:uid, :fn, '', CURDATE(), :ph)
                ")->execute([
                    ':uid' => $d['user_id'],
                    ':fn'  => $d['nombre'] ?: '',
                    ':ph'  => $d['telefono'] ?: null
                ]);
            }

            // BANK slot=1: upsert si falta/campo vacío
            $stmt = $this->conn->prepare("SELECT id, cuit, cbu, alias FROM user_bank_accounts WHERE n_socio = ? AND slot = 1");
            $stmt->execute([$d['user_id']]);
            $bank = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($bank) {
                $sql = "UPDATE user_bank_accounts
                        SET dni = :dni,
                            cuit = COALESCE(NULLIF(:cuit,''), cuit),
                            cbu  = COALESCE(NULLIF(:cbu, ''), cbu),
                            alias= COALESCE(NULLIF(:alias,''), alias)
                        WHERE n_socio = :uid AND slot = 1";
                $this->conn->prepare($sql)->execute([
                    ':dni'   => $d['dni'],
                    ':cuit'  => $d['cuit_ben'],
                    ':cbu'   => $d['cbu_ben'],
                    ':alias' => $d['alias_ben'],
                    ':uid'   => $d['user_id']
                ]);
            } else {
                $sql = "INSERT INTO user_bank_accounts (dni, n_socio, slot, cbu, alias, titular, banco, cuit, created_at)
                        VALUES (:dni, :uid, 1, :cbu, :alias, NULL, NULL, :cuit, NOW())";
                $this->conn->prepare($sql)->execute([
                    ':dni'   => $d['dni'],
                    ':uid'   => $d['user_id'],
                    ':cbu'   => $d['cbu_ben'] ?: null,
                    ':alias' => $d['alias_ben'] ?: null,
                    ':cuit'  => $d['cuit_ben'] ?: null
                ]);
            }

            // INSERT del pago
            $sql = "INSERT INTO payments
                    (user_id, event, contract_amount, stamp_amount, debit_credit_tax_rate, debit_credit_tax_amount,
                     retention_rate, retention_amount, total_to_user,
                     beneficiary_cuit, beneficiary_cbu, beneficiary_alias, phone,
                     dest_entity_id, dest_entity_name, dest_entity_cuit,
                     pedido_pdf_path, factura_pdf_path, created_by, created_at)
                    VALUES
                    (:uid, :event, :amount, :stamp, :taxr, :taxa, :retr, :reta, :total,
                     :bcuit, :bcbu, :balias, :phone,
                     :deid, :dename, :decuit,
                     NULL, NULL, :created_by, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':uid'        => $d['user_id'],
                ':event'      => $d['evento'],
                ':amount'     => $monto,
                ':stamp'      => $sellado,
                ':taxr'       => $taxPct,
                ':taxa'       => $taxAmt,
                ':retr'       => $retPct,
                ':reta'       => $retAmt,
                ':total'      => $total,
                ':bcuit'      => $d['cuit_ben'] ?: null,
                ':bcbu'       => $d['cbu_ben'] ?: null,
                ':balias'     => $d['alias_ben'] ?: null,
                ':phone'      => $d['telefono'] ?: null,
                ':deid'       => $d['dest_entity'],
                ':dename'     => $ent['name'],
                ':decuit'     => $ent['cuit'],
                ':created_by' => $d['created_by'] ?: null
            ]);
            $payment_id = (int)$this->conn->lastInsertId();

            // Subir PDFs a /uploads/tax_invoices con nombre {DNI}-{YYYYMMDD_HHMMSS}-{tipo}-{pid}.pdf
            $paths = $this->savePdfFiles($payment_id, $files, $d['dni']);
            if ($paths['pedido'] || $paths['factura']) {
                $this->conn->prepare("UPDATE payments SET pedido_pdf_path = ?, factura_pdf_path = ? WHERE id = ?")
                    ->execute([$paths['pedido'], $paths['factura'], $payment_id]);
            }

            $this->conn->commit();
            return ['payment_id' => $payment_id];
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function savePdfFiles(int $payment_id, array $files, string $dni): array
    {
        // Con tu estructura: /models -> (sube 1) -> raíz del proyecto
        $baseDir = realpath(__DIR__ . '/..');
        if ($baseDir === false) {
            $baseDir = dirname(__DIR__, 1);
        }

        $uploadDir = $baseDir . '/uploads/tax_invoices';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $out = ['pedido' => null, 'factura' => null];
        $dniSafe = preg_replace('/\D+/', '', $dni) ?: 'dni';

        foreach (['pedido', 'factura'] as $key) {
            if (empty($files[$key]) || (is_array($files[$key]) && ($files[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)) continue;

            $f = $files[$key];
            if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new Exception('Error al subir PDF: ' . $key, 400);
            }
            if (($f['size'] ?? 0) > 10 * 1024 * 1024) {
                throw new Exception('PDF supera 10MB: ' . $key, 400);
            }

            $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                throw new Exception('Solo se permiten PDF: ' . $key, 415);
            }

            $stamp = date('Ymd_His');
            $safe  = "{$dniSafe}-{$stamp}-{$key}-{$payment_id}.pdf";
            $dest  = $uploadDir . '/' . $safe;

            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                throw new Exception('No se pudo guardar archivo: ' . $key, 500);
            }

            $out[$key] = '/uploads/tax_invoices/' . $safe; // ruta pública
        }
        return $out;
    }

    public function registrarPagoCuota(int $user_id, int $year, ?string $paid_at = null): void
    {
        if ($year < 2000 || $year > 2100) throw new Exception('Año inválido', 422);
        $paid = $paid_at ? date('Y-m-d H:i:s', strtotime($paid_at)) : date('Y-m-d H:i:s');

        // Idempotente sin índice único
        $stmt = $this->conn->prepare("SELECT id FROM membership_fees WHERE user_id = ? AND year = ? LIMIT 1");
        $stmt->execute([$user_id, $year]);
        $id = $stmt->fetchColumn();

        if ($id) {
            $this->conn->prepare("UPDATE membership_fees SET paid_at = ? WHERE user_id = ? AND year = ?")
                ->execute([$paid, $user_id, $year]);
        } else {
            $this->conn->prepare("INSERT INTO membership_fees (user_id, year, paid_at, created_at) VALUES (?, ?, ?, NOW())")
                ->execute([$user_id, $year, $paid]);
        }
    }
}
