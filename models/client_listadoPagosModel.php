<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

class client_listadoPagosModel
{
    private $conn;

    public function __construct()
    {
        global $pdo;
        $this->conn = $pdo;
    }

    /* ========= Helpers ========= */
    private function asFloat($v): float
    {
        if ($v === null) return 0.0;
        if (is_numeric($v)) return (float)$v;
        $v = preg_replace('/[^\d,\.\-]/', '', (string)$v);
        $v = str_replace(',', '.', $v);
        return is_numeric($v) ? (float)$v : 0.0;
    }

    private function unlinkIfExists(?string $path): void
    {
        if (!$path) return;
        $abs = realpath(__DIR__ . '/..'); // -> raíz del proyecto
        if ($abs === false) $abs = dirname(__DIR__, 1);
        $full = $abs . $path;
        if (is_file($full)) @unlink($full);
    }

    private function ensureDir(string $rel): string
    {
        $base = realpath(__DIR__ . '/..') ?: dirname(__DIR__, 1);
        $dir  = $base . $rel;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        return $dir;
    }

    /* ========= LIST ========= */
    // Devuelve filas para la grilla (con filtros por nombre y dni)
    public function list(array $filtros): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['search_nombre'])) {
            $where[] = "UP.first_name LIKE :n";
            $params[':n'] = '%' . $filtros['search_nombre'] . '%';
        }
        if (!empty($filtros['search_dni'])) {
            $where[] = "U.user_name LIKE :d";
            $params[':d'] = '%' . preg_replace('/\D+/', '', $filtros['search_dni']) . '%';
        }

        $wsql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = "
            SELECT 
                P.id,
                P.created_at,
                P.event,
                P.contract_amount,
                P.total_to_user,
                P.pedido_pdf_path,
                P.factura_pdf_path,
                U.user_name AS dni,
                COALESCE(UP.first_name,'') AS first_name,
                (CASE WHEN S.id IS NULL THEN 0 ELSE 1 END) AS is_paid
            FROM payments P
            INNER JOIN users U ON U.id = P.user_id
            LEFT JOIN user_profile UP ON UP.user_id = U.id
            LEFT JOIN payment_settlements S ON S.payment_id = P.id
            $wsql
            ORDER BY P.created_at DESC, P.id DESC
            LIMIT 500
        ";
        $st = $this->conn->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'rows' => $rows];
    }

    /* ========= GET ========= */
    public function get(int $payment_id): array
    {
        $sql = "
            SELECT 
                P.*,
                U.user_name AS dni,
                U.email,
                COALESCE(UP.first_name,'') AS first_name,
                COALESCE(UP.contact_phone,'') AS phone,
                S.paid_at, S.txn_number, S.receipt_pdf_path
            FROM payments P
            INNER JOIN users U ON U.id = P.user_id
            LEFT JOIN user_profile UP ON UP.user_id = U.id
            LEFT JOIN payment_settlements S ON S.payment_id = P.id
            WHERE P.id = :id
            LIMIT 1
        ";
        $st = $this->conn->prepare($sql);
        $st->execute([':id' => $payment_id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception('Orden de pago no encontrada', 404);
        return ['success' => true, 'row' => $row];
    }

    /* ========= UPDATE ========= */
    public function update(int $payment_id, array $d, array $files): void
    {
        // tomar valores actuales
        $curr = $this->get($payment_id)['row'];

        // recalcular total si cambian componentes
        $amount = isset($d['contract_amount']) ? $this->asFloat($d['contract_amount']) : (float)$curr['contract_amount'];
        $stamp  = isset($d['stamp_amount'])    ? $this->asFloat($d['stamp_amount'])    : (float)$curr['stamp_amount'];
        $taxr   = isset($d['debit_credit_tax_rate']) ? (float)$d['debit_credit_tax_rate'] : (float)$curr['debit_credit_tax_rate'];
        $retr   = isset($d['retention_rate'])  ? (float)$d['retention_rate']  : (float)$curr['retention_rate'];

        $taxa = round($amount * ($taxr / 100), 2);
        $reta = round($amount * ($retr / 100), 2);
        $total = round($amount - $stamp - $taxa - $reta, 2);

        $sql = "UPDATE payments
                SET event=:event,
                    contract_amount=:amount,
                    stamp_amount=:stamp,
                    debit_credit_tax_rate=:taxr,
                    debit_credit_tax_amount=:taxa,
                    retention_rate=:retr,
                    retention_amount=:reta,
                    total_to_user=:total,
                    beneficiary_cuit=:bcuit,
                    beneficiary_cbu=:bcbu,
                    beneficiary_alias=:balias,
                    phone=:phone,
                    dest_entity_id=:deid,
                    dest_entity_name=:dename,
                    dest_entity_cuit=:decuit
                WHERE id=:id";
        $st = $this->conn->prepare($sql);
        $st->execute([
            ':event' => ($d['event'] ?? $curr['event']),
            ':amount' => $amount,
            ':stamp' => $stamp,
            ':taxr' => $taxr,
            ':taxa' => $taxa,
            ':retr' => $retr,
            ':reta' => $reta,
            ':total' => $total,
            ':bcuit' => ($d['beneficiary_cuit'] ?? $curr['beneficiary_cuit']),
            ':bcbu' => ($d['beneficiary_cbu'] ?? $curr['beneficiary_cbu']),
            ':balias' => ($d['beneficiary_alias'] ?? $curr['beneficiary_alias']),
            ':phone' => ($d['phone'] ?? $curr['phone']),
            ':deid' => ($d['dest_entity_id'] ?? $curr['dest_entity_id']),
            ':dename' => ($d['dest_entity_name'] ?? $curr['dest_entity_name']),
            ':decuit' => ($d['dest_entity_cuit'] ?? $curr['dest_entity_cuit']),
            ':id' => $payment_id
        ]);

        // Reemplazo de PDFs (opcional)
        $base = realpath(__DIR__ . '/..') ?: dirname(__DIR__, 1);
        $dir  = $this->ensureDir('/uploads/tax_invoices');

        foreach (['pedido_pdf_path' => 'pedido', 'factura_pdf_path' => 'factura'] as $col => $key) {
            if (empty($files[$key]) || $files[$key]['error'] === UPLOAD_ERR_NO_FILE) continue;
            $f = $files[$key];
            if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('Error subiendo PDF: ' . $key, 400);
            if ($f['size'] > 10 * 1024 * 1024) throw new Exception('PDF supera 10MB: ' . $key, 400);
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') throw new Exception('Solo PDF: ' . $key, 415);

            // borrar anterior
            $this->unlinkIfExists($curr[$col] ?? null);

            $safe = sprintf(
                '%s-%s-%s-%d.pdf',
                preg_replace('/\D+/', '', $curr['dni'] ?? 'dni'),
                date('Ymd_His'),
                $key,
                $payment_id
            );
            $dest = $dir . '/' . $safe;
            if (!move_uploaded_file($f['tmp_name'], $dest)) throw new Exception('No se pudo guardar: ' . $key, 500);

            $rel = '/uploads/tax_invoices/' . $safe;
            $this->conn->prepare("UPDATE payments SET $col = :p WHERE id=:id")->execute([':p' => $rel, ':id' => $payment_id]);
        }
    }

    /* ========= DELETE ========= */
    public function delete(int $payment_id): void
    {
        $row = $this->get($payment_id)['row'];
        $this->conn->beginTransaction();
        try {
            // borrar settlement + comprobante
            $st = $this->conn->prepare("SELECT receipt_pdf_path FROM payment_settlements WHERE payment_id = ?");
            $st->execute([$payment_id]);
            $rp = $st->fetchColumn();
            if ($rp) $this->unlinkIfExists($rp);
            $this->conn->prepare("DELETE FROM payment_settlements WHERE payment_id = ?")->execute([$payment_id]);

            // borrar PDFs de la orden
            $this->unlinkIfExists($row['pedido_pdf_path'] ?? null);
            $this->unlinkIfExists($row['factura_pdf_path'] ?? null);

            // borrar orden
            $this->conn->prepare("DELETE FROM payments WHERE id = ?")->execute([$payment_id]);

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /* ========= SETTLE (pagar) ========= */
    public function settle(int $payment_id, string $paid_at, string $txn, ?array $receipt): void
    {
        // guardar/actualizar settlement y comprobante
        $receiptRel = null;
        if (!empty($receipt) && ($receipt['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($receipt['error'] !== UPLOAD_ERR_OK) throw new Exception('Error subiendo comprobante', 400);
            if ($receipt['size'] > 10 * 1024 * 1024) throw new Exception('PDF supera 10MB (comprobante)', 400);
            $ext = strtolower(pathinfo($receipt['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') throw new Exception('Comprobante debe ser PDF', 415);

            $dir = $this->ensureDir('/uploads/tax_invoices/settlements');
            $safe = 'settle-' . date('Ymd_His') . '-' . $payment_id . '.pdf';
            if (!move_uploaded_file($receipt['tmp_name'], $dir . '/' . $safe)) throw new Exception('No se pudo guardar comprobante', 500);
            $receiptRel = '/uploads/tax_invoices/settlements/' . $safe;
        }

        // si ya existía, actualizar (y si hay nuevo pdf, sustituir el viejo)
        $st = $this->conn->prepare("SELECT id, receipt_pdf_path FROM payment_settlements WHERE payment_id = ?");
        $st->execute([$payment_id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if ($receiptRel) $this->unlinkIfExists($row['receipt_pdf_path']);
            $sql = "UPDATE payment_settlements SET paid_at=:p, txn_number=:t" . ($receiptRel ? ", receipt_pdf_path=:r" : "") . " WHERE payment_id=:id";
            $params = [':p' => $paid_at, ':t' => $txn, ':id' => $payment_id];
            if ($receiptRel) $params[':r'] = $receiptRel;
            $this->conn->prepare($sql)->execute($params);
        } else {
            $sql = "INSERT INTO payment_settlements (payment_id, paid_at, txn_number, receipt_pdf_path, created_at)
                    VALUES (:id, :p, :t, :r, NOW())";
            $this->conn->prepare($sql)->execute([
                ':id' => $payment_id,
                ':p' => $paid_at,
                ':t' => $txn,
                ':r' => $receiptRel
            ]);
        }
    }

    /* ========= Imagen JPG (GD) ========= */
    public function createJpegForPayment(int $payment_id)
    {
        $data = $this->get($payment_id)['row'];
        // Canvas
        $w = 1200;
        $h = 900;
        $im = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 34, 34, 34);
        $gray  = imagecolorallocate($im, 120, 120, 120);
        $violet = imagecolorallocate($im, 91, 33, 182);
        imagefilledrectangle($im, 0, 0, $w, $h, $white);

        // Títulos
        imagestring($im, 5, 40, 30, 'AMPD - Orden de pago #' . $data['id'], $violet);
        imagestring($im, 3, 40, 60, 'Fecha de carga: ' . $data['created_at'], $gray);

        $y = 110;
        $lh = 24;
        $lines = [
            'Socio: ' . ($data['first_name'] ?? ''),
            'DNI: ' . ($data['dni'] ?? ''),
            'Evento: ' . ($data['event'] ?? ''),
            'Monto real del contrato: $' . number_format((float)$data['contract_amount'], 2, ',', '.'),
            'Sellado: $' . number_format((float)$data['stamp_amount'], 2, ',', '.'),
            'Imp. d/c (' . number_format((float)$data['debit_credit_tax_rate'], 2, ',', '.') . '%): $' . number_format((float)$data['debit_credit_tax_amount'], 2, ',', '.'),
            'Retencion (' . number_format((float)$data['retention_rate'], 2, ',', '.') . '%): $' . number_format((float)$data['retention_amount'], 2, ',', '.'),
            'Total a pagar al socio: $' . number_format((float)$data['total_to_user'], 2, ',', '.'),
            'Destinatario: ' . ($data['dest_entity_name'] ?? '') . ' - CUIT: ' . ($data['dest_entity_cuit'] ?? ''),
        ];
        foreach ($lines as $line) {
            imagestring($im, 4, 40, $y, $line, $black);
            $y += $lh + 6;
        }

        // Estado
        $paid = !empty($data['paid_at']);
        $badge = $paid ? 'Factura pagada' : 'Factura sin abonar';
        $badgeColor = $paid ? imagecolorallocate($im, 46, 204, 64) : imagecolorallocate($im, 255, 165, 0);
        imagefilledrectangle($im, 40, $y + 10, 260, $y + 40, $badgeColor);
        imagestring($im, 4, 50, $y + 18, $badge, $white);

        // Salida
        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="orden_' . $data['id'] . '.jpg"');
        imagejpeg($im, null, 90);
        imagedestroy($im);
        exit;
    }
}
