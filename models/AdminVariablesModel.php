<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

class VariablesModel
{
    private $conn;

    public function __construct()
    {
        global $pdo;
        $this->conn = $pdo;
    }

    /* ======== LECTURA (último vigente) ======== */
    public function getDebitCreditTax()
    {
        $sql = "SELECT id, CAST(value AS CHAR) AS value, is_favorite, created_at
                FROM env_debit_credit_tax
                ORDER BY id DESC
                LIMIT 1";
        return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getRetention()
    {
        $sql = "SELECT id, CAST(value AS CHAR) AS value, is_favorite, created_at
                FROM env_retention
                ORDER BY id DESC
                LIMIT 1";
        return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getBillingEntity()
    {
        $sql = "SELECT id, name, cuit, is_favorite, created_at
                FROM env_billing_entities
                ORDER BY id DESC
                LIMIT 1";
        return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ======== HISTÓRICO ======== */
    public function listDebitCreditHistory(int $limit = 50)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, CAST(value AS CHAR) AS value, is_favorite, created_at
             FROM env_debit_credit_tax
             ORDER BY id DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listRetentionHistory(int $limit = 50)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, CAST(value AS CHAR) AS value, is_favorite, created_at
             FROM env_retention
             ORDER BY id DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listBillingEntities(int $limit = 50)
    {
        $stmt = $this->conn->prepare(
            "SELECT id, name, cuit, is_favorite, created_at
             FROM env_billing_entities
             ORDER BY id DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ======== ESCRITURA (INSERT-only) ======== */
    public function saveDebitCreditTax(float $value)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO env_debit_credit_tax (value) VALUES (:v)"
        );
        $stmt->execute([':v' => $value]);
        return ['id' => (int)$this->conn->lastInsertId(), 'value' => $value];
    }

    public function saveRetention(float $value)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO env_retention (value) VALUES (:v)"
        );
        $stmt->execute([':v' => $value]);
        return ['id' => (int)$this->conn->lastInsertId(), 'value' => $value];
    }

    public function saveBillingEntity(string $name, string $cuit)
    {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO env_billing_entities (name, cuit) VALUES (:n, :c)"
            );
            $stmt->execute([':n' => $name, ':c' => $cuit]);
            return ['id' => (int)$this->conn->lastInsertId(), 'name' => $name, 'cuit' => $cuit];
        } catch (Throwable $e) {
            if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
                throw new RuntimeException('El CUIT ingresado ya existe (restricción UNIQUE).');
            }
            throw $e;
        }
    }

    /* ======== FAVORITOS (único por tipo) ======== */
    public function setFavoriteDebitCredit(int $id)
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->exec("UPDATE env_debit_credit_tax SET is_favorite = 0");
            $stmt = $this->conn->prepare("UPDATE env_debit_credit_tax SET is_favorite = 1 WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function setFavoriteRetention(int $id)
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->exec("UPDATE env_retention SET is_favorite = 0");
            $stmt = $this->conn->prepare("UPDATE env_retention SET is_favorite = 1 WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function setFavoriteBillingEntity(int $id)
    {
        $this->conn->beginTransaction();
        try {
            $this->conn->exec("UPDATE env_billing_entities SET is_favorite = 0");
            $stmt = $this->conn->prepare("UPDATE env_billing_entities SET is_favorite = 1 WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /* ======== ELIMINAR ======== */
    public function deleteDebitCredit(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM env_debit_credit_tax WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteRetention(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM env_retention WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteBillingEntity(int $id)
    {
        $stmt = $this->conn->prepare("DELETE FROM env_billing_entities WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
