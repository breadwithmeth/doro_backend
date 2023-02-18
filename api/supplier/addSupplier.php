<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/supplier.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $supplier = new Supplier($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($worker->isLoggedIn) { 
        echo $supplier->addSupplies($data, $worker->worker_id);
    }else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    }
?>