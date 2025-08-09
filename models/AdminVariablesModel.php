<?php
class OperativosModel
{
    private $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
}
