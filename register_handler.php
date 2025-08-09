<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/SessionManager.php';

SessionManager::start();

date_default_timezone_set('America/Argentina/Buenos_Aires'); // asegura fecha/hora AR

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['user_name'] ?? '');
    $password = trim($_POST['pass'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if ($username === '' || $password === '' || $email === '') {
        header("Location: /index.php?error=1");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1) Duplicados
        $stmt = $pdo->prepare("SELECT id FROM users WHERE user_name = :user_name OR email = :email LIMIT 1");
        $stmt->execute(['user_name' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            header("Location: /index.php?error=2");
            exit;
        }

        // 2) Hash
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // 3) Insert usuario (hora AR desde PHP)
        $stmt = $pdo->prepare("
            INSERT INTO users (registration_date, user_name, pass, email)
            VALUES (:registration_date, :user_name, :pass, :email)
        ");
        $stmt->execute([
            'registration_date' => date('Y-m-d H:i:s'),
            'user_name'         => $username,
            'pass'              => $hashedPassword,
            'email'             => $email
        ]);

        $userId = (int)$pdo->lastInsertId();

        // 4) Obtener id del rol 'socio' (precargado)
        $roleId = (int)$pdo->query("SELECT id FROM roles WHERE name = 'socio' LIMIT 1")->fetchColumn();
        if (!$roleId) {
            // fallback por si roles no están cargados
            $pdo->prepare("INSERT INTO roles (name) VALUES ('socio')")->execute();
            $roleId = (int)$pdo->lastInsertId();
        }

        // 5) Asignar rol
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
        $stmt->execute(['user_id' => $userId, 'role_id' => $roleId]);

        $pdo->commit();

        header("Location: /index.php?registered=1");
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error en registro: " . $e->getMessage());
        header("Location: /index.php?error=3");
        exit;
    }
}
