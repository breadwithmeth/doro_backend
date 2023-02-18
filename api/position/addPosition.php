<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/position.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $position = new Position($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($worker->isLoggedIn) {
        echo $position->addPosition($data);
    }else{
        header("HTTP/1.1 403 Access denied");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>