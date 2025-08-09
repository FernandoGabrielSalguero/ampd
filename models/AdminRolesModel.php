<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class AdminRolesModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    // Usuarios con su rol principal (menor role_id)
    public function getAllUsersWithRoles()
    {
        $sql = "
            SELECT u.id, u.user_name, u.email, r.id AS role_id, r.name AS role_name
            FROM users u
            LEFT JOIN (
                SELECT ur1.user_id, ur1.role_id
                FROM user_roles ur1
                WHERE NOT EXISTS (
                    SELECT 1 FROM user_roles ur2
                    WHERE ur2.user_id = ur1.user_id AND ur2.role_id < ur1.role_id
                )
            ) pur ON pur.user_id = u.id
            LEFT JOIN roles r ON r.id = pur.role_id
            ORDER BY u.user_name
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRoles()
    {
        $stmt = $this->db->query("SELECT id, name FROM roles ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Reemplaza el rol del usuario (único rol efectivo)
    public function updateUserRole($userId, $roleId)
    {
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
            $del->execute(['user_id' => $userId]);

            $ins = $this->db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
            $ins->execute(['user_id' => $userId, 'role_id' => $roleId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
