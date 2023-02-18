<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/item.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $product = new Item($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($worker->isLoggedIn) {
        echo $product->getItemLog($data);
    }else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>