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
                cuit_beneficiario, cbu_beneficiario, alias_beneficiario, 
                nombre_completo_beneficiario, telefono_beneficiario, evento,
                monto, sellado, impuesto_cheque, retencion,
                total_despues_impuestos, factura, pedido, fecha
            ) VALUES (
                :cuit_beneficiario, :cbu_beneficiario, :alias_beneficiario, 
                :nombre_completo_beneficiario, :telefono_beneficiario, :evento,
                :monto, :sellado, :impuesto_cheque, :retencion,
                :total_despues_impuestos, :factura, :pedido, NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':cuit_beneficiario' => $data['cuit_beneficiario'],
            ':cbu_beneficiario' => $data['cbu_beneficiario'],
            ':alias_beneficiario' => $data['alias_beneficiario'],
            ':nombre_completo_beneficiario' => $data['nombre_completo_beneficiario'],
            ':telefono_beneficiario' => $data['telefono_beneficiario'],
            ':evento' => $data['evento'],
            ':monto' => $data['monto'],
            ':sellado' => $data['sellado'],
            ':impuesto_cheque' => $data['impuesto_cheque'],
            ':retencion' => $data['retencion'],
            ':total_despues_impuestos' => $data['total_despues_impuestos'],
            ':factura' => $data['factura'],
            ':pedido' => $data['pedido']
        ]);
    }
}
