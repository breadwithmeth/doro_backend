<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/subscription.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $subscription = new Subscription($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($worker->isLoggedIn) { 
        echo $subscription->addSubscription($data, $worker->worker_id);
    }else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    }
?>