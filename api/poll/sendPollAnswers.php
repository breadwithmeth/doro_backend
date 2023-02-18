<?php
    include_once '../../config/Database.php';
    include_once '../../config/Headers.php';
    include_once '../../models/poll.php';
    include_once '../../models/tablet.php';
    include_once '../../models/customer.php';

    $database = new Database();
    $db = $database->connect();
    $poll = new Poll($db);
    $tablet = new Tablet($db);
    $customer = new Customer($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($customer->isLoggedIn) {
        echo $poll->sendPollAnswers($data, $customer->customer_id);
    }else{
        header("HTTP/1.1 403 Access Uguguugugugu");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>