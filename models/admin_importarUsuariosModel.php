<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class AdminImportarUsuariosModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function importarFila($row)
    {
        $cbu_a = preg_replace('/[^0-9]/', '', (string) ($row['cbu_a'] ?? ''));

        // Verificar si el usuario ya existe por DNI
        $stmt = $this->db->prepare("SELECT id_ FROM usuarios WHERE dni = ?");
        $stmt->execute([$row['dni']]);
        $usuarioId = $stmt->fetchColumn();

        if ($usuarioId) {
            // ✅ ACTUALIZAR REGISTROS EXISTENTES

            $stmt = $this->db->prepare("UPDATE usuarios SET 
                usuario = :usuario,
                nombre = :nombre,
                correo = :correo,
                telefono = :telefono,
                n_socio = :n_socio
                WHERE id_ = :id");
            $stmt->execute([
                ':usuario' => $row['usuario'] ?? null,
                ':nombre' => $row['nombre'] ?? null,
                ':correo' => $row['correo'] ?? null,
                ':telefono' => $row['telefono'] ?? null,
                ':n_socio' => isset($row['n_socio']) ? trim($row['n_socio']) : null,
                ':id' => $usuarioId
            ]);

            $stmt = $this->db->prepare("SELECT 1 FROM user_info WHERE usuario_id = ?");
            $stmt->execute([$usuarioId]);
            if ($stmt->fetchColumn()) {
                $stmt = $this->db->prepare("UPDATE user_info SET user_localidad = :localidad WHERE usuario_id = :uid");
            } else {
                $stmt = $this->db->prepare("INSERT INTO user_info (user_localidad, usuario_id) VALUES (:localidad, :uid)");
            }
            $stmt->execute([
                ':localidad' => $row['user_localidad'] ?? '',
                ':uid' => $usuarioId
            ]);

            $stmt = $this->db->prepare("SELECT 1 FROM user_bancarios WHERE usuario_id = ?");
            $stmt->execute([$usuarioId]);
            if ($stmt->fetchColumn()) {
                $stmt = $this->db->prepare("UPDATE user_bancarios SET 
                    cbu_a = :cbu, alias_a = :alias, titular_a = :titular, banco_a = :banco, cuit_a = :cuit 
                    WHERE usuario_id = :uid");
            } else {
                $stmt = $this->db->prepare("INSERT INTO user_bancarios (cbu_a, alias_a, titular_a, banco_a, cuit_a, usuario_id)
                    VALUES (:cbu, :alias, :titular, :banco, :cuit, :uid)");
            }
            $stmt->execute([
                ':cbu' => $cbu_a,
                ':alias' => $row['alias_a'] ?? '',
                ':titular' => $row['titular_a'] ?? '',
                ':banco' => $row['banco_a'] ?? '',
                ':cuit' => $row['cuit_a'] ?? '',
                ':uid' => $usuarioId
            ]);

            $stmt = $this->db->prepare("SELECT 1 FROM user_disciplina WHERE usuario_id = ?");
            $stmt->execute([$usuarioId]);
            if ($stmt->fetchColumn()) {
                $stmt = $this->db->prepare("UPDATE user_disciplina SET disciplina = :disciplina WHERE usuario_id = :uid");
            } else {
                $stmt = $this->db->prepare("INSERT INTO user_disciplina (disciplina, usuario_id) VALUES (:disciplina, :uid)");
            }
            $stmt->execute([
                ':disciplina' => $row['user_disciplina'] ?? '',
                ':uid' => $usuarioId
            ]);

            return 'actualizado';
        } else {
            // ✅ INSERTAR NUEVO REGISTRO

            $contrasenaHash = password_hash($row['dni'], PASSWORD_BCRYPT);
            $n_socio = isset($row['n_socio']) ? trim($row['n_socio']) : null;

            $stmt = $this->db->prepare("INSERT INTO usuarios (usuario, contrasena, nombre, correo, telefono, dni, n_socio)
                VALUES (:usuario, :contrasena, :nombre, :correo, :telefono, :dni, :n_socio)");
            $stmt->execute([
                ':usuario' => $row['usuario'],
                ':contrasena' => $contrasenaHash,
                ':nombre' => $row['nombre'],
                ':correo' => $row['correo'] ?? null,
                ':telefono' => $row['telefono'],
                ':dni' => $row['dni'],
                ':n_socio' => $n_socio
            ]);

            $usuarioId = $this->db->lastInsertId();

            $stmt = $this->db->prepare("INSERT INTO user_info (user_localidad, usuario_id) VALUES (:localidad, :uid)");
            $stmt->execute([
                ':localidad' => $row['user_localidad'] ?? '',
                ':uid' => $usuarioId
            ]);

            $stmt = $this->db->prepare("INSERT INTO user_bancarios (cbu_a, alias_a, titular_a, banco_a, cuit_a, usuario_id)
                VALUES (:cbu, :alias, :titular, :banco, :cuit, :uid)");
            $stmt->execute([
                ':cbu' => $cbu_a,
                ':alias' => $row['alias_a'] ?? '',
                ':titular' => $row['titular_a'] ?? '',
                ':banco' => $row['banco_a'] ?? '',
                ':cuit' => $row['cuit_a'] ?? '',
                ':uid' => $usuarioId
            ]);

            $stmt = $this->db->prepare("INSERT INTO user_disciplina (disciplina, usuario_id) VALUES (:disciplina, :uid)");
            $stmt->execute([
                ':disciplina' => $row['user_disciplina'] ?? '',
                ':uid' => $usuarioId
            ]);

            return 'insertado';
        }
    }
}
