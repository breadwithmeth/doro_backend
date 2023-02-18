<?php
    include_once '../../config/Database.php';
    include_once '../../models/worker.php';
    include_once '../../config/Headers.php';
    include_once '../../models/recipe.php';
    include_once '../../models/tablet.php';
    include_once '../../models/customer.php';


    $database = new Database();
    $db = $database->connect();
    $customer = new Customer($db);
    $recipe = new Recipe($db);
    $tablet = new Tablet($db);

    $data = json_decode(file_get_contents("php://input"), true);
    if ($customer->isLoggedIn) {
        echo $recipe->buyRecipe($data, $customer->customer_id, $customer->balance);
    }else{
        header("HTTP/1.1 403 Access denied");
    }
    //$data = json_decode(file_get_contents("php://input"), true);
    //echo $worker->login($data['login'], $data['password']);
    


?>