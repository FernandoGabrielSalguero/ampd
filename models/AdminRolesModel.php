<?php

class AdminRolesModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    // Obtener todos los usuarios con su rol
    public function getAllUsersWithRoles()
    {
        $sql = "
            SELECT u.id, u.user_name, u.email, r.id as role_id, r.name as role_name
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            ORDER BY u.user_name
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener todos los roles disponibles
    public function getAllRoles()
    {
        $stmt = $this->db->query("SELECT id, name FROM roles ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cambiar el rol de un usuario
    public function updateUserRole($userId, $roleId)
    {
        // Verificar si ya tiene un rol asignado
        $check = $this->db->prepare("SELECT * FROM user_roles WHERE user_id = :user_id");
        $check->execute(['user_id' => $userId]);

        if ($check->fetch()) {
            $stmt = $this->db->prepare("UPDATE user_roles SET role_id = :role_id WHERE user_id = :user_id");
        } else {
            $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
        }

        return $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId
        ]);
    }

    // Obtener todos los permisos
    public function getAllPermissions()
    {
        $stmt = $this->db->query("SELECT id, name FROM permissions ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener permisos asignados por rol (y opcionalmente directos por usuario)
    public function getUserPermissions($userId)
    {
        $sql = "
            SELECT DISTINCT p.id, p.name
            FROM permissions p
            INNER JOIN role_permissions rp ON rp.permission_id = p.id
            INNER JOIN user_roles ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = :user_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener permisos directos asignados a un usuario (por fuera del rol)
    public function getDirectPermissionsByUser($userId)
    {
        $sql = "
            SELECT p.id, p.name
            FROM user_permissions up
            INNER JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = :user_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizar permisos directos del usuario (reemplaza todos)
    public function updateUserPermissions($userId, $permissionIds)
    {
        // 1. Eliminar los existentes
        $delete = $this->db->prepare("DELETE FROM user_permissions WHERE user_id = :user_id");
        $delete->execute(['user_id' => $userId]);

        // 2. Insertar nuevos
        $insert = $this->db->prepare("INSERT INTO user_permissions (user_id, permission_id) VALUES (:user_id, :permission_id)");

        foreach ($permissionIds as $pid) {
            $insert->execute([
                'user_id' => $userId,
                'permission_id' => $pid
            ]);
        }

        return true;
    }
}
