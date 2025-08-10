<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

class client_asociarModel
{
    private $conn;

    public function __construct()
    {
        global $pdo;
        $this->conn = $pdo;
    }

    public function crearSocio(array $data): array
    {
        $this->conn->beginTransaction();
        try {
            // Validaciones mínimas backend
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido', 422);
            }
            if (!preg_match('/^\d{6,10}$/', $data['dni'])) {
                throw new Exception('DNI inválido', 422);
            }

            // 1) USERS
            // user_name = DNI, pass = hash(DNI)
            $sql = "INSERT INTO users (registration_date, user_name, pass, email, created_at, updated_at)
                    VALUES (NOW(), :user_name, :pass, :email, NOW(), NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':user_name' => $data['dni'],
                ':pass'      => password_hash($data['dni'], PASSWORD_DEFAULT),
                ':email'     => $data['email']
            ]);
            $user_id = (int)$this->conn->lastInsertId();

            // 2) USER_PROFILE
            $sql = "INSERT INTO user_profile (user_id, first_name, last_name, birth_date, contact_phone)
                    VALUES (:user_id, :first_name, '', CURDATE(), :phone)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':user_id'    => $user_id,
                ':first_name' => $data['first_name'],
                ':phone'      => $data['phone'] ?? null
            ]);

            // 3) ROLE = Socio
            $role_id = $this->ensureRole('Socio');
            $sql = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':user_id' => $user_id, ':role_id' => $role_id]);

            // 4) USER_BANK_ACCOUNTS (opcional)
            $hasBank = false;
            if (!empty($data['cuit']) || !empty($data['cbu']) || !empty($data['alias']) || !empty($data['titular'])) {
                $sql = "INSERT INTO user_bank_accounts (dni, n_socio, slot, cbu, alias, titular, banco, cuit, created_at)
                        VALUES (:dni, :n_socio, :slot, :cbu, :alias, :titular, :banco, :cuit, NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':dni'     => $data['dni'],
                    ':n_socio' => $user_id, // Asumimos número de socio = user_id
                    ':slot'    => 1,
                    ':cbu'     => $data['cbu'] ?: null,
                    ':alias'   => $data['alias'] ?: null,
                    ':titular' => $data['titular'] ?: null,
                    ':banco'   => null,
                    ':cuit'    => $data['cuit'] ?: null
                ]);
                $hasBank = true;
            }

            $this->conn->commit();
            return ['user_id' => $user_id, 'has_bank' => $hasBank];
        } catch (Throwable $e) {
            $this->conn->rollBack();
            // Keys únicas: users.user_name, users.email
            if ($e->getCode() == 23000) {
                throw new Exception('El DNI o Email ya está registrado', 409);
            }
            throw $e;
        }
    }

    public function listarSocios(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['search_nombre'])) {
            $where[] = "UP.first_name LIKE :fname";
            $params[':fname'] = '%' . $filters['search_nombre'] . '%';
        }
        if (!empty($filters['search_dni'])) {
            $where[] = "U.user_name LIKE :dni";
            $params[':dni'] = $filters['search_dni'] . '%';
        }
        $whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Trae datos base
        $sql = "
            SELECT U.id AS user_id,
                   U.user_name AS dni,
                   UP.first_name
            FROM users U
            LEFT JOIN user_profile UP ON UP.user_id = U.id
            $whereSQL
            ORDER BY U.id DESC
            LIMIT 200
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) return [];

        // Mejora: traer flags en batch

        // 1) Bancos por user_id
        $ids = array_column($rows, 'user_id');
        $in = implode(',', array_fill(0, count($ids), '?'));

        $hasBankByUser = [];
        $sqlB = "SELECT n_socio AS user_id, COUNT(*) c FROM user_bank_accounts WHERE n_socio IN ($in) GROUP BY n_socio";
        $stmtB = $this->conn->prepare($sqlB);
        $stmtB->execute($ids);
        foreach ($stmtB as $r) $hasBankByUser[(int)$r['user_id']] = (int)$r['c'] > 0;

        // 2) Cuota año actual
        $year = (int)date('Y');
        $feePaidByUser = [];
        $sqlF = "SELECT user_id FROM membership_fees WHERE year = ? AND paid_at IS NOT NULL AND user_id IN ($in)";
        $stmtF = $this->conn->prepare($sqlF);
        $stmtF->execute(array_merge([$year], $ids));
        foreach ($stmtF as $r) $feePaidByUser[(int)$r['user_id']] = true;

        // Armo salida
        $out = [];
        foreach ($rows as $r) {
            $uid = (int)$r['user_id'];
            $out[] = [
                'user_id'    => $uid,
                'dni'        => $r['dni'],
                'first_name' => $r['first_name'],
                'has_bank'   => $hasBankByUser[$uid] ?? false,
                'fee_paid'   => $feePaidByUser[$uid] ?? false,
                'fee_year'   => $year
            ];
        }
        return $out;
    }

    public function eliminarSocioTotal(int $user_id): void
    {
        $this->conn->beginTransaction();
        try {
            // delete en orden simple
            $stmt = $this->conn->prepare("DELETE FROM user_bank_accounts WHERE n_socio = ?");
            $stmt->execute([$user_id]);

            $stmt = $this->conn->prepare("DELETE FROM membership_fees WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $stmt = $this->conn->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $stmt = $this->conn->prepare("DELETE FROM user_profile WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    private function ensureRole(string $roleName): int
    {
        // Busca rol, si no existe lo crea
        $stmt = $this->conn->prepare("SELECT id FROM roles WHERE name = ?");
        $stmt->execute([$roleName]);
        $id = $stmt->fetchColumn();
        if ($id) return (int)$id;

        $stmt = $this->conn->prepare("INSERT INTO roles (name) VALUES (?)");
        $stmt->execute([$roleName]);
        return (int)$this->conn->lastInsertId();
    }

    public function obtenerSocio(int $user_id): array
    {
        $sql = "
      SELECT 
        U.id AS user_id, U.user_name AS dni, U.email,
        COALESCE(R.name,'') AS role_name,
        UP.first_name, UP.contact_phone AS phone,
        BA.cuit, BA.cbu, BA.alias, BA.titular
      FROM users U
      LEFT JOIN user_profile UP ON UP.user_id = U.id
      LEFT JOIN user_roles UR ON UR.user_id = U.id
      LEFT JOIN roles R ON R.id = UR.role_id
      LEFT JOIN user_bank_accounts BA ON BA.n_socio = U.id AND BA.slot = 1
      WHERE U.id = ?
      LIMIT 1
    ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception('Usuario no encontrado', 404);
        return $row;
    }

    public function actualizarSocio(array $d): void
    {
        $this->conn->beginTransaction();
        try {
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) throw new Exception('Email inválido', 422);
            if (!preg_match('/^\d{6,10}$/', $d['dni'])) throw new Exception('DNI inválido', 422);

            // users
            $stmt = $this->conn->prepare("UPDATE users SET user_name = :dni, email = :email, updated_at = NOW() WHERE id = :id");
            $stmt->execute([':dni' => $d['dni'], ':email' => $d['email'], ':id' => $d['user_id']]);

            // profile
            $existsP = $this->conn->prepare("SELECT id FROM user_profile WHERE user_id = ?");
            $existsP->execute([$d['user_id']]);
            if ($existsP->fetchColumn()) {
                $stmt = $this->conn->prepare("UPDATE user_profile SET first_name = :fn, contact_phone=:ph WHERE user_id=:uid");
                $stmt->execute([':fn' => $d['first_name'], ':ph' => $d['phone'] ?: null, ':uid' => $d['user_id']]);
            } else {
                $stmt = $this->conn->prepare("INSERT INTO user_profile (user_id, first_name, last_name, birth_date, contact_phone) VALUES (:uid,:fn,'',CURDATE(),:ph)");
                $stmt->execute([':uid' => $d['user_id'], ':fn' => $d['first_name'], ':ph' => $d['phone'] ?: null]);
            }

            // role (set único)
            $role_id = $this->ensureRole($d['role']);
            $this->conn->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$d['user_id']]);
            $this->conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?,?)")->execute([$d['user_id'], $role_id]);

            // bank upsert (slot 1)
            $existsB = $this->conn->prepare("SELECT id FROM user_bank_accounts WHERE n_socio = ? AND slot = 1");
            $existsB->execute([$d['user_id']]);
            $paramsB = [
                ':dni' => $d['dni'],
                ':n_socio' => $d['user_id'],
                ':slot' => 1,
                ':cbu' => $d['cbu'] ?: null,
                ':alias' => $d['alias'] ?: null,
                ':titular' => $d['titular'] ?: null,
                ':banco' => null,
                ':cuit' => $d['cuit'] ?: null
            ];
            if ($existsB->fetchColumn()) {
                $sql = "UPDATE user_bank_accounts 
                    SET dni=:dni, cbu=:cbu, alias=:alias, titular=:titular, banco=:banco, cuit=:cuit 
                    WHERE n_socio=:n_socio AND slot=:slot";
                $this->conn->prepare($sql)->execute($paramsB);
            } else {
                $sql = "INSERT INTO user_bank_accounts (dni, n_socio, slot, cbu, alias, titular, banco, cuit, created_at)
                    VALUES (:dni, :n_socio, :slot, :cbu, :alias, :titular, :banco, :cuit, NOW())";
                $this->conn->prepare($sql)->execute($paramsB);
            }

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollBack();
            if ($e->getCode() == 23000) throw new Exception('DNI o Email ya registrado', 409);
            throw $e;
        }
    }

    public function registrarPagoCuota(int $user_id, int $year, ?string $paid_at = null): void
    {
        if ($year < 2000 || $year > 2100) throw new Exception('Año inválido', 422);
        // si paid_at no viene, NOW()
        $paid = $paid_at ? date('Y-m-d H:i:s', strtotime($paid_at)) : date('Y-m-d H:i:s');
        $sql = "INSERT INTO membership_fees (user_id, year, paid_at)
            VALUES (:uid, :y, :p)
            ON DUPLICATE KEY UPDATE paid_at = VALUES(paid_at)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':uid' => $user_id, ':y' => $year, ':p' => $paid]);
    }
}
