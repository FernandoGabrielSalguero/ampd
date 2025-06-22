<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once '../../models/admin_pagoFacturasModel.php';

// ================================
// 🔍 AJAX: Buscar datos por DNI
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['buscarDni'])) {
    header('Content-Type: application/json');

    $dni = trim($_GET['buscarDni']);
    if (!$dni) {
        echo json_encode(['error' => 'DNI inválido.']);
        exit;
    }

    try {
        // Buscar usuario por DNI
        $stmt = $pdo->prepare("SELECT id_, nombre FROM usuarios WHERE dni = ?");
        $stmt->execute([$dni]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            echo json_encode(['error' => 'No se encontró un usuario con ese DNI.']);
            exit;
        }

        if (headers_sent()) {
    echo json_encode(['error' => '⚠️ Encabezados ya enviados, posible error en otro lado.']);
    exit;
}

        // Buscar cuentas bancarias
        $stmt = $pdo->prepare("SELECT * FROM user_bancarios WHERE usuario_id = ?");
        $stmt->execute([$usuario['id_']]);
        $cuentasRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $cuentas = [];
        foreach (['a', 'b', 'c'] as $sufijo) {
            if (!empty($cuentasRaw[0]["cbu_$sufijo"])) {
                $cuentas[] = [
                    'cbu' => $cuentasRaw[0]["cbu_$sufijo"],
                    'alias' => $cuentasRaw[0]["alias_$sufijo"],
                    'cuit' => $cuentasRaw[0]["cuit_$sufijo"],
                    'banco' => $cuentasRaw[0]["banco_$sufijo"]
                ];
            }
        }

        if (empty($cuentas)) {
            echo json_encode(['error' => 'El usuario no tiene cuentas bancarias cargadas.']);
            exit;
        }

        // Verificar cuota 2025
        $anioActual = date('Y');
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cuotas_socios WHERE usuario_id = ? AND anio = ?");
        $stmt->execute([$usuario['id_'], $anioActual]);
        $cuotaPagada = $stmt->fetchColumn() > 0;

        echo json_encode([
            'usuario_id' => $usuario['id_'],
            'nombre' => $usuario['nombre'],
            'cuentas' => $cuentas,
            'cuota_pagada' => $cuotaPagada
        ]);
        if (headers_sent()) {
    echo json_encode(['error' => '⚠️ Encabezados ya enviados, posible error en otro lado.']);
    exit;
}

        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
        exit;
    }
}

