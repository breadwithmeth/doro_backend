<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/shopping_cart.php';
    include_once '../../models/tablet.php';
    include_once '../../models/customer.php';
    include_once '../../models/subscription.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $shopping_cart = new shopping_cart($db);
    $tablet = new Tablet($db);
    $customer = new Customer($db);
    $subscription = new Subscription($db);

    $data = json_decode(file_get_contents("php://input"), true);
    if ($customer->isLoggedIn) {
        echo $subscription->getSubscriptionsCustomer($data,$customer->customer_id);
    }elseif ($worker->isLoggedIn) { 
        echo $subscription->getSubscriptionsCustomer($data, $data["customer_id"]);
    }else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>