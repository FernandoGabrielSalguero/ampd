<?php
require_once __DIR__ . '/../config.php';

class AdminAltaUsuariosModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function obtenerUsuarios($filtroDNI = '', $filtroNombre = '')
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

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
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
}
