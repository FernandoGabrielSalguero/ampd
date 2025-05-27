<?php
require_once '../../config.php';
require_once '../../models/PagoEventoModel.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    // 🟢 AJAX: devolver listado de pagos
    header('Content-Type: application/json');

    try {
        $stmt = $pdo->query("
            SELECT 
                pe.id,
                CONCAT(ui.nombre, ' ', ui.apellido) AS beneficiario,
                pe.evento_id AS contrato,
                pe.monto,
                pe.retencion,
                pe.fecha_solicitud,
                'Cargado' AS estado,
                pe.pedido,
                pe.factura
            FROM pagos_evento pe
            INNER JOIN user_info ui ON ui.usuario_id = pe.usuario_id
            ORDER BY pe.fecha_solicitud DESC
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error DB: ' . $e->getMessage()]);
    }
    exit;
}

// 🟠 POST: procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $evento_id = intval($_POST['evento_id']);
    $usuario_id = intval($_POST['usuario_id']);
    $monto = floatval($_POST['monto']);
    $cuit_beneficiario = trim($_POST['cuit_beneficiario']);
    $cbu_beneficiario = trim($_POST['cbu_beneficiario']);
    $cargado_por_usuario = trim($_POST['cargado_por']);

    // Buscar ID real del usuario que carga
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->execute([$cargado_por_usuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $cargado_por = $row['id'] ?? null;

    if (!$evento_id || !$usuario_id || !$monto || !$cuit_beneficiario || !$cbu_beneficiario || !$cargado_por) {
        die('Faltan datos obligatorios');
    }

    $uploadDir = '../../uploads/evento_pagos/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    function guardarArchivo($inputName, $uploadDir) {
        $original = basename($_FILES[$inputName]['name']);
        $safeName = time() . '_' . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", $original);
        $destino = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $destino)) {
            return str_replace('../../', '/', $destino);
        }
        return null;
    }

    $pedidoURL = guardarArchivo('pedido', $uploadDir);
    $facturaURL = guardarArchivo('factura', $uploadDir);

    if (!$pedidoURL || !$facturaURL) {
        die('Error al subir archivos');
    }

    $model = new PagoEventoModel($pdo);
    $model->insertarPagoEvento([
        'evento_id' => $evento_id,
        'usuario_id' => $usuario_id,
        'monto' => $monto,
        'cuit_beneficiario' => $cuit_beneficiario,
        'cbu_beneficiario' => $cbu_beneficiario,
        'pedido' => $pedidoURL,
        'factura' => $facturaURL,
        'cargado_por' => $cargado_por
    ]);

    header("Location: ../views/admin/admin_pagoFacturas.php?success=1");
    exit;
}

die('Solicitud no válida');
