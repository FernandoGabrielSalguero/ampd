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
                    u.id_ AS id_real,
                    u.usuario,
                    u.contrasena,
                    u.rol,
                    u.estado,
                    u.fecha_creacion,
                    u.nombre,
                    u.correo,
                    u.telefono,
                    u.dni,
                    u.n_socio,

                    ui.user_direccion,
                    ui.user_localidad,
                    ui.user_fecha_nacimiento,
                    ui.id_ AS user_info_id

                FROM usuarios u
                LEFT JOIN user_info ui ON u.id_ = ui.usuario_id
                WHERE u.usuario = :usuario
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['estado'] !== 'activo') {
            return false;
        }

        // Permitir acceso si no tiene contraseña y es asociado
        if (empty($user['contrasena']) && $user['rol'] === 'asociado') {
            return $user;
        }

        $hash = $user['contrasena'];
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
