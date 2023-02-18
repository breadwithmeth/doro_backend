<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/training.php';
    include_once '../../models/customer.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $training = new Training($db);
    $customer = new Customer($db);

    $data = json_decode(file_get_contents("php://input"), true);
    if ($customer->isLoggedIn) {
        echo $training->enrollTraining($data, $customer->customer_id);
    }
    
    else{
        header("HTTP/1.1 403 Access denied");
    }    
?>