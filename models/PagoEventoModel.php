<?php

require_once __DIR__ . '/../config.php';

class PagoEventoModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function insertarPagoEvento($data) {
        $sql = "
            INSERT INTO pagos_evento (
                evento_id, usuario_id, monto, cuit_beneficiario, cbu_beneficiario,
                pedido, factura, fecha_solicitud, cargado_por
            ) VALUES (
                :evento_id, :usuario_id, :monto, :cuit_beneficiario, :cbu_beneficiario,
                :pedido, :factura, NOW(), :cargado_por
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':evento_id' => $data['evento_id'],
            ':usuario_id' => $data['usuario_id'],
            ':monto' => $data['monto'],
            ':cuit_beneficiario' => $data['cuit_beneficiario'],
            ':cbu_beneficiario' => $data['cbu_beneficiario'],
            ':pedido' => $data['pedido'],
            ':factura' => $data['factura'],
            ':cargado_por' => $data['cargado_por']
        ]);
    }
}
