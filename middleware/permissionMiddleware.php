<?php
require_once __DIR__ . '/../core/SessionManager.php';

SessionManager::start();

function requirePermission(string $permission)
{
    if (!SessionManager::hasPermission($permission)) {
        http_response_code(403);
        echo "🚫 Acceso denegado. Se requiere el permiso: <strong>$permission</strong>";
        exit;
    }
}