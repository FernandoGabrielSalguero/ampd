<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

class AdminUsuarioMasivoModel
{
    private $db;

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /* ===== Roles / Usuarios ===== */

    /** Devuelve el id del rol 'socio' (minúsculas). Lo crea si no existe. */
    public function getOrCreateSocioRoleId(): int
    {
        $stmt = $this->db->prepare("SELECT id FROM roles WHERE name = 'socio' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return (int)$row['id'];

        $this->db->prepare("INSERT INTO roles (name) VALUES ('socio')")->execute();
        return (int)$this->db->lastInsertId();
    }

    /** Busca usuario por DNI (asumimos users.user_name = DNI). */
    public function findUserIdByDni(string $dni): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE user_name = :dni LIMIT 1");
        $stmt->execute([':dni' => $dni]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['id'] : null;
    }

    /**
     * Crea usuario si no existe (user_name = DNI). Retorna user_id.
     * - email: usa el del CSV; si está tomado o es no válido, usa DNI@sin-correo.local
     * - pass: se guarda hasheado.
     */
    public function createUserIfMissing(string $dni, ?string $emailCsv, string $plainPass): int
    {
        $existing = $this->findUserIdByDni($dni);
        if ($existing !== null) return $existing;

        $email = $this->buildUniqueEmail($dni, $emailCsv);

        $stmt = $this->db->prepare("
            INSERT INTO users (registration_date, user_name, pass, email, created_at, updated_at)
            VALUES (NOW(), :user_name, :pass, :email, NOW(), NOW())
        ");
        $stmt->execute([
            ':user_name' => $dni,
            ':pass'      => password_hash($plainPass, PASSWORD_BCRYPT),
            ':email'     => $email,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Garantiza que el user_id tenga el rol socio. */
    public function ensureUserHasSocioRole(int $userId): void
    {
        $roleId = $this->getOrCreateSocioRoleId();
        $stmt = $this->db->prepare("SELECT 1 FROM user_roles WHERE user_id = :uid AND role_id = :rid LIMIT 1");
        $stmt->execute([':uid'=>$userId, ':rid'=>$roleId]);
        if ($stmt->fetch()) return;

        $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)");
        $stmt->execute([':uid'=>$userId, ':rid'=>$roleId]);
    }

    /** Genera un email válido y único basado en el CSV o en el DNI. */
    private function buildUniqueEmail(string $dni, ?string $emailCsv): string
    {
        $email = trim((string)$emailCsv);
        $isPlaceholder = false;

        // Si viene vacío o "sin@correo.com" => usar placeholder
        if ($email === '' || stripos($email, 'sin@correo.com') === 0) {
            $email = $dni . '@sin-correo.local';
            $isPlaceholder = true;
        }

        // Verificar unicidad; si choca y no es placeholder, fallback a dni@
        if (!$this->isEmailAvailable($email)) {
            $email = $dni . '@sin-correo.local';
            $isPlaceholder = true;
        }
        // Si aún así chocara (altamente improbable), agregar sufijo
        $i = 1;
        $base = $email;
        while (!$this->isEmailAvailable($email)) {
            $email = preg_replace('/(@.*)$/', "+$i$1", $base);
            $i++;
        }
        return $email;
    }

    private function isEmailAvailable(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM users WHERE email = :e LIMIT 1");
        $stmt->execute([':e'=>$email]);
        return $stmt->fetch() ? false : true;
    }

    /* ===== Batch cuentas bancarias ===== */

    /**
     * Inserta/actualiza un lote de cuentas bancarias por (dni, slot 1..3).
     * $rows: [dni, n_socio, slot, cbu, alias, titular, banco, cuit]
     */
    public function upsertBatch(array $rows, bool $replace = true): array
    {
        if (empty($rows)) return ['inserted'=>0,'updated'=>0];

        $sql = $replace
            ? "INSERT INTO user_bank_accounts (dni, n_socio, slot, cbu, alias, titular, banco, cuit)
               VALUES (:dni,:n_socio,:slot,:cbu,:alias,:titular,:banco,:cuit)
               ON DUPLICATE KEY UPDATE
                   n_socio = VALUES(n_socio),
                   cbu     = VALUES(cbu),
                   alias   = VALUES(alias),
                   titular = VALUES(titular),
                   banco   = VALUES(banco),
                   cuit    = VALUES(cuit)"
            : "INSERT IGNORE INTO user_bank_accounts (dni, n_socio, slot, cbu, alias, titular, banco, cuit)
               VALUES (:dni,:n_socio,:slot,:cbu,:alias,:titular,:banco,:cuit)";

        $stmt = $this->db->prepare($sql);

        $ins = 0; $upd = 0;
        $this->db->beginTransaction();
        try {
            foreach ($rows as $r) {
                $stmt->execute([
                    ':dni'     => $r['dni'],
                    ':n_socio' => $r['n_socio'],
                    ':slot'    => $r['slot'],
                    ':cbu'     => $r['cbu'],
                    ':alias'   => $r['alias'],
                    ':titular' => $r['titular'],
                    ':banco'   => $r['banco'],
                    ':cuit'    => $r['cuit'],
                ]);
                if ($replace) {
                    $rc = $stmt->rowCount(); // 1 insert, 2 update
                    if ($rc === 1) $ins++;
                    elseif ($rc === 2) $upd++;
                } else {
                    if ($stmt->rowCount() === 1) $ins++;
                }
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return ['inserted'=>$ins, 'updated'=>$upd];
    }
}
