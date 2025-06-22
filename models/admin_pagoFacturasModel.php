<?php

require_once __DIR__ . '/../config.php';
class PagoEventoModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Inserta un nuevo registro de pago de evento en la base de datos.
     * 
     * @param array $data Datos del formulario ya validados.
     */
    public function insertarPagoEvento($data)
    {
        $sql = "
            INSERT INTO pagos_evento (
                usuario_id,
                dni_beneficiario,
                nombre_completo_beneficiario,
                telefono_beneficiario,
                cuit_beneficiario,
                cbu_beneficiario,
                alias_beneficiario,
                evento,
                cargado_por_nombre,
                fecha_evento,
                numero_orden,
                monto,
                sellado,
                impuesto_cheque,
                retencion,
                descuento_cuota,
                total_despues_impuestos,
                factura,
                pedido,
                cuota_pagada,
                fecha
            ) VALUES (
                :usuario_id,
                :dni_beneficiario,
                :nombre_completo_beneficiario,
                :telefono_beneficiario,
                :cuit_beneficiario,
                :cbu_beneficiario,
                :alias_beneficiario,
                :cargado_por_nombre,
                :evento,
                :fecha_evento,
                :numero_orden,
                :monto,
                :sellado,
                :impuesto_cheque,
                :retencion,
                :descuento_cuota,
                :total_despues_impuestos,
                :factura,
                :pedido,
                :cuota_pagada,
                NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $exito = $stmt->execute([
            ':usuario_id' => $data['usuario_id'],
            ':dni_beneficiario' => $data['dni_beneficiario'],
            ':nombre_completo_beneficiario' => $data['nombre_completo_beneficiario'],
            ':telefono_beneficiario' => $data['telefono_beneficiario'],
            ':cuit_beneficiario' => $data['cuit_beneficiario'],
            ':cbu_beneficiario' => $data['cbu_beneficiario'],
            ':alias_beneficiario' => $data['alias_beneficiario'],
            ':evento' => $data['evento'],
            ':cargado_por_nombre' => $data['cargado_por_nombre'],
            ':fecha_evento' => $data['fecha_evento'],
            ':numero_orden' => $data['numero_orden'],
            ':monto' => $data['monto'],
            ':sellado' => $data['sellado'],
            ':impuesto_cheque' => $data['impuesto_cheque'],
            ':retencion' => $data['retencion'],
            ':descuento_cuota' => $data['descuento_cuota'],
            ':total_despues_impuestos' => $data['total_despues_impuestos'],
            ':factura' => $data['factura'],
            ':pedido' => $data['pedido'],
            ':cuota_pagada' => $data['cuota_pagada'],
        ]);

        if (!$exito) {
            $error = $stmt->errorInfo();
            die('❌ Error al insertar en pagos_evento: ' . $error[2]);
        }
    }
}
