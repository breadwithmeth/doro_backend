<?php
    include_once '../../config/Database.php';
    include_once '../../config/Headers.php';
    include_once '../../models/customer.php';

    $database = new Database();
    $db = $database->connect();
    $customer = new Customer($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data['qr_uuid'])) {
        echo $customer->login(null, null, $qr_uuid = $data['qr_uuid']);
    }else{
        echo $customer->login($data['login'], $data['password']);
    }
    
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>