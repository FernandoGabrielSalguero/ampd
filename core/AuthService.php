<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../models/AuthModel.php';
require_once __DIR__ . '/SessionManager.php';

class AuthService
{
    private $authModel;

    public function __construct($pdo)
    {
        $this->authModel = new AuthModel($pdo);
    }

    public function login(string $username, string $password): bool
    {
        $user = $this->authModel->login($username, $password);
        if (!is_array($user)) return false;

        $userSessionData = [
            'id'       => $user['id'],
            'username' => $user['user_name'],
            'email'    => $user['email'],
            'role'     => $user['role'],
        ];

        SessionManager::setUser($userSessionData);
        return true;
    }

    public function logout()
    {
        SessionManager::destroy();
    }
}
