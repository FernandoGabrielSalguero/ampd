<?php
// controllers/auth.php

require_once __DIR__ . '/../models/AuthModel.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuit = $_POST['cuit'];
    $contrasena = $_POST['contrasena'];

    $auth = new AuthModel($pdo);
    $user = $auth->login($cuit, $contrasena);

    if ($user) {
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['correo'] = $user['correo'];
        $_SESSION['cuit'] = $user['cuit'];
        $_SESSION['telefono'] = $user['telefono'];
        $_SESSION['observaciones'] = $user['observaciones'];

        switch ($user['rol']) {
            case 'cooperativa':
                header('Location: /views/cooperativa/dashboard.php');
                break;
            case 'productor':
                header('Location: /views/productor/dashboard.php');
                break;
            case 'sve':
                header('Location: /views/sve/sve_dashboard.php');
                break;
        }
        exit;
    } else {
        $error = "CUIT, contraseña inválidos o permiso no habilitado.";
    }
}
include __DIR__ . '/../views/sve/login.php';
