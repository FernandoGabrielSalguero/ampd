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
                u.id AS id_real,
                u.usuario,
                u.contrasena,
                u.rol,
                u.estado,
                u.fecha_creacion,
                
                ui.dni,
                ui.correo,
                ui.tel AS telefono,
                ui.fecha_nacimiento,
                ui.direccion,
                ui.id AS user_info_id,
                u.nombre AS nombre

            FROM usuarios u
            JOIN user_info ui ON u.id = ui.usuario_id
            WHERE u.usuario = :usuario
            LIMIT 1";

    $stmt = $this->db->prepare($sql);
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['estado'] === 'activo' && password_verify($contrasenaIngresada, $user['contrasena'])) {
        return $user;
    }

    return false;
}

}
