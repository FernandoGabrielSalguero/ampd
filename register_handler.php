<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/SessionManager.php';

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['user_name'] ?? '');
    $password = trim($_POST['pass'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($username === '' || $password === '' || $email === '') {
        header("Location: /index.php?error=1");
        exit;
    }

    try {
        // 1) Chequear duplicados
        $stmt = $pdo->prepare("SELECT id FROM users WHERE user_name = :user_name OR email = :email LIMIT 1");
        $stmt->execute(['user_name' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            header("Location: /index.php?error=2");
            exit;
        }

        // 2) Hash de contraseña
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // 3) Crear usuario
        $stmt = $pdo->prepare("
            INSERT INTO users (registration_date, user_name, pass, email)
            VALUES (NOW(), :user_name, :pass, :email)
        ");
        $stmt->execute([
            'user_name' => $username,
            'pass'      => $hashedPassword,
            'email'     => $email
        ]);
        $userId = (int)$pdo->lastInsertId();

        // 4) Garantizar existencia del rol 'socio'
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'socio' LIMIT 1");
        $stmt->execute();
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            $pdo->prepare("INSERT INTO roles (name) VALUES ('socio')")->execute();
            $roleId = (int)$pdo->lastInsertId();
        } else {
            $roleId = (int)$role['id'];
        }

        // 5) Asignar 'socio' al usuario (único rol inicial)
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
        $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);

        // 6) Ir al login
        header("Location: /index.php?registered=1");
        exit;

    } catch (Exception $e) {
        error_log("Error en registro: " . $e->getMessage());
        header("Location: /index.php?error=3");
        exit;
    }
}
