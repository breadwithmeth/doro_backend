<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/menu.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $menu = new Menu($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($worker->isLoggedIn) {
        echo $menu->getMenu($worker->worker_id);
    }else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>