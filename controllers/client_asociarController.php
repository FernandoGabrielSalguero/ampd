<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../models/client_asociarModel.php';

class client_AsociarController {
    private $model;

    public function asociarNuevoSocio($pdo) {
        $this->model = new client_asociarModel($pdo);
    }


}
