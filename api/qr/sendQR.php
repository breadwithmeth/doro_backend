<?php
    include_once '../../config/Database.php';
    include_once '../../models/customer.php';
    include_once '../../config/Headers.php';
    include_once '../../models/qr.php';

    $database = new Database();
    $db = $database->connect();
    $customer = new Customer($db);
    $qr = new QR($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($customer->isLoggedIn) {
        echo $qr->sendQR($data);
    }else{
        header("HTTP/1.1 403 Access denied");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>