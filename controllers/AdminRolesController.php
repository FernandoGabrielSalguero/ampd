<?php

require_once __DIR__ . '/../models/AdminRolesModel.php';

class AdminRolesController {
    private $model;

    public function __construct($pdo) {
        $this->model = new AdminRolesModel($pdo);
    }

    public function getUsersWithRolesAndPermissions() {
        return $this->model->getUsersWithRolesAndPermissions();
    }

    public function getAllRoles() {
        return $this->model->getAllRoles();
    }

    public function getAllPermissions() {
        return $this->model->getAllPermissions();
    }

    public function updateUserRole($userId, $roleId) {
        return $this->model->updateUserRole($userId, $roleId);
    }

    public function updateUserPermissions($userId, $permissions) {
        return $this->model->updateUserPermissions($userId, $permissions);
    }
}
?>