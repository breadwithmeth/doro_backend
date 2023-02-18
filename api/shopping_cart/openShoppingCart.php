<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/shopping_cart.php';
    include_once '../../models/tablet.php';
    include_once '../../models/customer.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $shopping_cart = new shopping_cart($db);
    $tablet = new Tablet($db);
    $customer = new Customer($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($worker->isLoggedIn) {
        echo $shopping_cart->openShoppingCart($data, $worker, $customer);
    }else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>