<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/transaction.php';

    $database = new Database();
    $db = $database->connect();
    $worker = new Worker($db);
    $transaction = new Transaction($db);
    $data = json_decode(file_get_contents("php://input"), true);
    if ($worker->isLoggedIn) {
        echo $transaction->getTransactions($data);
    }else{
        header("HTTP/1.1 403 Access denied");
    }    
?>