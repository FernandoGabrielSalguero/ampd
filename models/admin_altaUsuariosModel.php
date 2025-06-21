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

    public function obtenerUsuarios($filtroDNI = '', $filtroNombre = '', $limit = 20, $offset = 0)
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
            $stmt = $this->db->prepare("UPDATE user_bancarios SET alias_a = :alias, cbu_a = :cbu, titular_a = :titular, cuit_a = :cuit, banco_a = :banco WHERE usuario_id = :id");
        } else {
            $stmt = $this->db->prepare("INSERT INTO user_bancarios (alias_a, cbu_a, titular_a, cuit_a, banco_a, usuario_id)
                                    VALUES (:alias, :cbu, :titular, :cuit, :banco, :id)");
        }
        $stmt->execute([
            ':alias' => $data['alias_a'] ?? '',
            ':cbu' => $data['cbu_a'] ?? '',
            ':titular' => $data['titular_a'] ?? '',
            ':cuit' => $data['cuit_a'] ?? '',
            ':banco' => $data['banco_a'] ?? '',
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

    public function contarUsuarios($filtroDNI = '', $filtroNombre = '')
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

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
