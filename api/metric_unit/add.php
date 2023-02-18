<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/metric_unit.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $metric_unit = new metric_unit($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($worker->isLoggedIn) {
        echo $metric_unit->add($data, $worker->worker_id);
    }else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    } 


?>