<?php
    include_once '../../config/Database.php';
    include_once '../../models/customer.php';
    include_once '../../config/Headers.php';
    include_once '../../models/service.php'; 
 
    $database = new Database();
    $db = $database->connect();
    $customer = new Customer($db);
    $service = new Service($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($customer->isLoggedIn) {
        echo $service->buyService($data, $customer->customer_id, $customer->balance );
    }else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>