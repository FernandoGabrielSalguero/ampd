<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config.php';

class AdminAltaUsuariosModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerUsuarios($filtroDNI = '', $filtroNombre = '', $filtroNSocio = '', $limit = 20, $offset = 0)
    {
        $sql = "SELECT id_ AS id, nombre, correo, telefono, dni, n_socio FROM usuarios WHERE 1=1";
        $params = [];

        if (!empty($filtroDNI)) {
            $sql .= " AND dni LIKE :dni";
            $params[':dni'] = '%' . $filtroDNI . '%';
        }

        if (!empty($filtroNombre)) {
            $sql .= " AND nombre LIKE :nombre";
            $params[':nombre'] = '%' . $filtroNombre . '%';
        }

        if (!empty($filtroNSocio)) {
            if (ctype_digit($filtroNSocio)) {
                $sql .= " AND n_socio = :n_socio";
                $params[':n_socio'] = (int) $filtroNSocio;
            } else {
                $sql .= " AND n_socio LIKE :n_socio";
                $params[':n_socio'] = '%' . $filtroNSocio . '%';
            }
        }

        $sql .= " ORDER BY id_ DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val, PDO::PARAM_STR);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearUsuario($nombre, $dni, $correo, $telefono)
    {
        // Verificar duplicado
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE dni = :dni");
        $stmt->execute([':dni' => $dni]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ya existe un usuario con ese DNI.");
        }

        // Obtener n_socio correlativo
        $stmt = $this->db->query("SELECT MAX(n_socio) AS max_n_socio FROM usuarios");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $n_socio = $result['max_n_socio'] ? $result['max_n_socio'] + 1 : 1;

        $usuario = $dni;
        $contrasenaHash = password_hash($dni, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare("INSERT INTO usuarios (usuario, contrasena, nombre, correo, telefono, dni, n_socio)
                                    VALUES (:usuario, :contrasena, :nombre, :correo, :telefono, :dni, :n_socio)");
        $stmt->execute([
            ':usuario' => $usuario,
            ':contrasena' => $contrasenaHash,
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':telefono' => $telefono,
            ':dni' => $dni,
            ':n_socio' => $n_socio
        ]);
    }

    public function eliminarUsuario($id)
    {
        $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id_ = :id");
        $stmt->execute([':id' => $id]);
    }

    public function actualizarUsuario($id, $data)
    {
        // 1. Actualizar tabla `usuarios`
        $stmt = $this->db->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo, telefono = :telefono, dni = :dni WHERE id_ = :id");
        $stmt->execute([
            ':nombre' => $data['nombre'] ?? '',
            ':correo' => $data['correo'] ?? '',
            ':telefono' => $data['telefono'] ?? '',
            ':dni' => $data['dni'] ?? '',
            ':id' => $id
        ]);

        // 2. Actualizar tabla `user_info` (insertar si no existe)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_info WHERE usuario_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $this->db->prepare("UPDATE user_info SET user_direccion = :direccion, user_localidad = :localidad, user_fecha_nacimiento = :fecha WHERE usuario_id = :id");
        } else {
            $stmt = $this->db->prepare("INSERT INTO user_info (user_direccion, user_localidad, user_fecha_nacimiento, usuario_id)
                                    VALUES (:direccion, :localidad, :fecha, :id)");
        }
        $stmt->execute([
            ':direccion' => $data['direccion'] ?? '',
            ':localidad' => $data['localidad'] ?? '',
            ':fecha' => $data['fecha_nacimiento'] ?? null,
            ':id' => $id
        ]);

        // 3. Actualizar `user_bancarios` (insertar si no existe)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_bancarios WHERE usuario_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $this->db->prepare("UPDATE user_bancarios SET 
    alias_a = :alias_a, cbu_a = :cbu_a, titular_a = :titular_a, cuit_a = :cuit_a, banco_a = :banco_a,
    alias_b = :alias_b, cbu_b = :cbu_b, titular_b = :titular_b, cuit_b = :cuit_b, banco_b = :banco_b,
    alias_c = :alias_c, cbu_c = :cbu_c, titular_c = :titular_c, cuit_c = :cuit_c, banco_c = :banco_c
    WHERE usuario_id = :id");
        } else {
            $stmt = $this->db->prepare("INSERT INTO user_bancarios (
    alias_a, cbu_a, titular_a, cuit_a, banco_a,
    alias_b, cbu_b, titular_b, cuit_b, banco_b,
    alias_c, cbu_c, titular_c, cuit_c, banco_c,
    usuario_id)
    VALUES (
    :alias_a, :cbu_a, :titular_a, :cuit_a, :banco_a,
    :alias_b, :cbu_b, :titular_b, :cuit_b, :banco_b,
    :alias_c, :cbu_c, :titular_c, :cuit_c, :banco_c,
    :id)");
        }
        $stmt->execute([
            ':alias_a' => $data['alias_a'] ?? '',
            ':cbu_a' => $data['cbu_a'] ?? '',
            ':titular_a' => $data['titular_a'] ?? '',
            ':cuit_a' => $data['cuit_a'] ?? '',
            ':banco_a' => $data['banco_a'] ?? '',

            ':alias_b' => $data['alias_b'] ?? '',
            ':cbu_b' => $data['cbu_b'] ?? '',
            ':titular_b' => $data['titular_b'] ?? '',
            ':cuit_b' => $data['cuit_b'] ?? '',
            ':banco_b' => $data['banco_b'] ?? '',

            ':alias_c' => $data['alias_c'] ?? '',
            ':cbu_c' => $data['cbu_c'] ?? '',
            ':titular_c' => $data['titular_c'] ?? '',
            ':cuit_c' => $data['cuit_c'] ?? '',
            ':banco_c' => $data['banco_c'] ?? '',

            ':id' => $id
        ]);

        // 4. Actualizar `user_disciplina` (solo hay una)
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_disciplina WHERE usuario_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $stmt = $this->db->prepare("UPDATE user_disciplina SET disciplina = :disciplina WHERE usuario_id = :id");
        } else {
            $stmt = $this->db->prepare("INSERT INTO user_disciplina (disciplina, usuario_id) VALUES (:disciplina, :id)");
        }
        $stmt->execute([
            ':disciplina' => $data['disciplina_libre'] ?? '',
            ':id' => $id
        ]);
    }

    public function contarUsuarios($filtroDNI = '', $filtroNombre = '', $filtroNSocio = '')

    {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE 1=1";
        $params = [];

        if (!empty($filtroDNI)) {
            $sql .= " AND dni LIKE :dni";
            $params[':dni'] = '%' . $filtroDNI . '%';
        }

        if (!empty($filtroNombre)) {
            $sql .= " AND nombre LIKE :nombre";
            $params[':nombre'] = '%' . $filtroNombre . '%';
        }

        if (!empty($filtroNSocio)) {
            if (ctype_digit($filtroNSocio)) {
                $sql .= " AND n_socio = :n_socio";
                $params[':n_socio'] = (int) $filtroNSocio;
            } else {
                $sql .= " AND n_socio LIKE :n_socio";
                $params[':n_socio'] = '%' . $filtroNSocio . '%';
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
