<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/service.php';
    include_once '../../models/customer.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $customer = new Customer($db);
    $service = new Service($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($worker->isLoggedIn) {
        echo $service->getServices($data, $worker);
    }elseif($customer->isLoggedIn){
        echo $service->getServicesCustomer($data, $customer->customer_id);
    }
    else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>