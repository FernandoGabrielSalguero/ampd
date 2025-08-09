<?php
class SessionManager
{
    const SESSION_TIMEOUT = 1200; // 20 minutos

    public static function start()
    {
        ini_set('session.gc_maxlifetime', self::SESSION_TIMEOUT);
        session_set_cookie_params([
            'lifetime' => self::SESSION_TIMEOUT,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_start();

        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > self::SESSION_TIMEOUT) {
            self::destroy();
            header("Location: /index.php?expired=1");
            exit;
        }

        $_SESSION['LAST_ACTIVITY'] = time();
    }

    public static function destroy()
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function setUser(array $userData)
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $userData;
        $_SESSION['LAST_ACTIVITY'] = time();
    }

    public static function getUser()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function isLoggedIn()
    {
        return isset($_SESSION['user']);
    }

    public static function requireRole(string $role)
    {
        if (!self::isLoggedIn() || ($_SESSION['user']['role'] ?? null) !== $role) {
            header("Location: /index.php");
            exit;
        }
    }
}
