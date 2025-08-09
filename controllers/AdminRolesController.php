<?php
require_once __DIR__ . '/../models/AdminRolesModel.php';

class AdminRolesController {
    private $model;

    public function __construct($pdo) {
        $this->model = new AdminRolesModel($pdo);
    }

    public function getAllUsersWithRoles() {
        return $this->model->getAllUsersWithRoles();
    }

    public function getAllRoles() {
        return $this->model->getAllRoles();
    }

    public function updateUserRole($userId, $roleId) {
        return $this->model->updateUserRole($userId, $roleId);
    }
}
