<?php
class AdminImportarUsuariosModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function importarFila($row)
    {
        // Verificar duplicado por DNI
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE dni = ?");
        $stmt->execute([$row['dni']]);
        if ($stmt->fetchColumn() > 0) {
            return; // Ya existe, salteamos
        }

        // Insertar en usuarios
        $stmt = $this->db->query("SELECT MAX(n_socio) FROM usuarios");
        $maxSocio = $stmt->fetchColumn();
        $n_socio = $maxSocio ? $maxSocio + 1 : 1;

        $contrasenaHash = password_hash($row['dni'], PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("INSERT INTO usuarios (usuario, contrasena, nombre, correo, telefono, dni, n_socio)
                                    VALUES (:usuario, :contrasena, :nombre, :correo, :telefono, :dni, :n_socio)");
        $stmt->execute([
            ':usuario' => $row['usuario'],
            ':contrasena' => $contrasenaHash,
            ':nombre' => $row['nombre'],
            ':correo' => $row['correo'],
            ':telefono' => $row['telefono'],
            ':dni' => $row['dni'],
            ':n_socio' => $n_socio
        ]);
        $usuarioId = $this->db->lastInsertId();

        // Insertar en user_info
        $stmt = $this->db->prepare("INSERT INTO user_info (user_localidad, usuario_id) VALUES (:localidad, :uid)");
        $stmt->execute([
            ':localidad' => $row['user_localidad'] ?? '',
            ':uid' => $usuarioId
        ]);

        // Insertar en user_bancarios
        $stmt = $this->db->prepare("INSERT INTO user_bancarios (cbu_a, alias_a, titular_a, banco_a, cuit_a, usuario_id)
                                    VALUES (:cbu, :alias, :titular, :banco, :cuit, :uid)");
        $stmt->execute([
            ':cbu' => $row['cbu_a'] ?? '',
            ':alias' => $row['alias_a'] ?? '',
            ':titular' => $row['titular_a'] ?? '',
            ':banco' => $row['banco_a'] ?? '',
            ':cuit' => $row['cuit_a'] ?? '',
            ':uid' => $usuarioId
        ]);

        // Insertar disciplina libre
        $stmt = $this->db->prepare("INSERT INTO user_disciplina (disciplina, usuario_id) VALUES (:disciplina, :uid)");
        $stmt->execute([
            ':disciplina' => $row['user_disciplina'] ?? '',
            ':uid' => $usuarioId
        ]);
    }
}