// ================================
// 📄 AJAX: Listado de pagos
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');

    try {
        $stmt = $pdo->query("
            SELECT 
                id_ AS id_,
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

// ================================
// 🚫 POST obligatorio
// ================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido');
}

// ================================
// 📁 Función: Guardar archivos PDF
// ================================
function guardarArchivo($campo, $uploadDir = '../../uploads/evento_pagos/')
{
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

// ================================
// 📥 Procesar datos del formulario
// ================================
$data = [
    'usuario_id' => $_POST['usuario_id'] ?? null,
    'dni_beneficiario' => $_POST['dni_beneficiario'],
    'nombre_completo_beneficiario' => $_POST['nombre_completo_beneficiario'],
    'telefono_beneficiario' => $_POST['telefono_beneficiario'],
    'cuit_beneficiario' => $_POST['cuit_beneficiario'],
    'cbu_beneficiario' => $_POST['cbu_beneficiario'],
    'alias_beneficiario' => $_POST['alias_beneficiario'],
    'evento' => $_POST['evento'],
    'fecha_evento' => $_POST['fecha_evento'],
    'numero_orden' => $_POST['numero_orden'],
    'monto' => floatval($_POST['monto']),
    'sellado' => floatval($_POST['sellado']),
    'impuesto_cheque' => floatval($_POST['impuesto_cheque']),
    'retencion' => floatval($_POST['retencion']),
    'descuento_cuota' => floatval($_POST['descuento_cuota'] ?? 0),
];

// ================================
// ✅ Validación fuerte de campos obligatorios
// ================================
$camposObligatorios = [
    'usuario_id',
    'dni_beneficiario',
    'nombre_completo_beneficiario',
    'cuit_beneficiario',
    'cbu_beneficiario',
    'evento',
    'fecha_evento',
    'numero_orden',
    'monto'
];

foreach ($camposObligatorios as $campo) {
    if (empty($data[$campo])) {
        die("❌ Faltan datos obligatorios: $campo");
    }
}

if (!is_numeric($data['monto']) || $data['monto'] <= 0) {
    die('❌ El monto debe ser un número mayor a 0');
}

if ($data['sellado'] < 0 || $data['impuesto_cheque'] < 0 || $data['retencion'] < 0) {
    die('❌ Los porcentajes no pueden ser negativos');
}

if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$|^\d{4}-\d{2}-\d{2}$/', $data['fecha_evento'])) {
    die('❌ La fecha del evento no es válida');
}

// ================================
// ✅ Validaciones adicionales específicas
// ================================

// Validar CUIT (11 dígitos numéricos)
if (!preg_match('/^\d{11}$/', $data['cuit_beneficiario'])) {
    die('❌ El CUIT debe contener exactamente 11 dígitos numéricos.');
}

// Validar CBU (22 dígitos numéricos)
if (!preg_match('/^\d{22}$/', $data['cbu_beneficiario'])) {
    die('❌ El CBU debe contener exactamente 22 dígitos numéricos.');
}

// Validar que el alias, si existe, tenga entre 6 y 20 caracteres alfanuméricos o guiones bajos
if (!empty($data['alias_beneficiario']) && !preg_match('/^[a-zA-Z0-9_.-]{6,20}$/', $data['alias_beneficiario'])) {
    die('❌ El alias debe tener entre 6 y 20 caracteres válidos (letras, números, guiones, puntos).');
}

// Validar número de orden (evitar duplicados)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pagos_evento WHERE numero_orden = ?");
$stmt->execute([$data['numero_orden']]);
if ($stmt->fetchColumn() > 0) {
    die('❌ Ya existe un pago registrado con ese número de orden.');
}

// ================================
// ✅ Validar campos monetarios y porcentuales no negativos
// ================================
$camposNumericos = [
    'monto' => 'Monto',
    'sellado' => 'Sellado (%)',
    'impuesto_cheque' => 'Impuesto al Cheque (%)',
    'retencion' => 'Retención (%)',
    'descuento_cuota' => 'Descuento por Cuota'
];

foreach ($camposNumericos as $campo => $label) {
    if (!isset($data[$campo]) || !is_numeric($data[$campo]) || $data[$campo] < 0) {
        die("❌ El campo \"{$label}\" debe ser un número mayor o igual a cero.");
    }
}

// ================================
// ✅ Validar fecha del evento
// ================================
$fechaEvento = $data['fecha_evento'] ?? null;

if (!$fechaEvento) {
    die('❌ La fecha del evento es obligatoria.');
}

if (!DateTime::createFromFormat('Y-m-d', $fechaEvento)) {
    die('❌ La fecha del evento no tiene un formato válido (YYYY-MM-DD).');
}

if (strtotime($fechaEvento) > strtotime(date('Y-m-d'))) {
    die('❌ La fecha del evento no puede ser en el futuro.');
}

// ================================
// ✅ Validar número de orden
// ================================
$numeroOrden = trim($data['numero_orden'] ?? '');

if (empty($numeroOrden)) {
    die('❌ El número de orden es obligatorio.');
}

// Solo letras, números, guiones y espacios (hasta 50 caracteres)
if (!preg_match('/^[a-zA-Z0-9\s\-]{1,50}$/', $numeroOrden)) {
    die('❌ El número de orden contiene caracteres inválidos. Solo se permiten letras, números, guiones y espacios (máx. 50 caracteres).');
}

// ================================
// ✅ Validar nombre del evento
// ================================
$evento = trim($data['evento'] ?? '');

if (empty($evento)) {
    die('❌ El nombre del evento es obligatorio.');
}

// Solo letras, números, espacios, tildes, guiones y paréntesis
if (!preg_match('/^[\p{L}0-9\s\-\(\)áéíóúÁÉÍÓÚñÑ]{3,255}$/u', $evento)) {
    die('❌ El nombre del evento contiene caracteres inválidos o es demasiado corto. Mínimo 3 caracteres.');
}

// ================================
// ✅ Validar teléfono del beneficiario (opcional)
// ================================
$telefono = trim($data['telefono_beneficiario'] ?? '');

if (!empty($telefono)) {
    // Solo números, espacios, guiones, paréntesis y el signo +
    if (!preg_match('/^[\d\s\-\+\(\)]{6,25}$/', $telefono)) {
        die('❌ El número de teléfono ingresado no es válido. Permitidos: dígitos, espacios, +, guiones y paréntesis (entre 6 y 25 caracteres).');
    }
}

// 🧮 Calcular total después de impuestos y descuentos
$data['total_despues_impuestos'] = $data['monto']
    - ($data['monto'] * $data['sellado'] / 100)
    - ($data['monto'] * $data['impuesto_cheque'] / 100)
    - ($data['monto'] * $data['retencion'] / 100)
    - $data['descuento_cuota'];

// ================================
// ✅ Validar total después de impuestos
// ================================
if (!is_numeric($data['total_despues_impuestos']) || $data['total_despues_impuestos'] < 0) {
    die('❌ El total final calculado no puede ser negativo. Verificá los valores ingresados.');
}

// ================================
// ✅ Validar tipo de archivo (solo PDF)
// ================================
$archivos = ['pedido', 'factura'];
foreach ($archivos as $campo) {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== 0) {
        die("❌ El archivo {$campo} no se ha subido correctamente.");
    }

    $tipo = mime_content_type($_FILES[$campo]['tmp_name']);
    if ($tipo !== 'application/pdf') {
        die("❌ El archivo '{$campo}' debe ser un PDF válido.");
    }
}
// 📁 Subir archivos
$data['pedido'] = guardarArchivo('pedido');
$data['factura'] = guardarArchivo('factura');

if (!$data['pedido'] || !$data['factura']) {
    die('❌ Error al subir archivos');
}

// 🧾 Marcar si se pagó la cuota anual en este movimiento
$data['cuota_pagada'] = $data['descuento_cuota'] > 0 ? 1 : 0;

// ================================
// 💾 Guardar en la base de datos
// ================================
$model = new PagoEventoModel($pdo);
$model->insertarPagoEvento($data);

// 🧾 Si se descontó la cuota, registrar como pagada
if ($data['cuota_pagada'] && !empty($data['usuario_id'])) {
    $stmt = $pdo->prepare("
    INSERT INTO cuotas_socios (usuario_id, anio, monto, fecha_pago)
    VALUES (?, ?, ?, CURDATE())
");
    $stmt->execute([$data['usuario_id'], date('Y'), $data['descuento_cuota']]);
}

// ✅ Todo OK
echo 'ok';
exit;
