<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/SessionManager.php';

SessionManager::start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['user_name'] ?? '');
    $password = trim($_POST['pass'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($username) || empty($password) || empty($email)) {
        header("Location: /index.php?error=1");
        exit;
    }

    try {
        // 1. Validar que no exista el usuario
        $stmt = $pdo->prepare("SELECT id FROM users WHERE user_name = :user_name OR email = :email LIMIT 1");
        $stmt->execute(['user_name' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            header("Location: /index.php?error=2"); // Usuario ya existe
            exit;
        }

        // 2. Hashear contraseña
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // 3. Insertar usuario
        $stmt = $pdo->prepare("INSERT INTO users (registration_date, user_name, pass, email) 
                               VALUES (NOW(), :user_name, :pass, :email)");
        $stmt->execute([
            'user_name' => $username,
            'pass' => $hashedPassword,
            'email' => $email
        ]);

        $userId = $pdo->lastInsertId();

        // 4. Asegurar existencia del rol 'client'
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'client' LIMIT 1");
        $stmt->execute();
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            // Si no existe, lo creamos
            $pdo->prepare("INSERT INTO roles (name) VALUES ('client')")->execute();
            $roleId = $pdo->lastInsertId();
        } else {
            $roleId = $role['id'];
        }

        // 5. Asegurar existencia del permiso 'access_dashboard'
        $stmt = $pdo->prepare("SELECT id FROM permissions WHERE name = 'access_dashboard' LIMIT 1");
        $stmt->execute();
        $perm = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$perm) {
            $pdo->prepare("INSERT INTO permissions (name) VALUES ('access_dashboard')")->execute();
            $permId = $pdo->lastInsertId();
        } else {
            $permId = $perm['id'];
        }

        // 6. Asegurar asignación del permiso al rol
        $stmt = $pdo->prepare("SELECT 1 FROM role_permissions WHERE role_id = :role_id AND permission_id = :perm_id");
        $stmt->execute(['role_id' => $roleId, 'perm_id' => $permId]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :perm_id)")
                ->execute(['role_id' => $roleId, 'perm_id' => $permId]);
        }

        // 7. Asignar el rol al usuario
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
        $stmt->execute([
            'user_id' => $userId,
            'role_id' => $roleId
        ]);

        // 8. Redirigir al login
        header("Location: /index.php?registered=1");
        exit;

    } catch (Exception $e) {
        error_log("Error en registro: " . $e->getMessage());
        header("Location: /index.php?error=3");
        exit;
    }
}
?>
