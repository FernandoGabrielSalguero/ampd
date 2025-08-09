<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/SessionManager.php';
require_once __DIR__ . '/core/AuthService.php';

SessionManager::start();

$auth = new AuthService($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['usuario'] ?? '';
    $password = $_POST['contrasena'] ?? '';

    if ($auth->login($username, $password)) {
        $user = SessionManager::getUser();
        $role = $user['role'] ?? '';

        switch ($role) {
            case 'Super_admin':
                header("Location: /views/admin/admin_dashboard.php");
                break;
            case 'socio':
                header("Location: /views/asociado/asociado_dashboard.php");
                break;
            case 'Administrativo':
                header("Location: /views/client/client_dashboard.php");
                break;
            default:
                http_response_code(403);
                echo "Rol no reconocido: " . htmlspecialchars($role);
        }
        exit;
    } else {
        header("Location: /index.php?error=1");
        exit;
    }
}
