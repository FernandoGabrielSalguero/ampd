<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../config.php';
require_once '../../models/admin_pagoFacturasModel.php';

// ✅ PRIMERO: si es AJAX GET, responder JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');

    try {
        $stmt = $pdo->query("
            SELECT 
                id_ AS id,
                fecha,
                nombre_completo_beneficiario,
                evento,
                monto,
                sellado,
                impuesto_cheque,
                retencion,
                total_despues_impuestos,
                factura,
                pedido
            FROM pagos_evento
            ORDER BY fecha DESC
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// ✅ SI NO ES POST, CORTAMOS (después del GET ajax)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido');
}

// ✅ FUNCIONES
function guardarArchivo($campo, $uploadDir = '../../uploads/evento_pagos/') {
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== 0) {
        return null;
    }

    $nombre = time() . '_' . basename($_FILES[$campo]['name']);
    $ruta = $uploadDir . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $nombre);

    if (move_uploaded_file($_FILES[$campo]['tmp_name'], $ruta)) {
        return str_replace('../../', '/', $ruta);
    }

    return null;
}

// ✅ DATOS DEL FORMULARIO
$data = [
    'cuit_beneficiario' => $_POST['cuit_beneficiario'],
    'cbu_beneficiario' => $_POST['cbu_beneficiario'],
    'alias_beneficiario' => $_POST['alias_beneficiario'],
    'nombre_completo_beneficiario' => $_POST['nombre_completo_beneficiario'],
    'telefono_beneficiario' => $_POST['telefono_beneficiario'],
    'evento' => $_POST['evento'],
    'monto' => floatval($_POST['monto']),
    'sellado' => floatval($_POST['sellado']),
    'impuesto_cheque' => floatval($_POST['impuesto_cheque']),
    'retencion' => floatval($_POST['retencion']),
];

// ✅ CALCULAR TOTAL
$data['total_despues_impuestos'] = $data['monto']
    - ($data['monto'] * $data['sellado'] / 100)
    - ($data['monto'] * $data['impuesto_cheque'] / 100)
    - ($data['monto'] * $data['retencion'] / 100);

// ✅ SUBIDA DE ARCHIVOS
$data['pedido'] = guardarArchivo('pedido');
$data['factura'] = guardarArchivo('factura');

if (!$data['pedido'] || !$data['factura']) {
    die('❌ Error al subir archivos');
}

// ✅ INSERT EN BD
$model = new PagoEventoModel($pdo);
$model->insertarPagoEvento($data);

// ✅ RESPUESTA JS
echo 'ok';
exit;
