<?php
    include_once '../config/Database.php';
    include_once '../models/worker.php';
    include_once '../config/Headers.php';
    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $data = json_decode(file_get_contents("php://input"), true);
    echo $worker->login($data['login'], $data['password'], $data['area_id']);
    

    
    
    
    


?>