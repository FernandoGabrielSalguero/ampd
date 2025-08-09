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
        // En config.php ya debés tener $pdo (PDO conectado a u104036906_ampd)
        global $pdo;
        $this->conn = $pdo;
    }

    /* ======== LECTURA ======== */
    public function getDebitCreditTax()
    {
        // Tomo la última o la única
        $sql = "SELECT id, CAST(value AS CHAR) AS value FROM env_debit_credit_tax ORDER BY id DESC LIMIT 1";
        return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getRetention()
    {
        $sql = "SELECT id, CAST(value AS CHAR) AS value FROM env_retention ORDER BY id DESC LIMIT 1";
        return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getBillingEntity()
    {
        $sql = "SELECT id, name, cuit FROM env_billing_entities ORDER BY id DESC LIMIT 1";
        return $this->conn->query($sql)->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ======== ESCRITURA ======== */
    // Estrategia: mantener 1 fila. Si existe, actualizar; si no, insertar.
    public function saveDebitCreditTax(float $value)
    {
        $this->conn->beginTransaction();
        try {
            $row = $this->getDebitCreditTax();
            if ($row) {
                $stmt = $this->conn->prepare("UPDATE env_debit_credit_tax SET value = :v WHERE id = :id");
                $stmt->execute([':v' => $value, ':id' => $row['id']]);
                $id = $row['id'];
            } else {
                $stmt = $this->conn->prepare("INSERT INTO env_debit_credit_tax (value) VALUES (:v)");
                $stmt->execute([':v' => $value]);
                $id = (int)$this->conn->lastInsertId();
            }
            $this->conn->commit();
            return ['id'=>$id,'value'=>$value];
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function saveRetention(float $value)
    {
        $this->conn->beginTransaction();
        try {
            $row = $this->getRetention();
            if ($row) {
                $stmt = $this->conn->prepare("UPDATE env_retention SET value = :v WHERE id = :id");
                $stmt->execute([':v' => $value, ':id' => $row['id']]);
                $id = $row['id'];
            } else {
                $stmt = $this->conn->prepare("INSERT INTO env_retention (value) VALUES (:v)");
                $stmt->execute([':v' => $value]);
                $id = (int)$this->conn->lastInsertId();
            }
            $this->conn->commit();
            return ['id'=>$id,'value'=>$value];
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function saveBillingEntity(string $name, string $cuit)
    {
        $this->conn->beginTransaction();
        try {
            // Garantizar unicidad de CUIT (ya hay UNIQUE en la tabla)
            // Si existe fila actual, la actualizo; si no, inserto.
            $current = $this->getBillingEntity();

            if ($current) {
                // Si cambia CUIT y ya existe ese CUIT, tiro error legible
                if ($cuit !== $current['cuit']) {
                    $exists = $this->findBillingByCuit($cuit);
                    if ($exists) {
                        throw new RuntimeException('El CUIT ya existe en otra entidad.');
                    }
                }
                $stmt = $this->conn->prepare("UPDATE env_billing_entities SET name = :n, cuit = :c WHERE id = :id");
                $stmt->execute([':n'=>$name, ':c'=>$cuit, ':id'=>$current['id']]);
                $id = $current['id'];
            } else {
                $stmt = $this->conn->prepare("INSERT INTO env_billing_entities (name, cuit) VALUES (:n, :c)");
                $stmt->execute([':n'=>$name, ':c'=>$cuit]);
                $id = (int)$this->conn->lastInsertId();
            }
            $this->conn->commit();
            return ['id'=>$id,'name'=>$name,'cuit'=>$cuit];
        } catch (Throwable $e) {
            $this->conn->rollBack();

            // Capturar violación de UNIQUE (por si viene de MySQL)
            if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
                throw new RuntimeException('El CUIT ingresado ya existe.');
            }
            throw $e;
        }
    }

    private function findBillingByCuit(string $cuit)
    {
        $stmt = $this->conn->prepare("SELECT id, name, cuit FROM env_billing_entities WHERE cuit = :cuit LIMIT 1");
        $stmt->execute([':cuit'=>$cuit]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
