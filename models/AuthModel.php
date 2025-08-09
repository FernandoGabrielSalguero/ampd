<?php

require_once __DIR__ . '/../config.php';

class AuthModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

public function login($usuario, $contrasenaIngresada)
{
    $sql = "SELECT 
        u.id,
        u.user_name,
        u.pass,
        u.email,
        r.name AS role
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        WHERE u.user_name = :usuario
        LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) return false;

    $hash = $user['pass'] ?? '';
    $isHashed = preg_match('/^\$2y\$/', $hash);

    if (
        (!$isHashed && $hash === $contrasenaIngresada) ||
        ($isHashed && password_verify($contrasenaIngresada, $hash))
    ) {
        return $user;
    }

    return false;
}


    public function getPermissionsByUserId(int $userId): array
    {
        $sql = "
            SELECT DISTINCT p.name
            FROM user_roles ur
            JOIN role_permissions rp ON ur.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ur.user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
