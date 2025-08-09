<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/AdminVariablesModel.php';
header('Content-Type: application/json');

$model = new OperativosModel($pdo);
$method = $_SERVER['REQUEST_METHOD'];

