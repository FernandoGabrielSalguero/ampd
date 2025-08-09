<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
        // Tomo el rol principal como el de menor role_id asignado
        $sql = "
            SELECT 
                u.id,
                u.user_name,
                u.pass,
                u.email,
                r.name AS role
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON r.id = ur.role_id
            WHERE u.user_name = :usuario
            ORDER BY ur.role_id ASC
            LIMIT 1
        ";

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
}
