<?php
declare(strict_types=1);

class AdminVariablesModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        // Aseguramos modo seguro
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo = $pdo;
    }

    /* =============================
    Impuesto débito/crédito
    ==============================*/
    public function listDebitCreditTax(): array
    {
        $stmt = $this->pdo->query("SELECT id, value, created_at, updated_at FROM env_debit_credit_tax ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function createDebitCreditTax(float $value): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO env_debit_credit_tax (value) VALUES (:value)");
        $stmt->execute([':value' => $value]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateDebitCreditTax(int $id, float $value): bool
    {
        $stmt = $this->pdo->prepare("UPDATE env_debit_credit_tax SET value = :value WHERE id = :id");
        return $stmt->execute([':value' => $value, ':id' => $id]);
    }

    public function deleteDebitCreditTax(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM env_debit_credit_tax WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /* =============================
    Retención
    ==============================*/
    public function listRetention(): array
    {
        $stmt = $this->pdo->query("SELECT id, value, created_at, updated_at FROM env_retention ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function createRetention(float $value): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO env_retention (value) VALUES (:value)");
        $stmt->execute([':value' => $value]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateRetention(int $id, float $value): bool
    {
        $stmt = $this->pdo->prepare("UPDATE env_retention SET value = :value WHERE id = :id");
        return $stmt->execute([':value' => $value, ':id' => $id]);
    }

    public function deleteRetention(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM env_retention WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /* =============================
    Entidades de facturación
    ==============================*/
    public function listBillingEntities(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, cuit, created_at, updated_at FROM env_billing_entities ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function createBillingEntity(string $name, string $cuit): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO env_billing_entities (name, cuit) VALUES (:name, :cuit)");
        $stmt->execute([':name' => trim($name), ':cuit' => trim($cuit)]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateBillingEntity(int $id, string $name, string $cuit): bool
    {
        $stmt = $this->pdo->prepare("UPDATE env_billing_entities SET name = :name, cuit = :cuit WHERE id = :id");
        return $stmt->execute([':name' => trim($name), ':cuit' => trim($cuit), ':id' => $id]);
    }

    public function deleteBillingEntity(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM env_billing_entities WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
