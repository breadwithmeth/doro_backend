<?php
class Recipe{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    
    function getRecipies($data){
        $query = "SELECT * FROM `recipes`";
        if (!empty($data['filter']['keyword'])) {
            $query .= " WHERE title LIKE '{$data['filter']['keyword']}%'";
        }
        $result = $this->con->query($query);
        $resultArr = [];
        $recipes = [];
        while ($row = $result->fetch_assoc()) {
            $ingredients = [];
            $query1 = "SELECT `ri`.`amount`, `ri`.`recipe_id`, `ri`.recipe_ingredient_id, `i`.`name`, `i`.`metric_unit_id`, `mu`.`name` `metric_unit`, (SELECT 
            (
                (
                IFNULL
                    ((SELECT SUM(`s`.amount) amount  FROM `supplies` `s` WHERE `ri`.`item_id` = `s`.`item_id` GROUP BY `s`.`item_id`), 0) - 

                (
                    (IFNULL
                        ((SELECT SUM(`io`.amount) amount FROM items_orders `io` WHERE `ri`.`item_id` = `io`.`item_id` GROUP BY `io`.`item_id`), 0)) +
                    (IFNULL
                        ((SELECT SUM(`rio`.amount) amount FROM recipe_ingredients_orders `rio` WHERE `ri`.`item_id` = `rio`.`item_id` GROUP BY `rio`.`item_id`), 0))
                )
                 
                )
            )) stock
             FROM `recipe_ingredients` `ri`
            LEFT JOIN `items` `i` ON `i`.`item_id` = `ri`.`item_id` 
            LEFT JOIN metric_units `mu` ON `mu`.`metric_unit_id` = `i`.`metric_unit_id`
            WHERE `ri`.`recipe_id` = {$row['recipe_id']}";

            // $query1 = "SELECT `ri`.`amount`, `ri`.`recipe_id`, `ri`.recipe_ingredient_id, `i`.`name`, `i`.`metric_unit_id`, `mu`.`name` `metric_unit`
            //  FROM `recipe_ingredients` `ri`
            // LEFT JOIN `items` `i` ON `i`.`item_id` = `ri`.`item_id` 
            // LEFT JOIN metric_units `mu` ON `mu`.`metric_unit_id` = `i`.`metric_unit_id`
            // WHERE `ri`.`recipe_id` = {$row['recipe_id']}";
            //return $query1;
            $avaibility = true;
            $result1 = $this->con->query($query1);
            while ($row1 = $result1->fetch_assoc()) {
                if ($row1['stock'] >= $row1['amount']) {
                    $row1['aviability'] = true;
                }else{
                    $row1['aviability'] = false;
                    $avaibility = false;
                }
                array_push($ingredients, $row1);
            }
            $row['aviability'] = $avaibility;
            $row['ingredients'] = $ingredients;
            array_push($resultArr, $row);
        }
        // foreach ($resultArr as $row) {
        //     $query = "SELECT * FROM `recipe_ingredients` WHERE `recipe_ingredient_id` = {$row['recipe_id']}";
        //     $result = $this->con->query($query);
        //     while ($row1 = $result->fetch_assoc()) {
        //         array_push($row['ingredients'], $row1);

        //     }   
        // }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function addRecipe($data){
        if (isset($data['title']) && isset($data['description'])) {
            if (($_FILES['photo']['name']!="")){
                if($_FILES['photo']['size'] != 0){
                    $target_dir = "../../media/";
                    $file = $_FILES['photo']['name'];
                    $path = pathinfo($file);
                    $filename = $path['filename'];
                    $ext = $path['extension'];
                    $temp_name = $_FILES['photo']['tmp_name'];
                    $new_file_name = uniqid().".".$ext;
                    $path_filename_ext = $target_dir.$new_file_name;
                    move_uploaded_file($temp_name,$path_filename_ext);
                    $query = "INSERT INTO `recipes`(`title`,`description`, `photo`) VALUES('{$data['title']}', '{$data['description']}', 'https://new.doro.kz/media/{$new_file_name}')";
                }else{
                    header("HTTP/1.1 413 file is too big!");
                    return json_encode(false);
                }
            }else {
                $query = "INSERT INTO `recipes`(`title`,`description`, `price`) VALUES('{$data['title']}', '{$data['description']}', '{$data['price']}')";
            }
            $result = $this->con->query($query);
            if ($result) {
                $last_id = $this->con->insert_id;
                if (isset($data['ingredients'])) {
                    $dataI = json_decode($data['ingredients'], true);
                    $query = "INSERT INTO `recipe_ingredients`(`recipe_id`, `item_id`, `amount`) VALUES ";
                    foreach ($dataI as $row) {
                        $query .= "($last_id, '{$row['item_id']}', '{$row['amount']}'),";
                    }
                    $query = substr($query, 0, -1);
                    // return $query;
                    $result = $this->con->query($query);
                    return json_encode($result, JSON_UNESCAPED_UNICODE);
                }
                
            }
        }
        if (($_FILES['photo']['name']!="")){
            // Where the file is going to be stored
                $target_dir = "../../media/";
                $file = $_FILES['photo']['name'];
                $path = pathinfo($file);
                $filename = $path['filename'];
                $ext = $path['extension'];
                $temp_name = $_FILES['photo']['tmp_name'];
                $path_filename_ext = $target_dir.uniqid().".".$ext;
                move_uploaded_file($temp_name,$path_filename_ext);
        }
        return json_encode($query);
    }

    function deleteRecipe($data){
        if (isset($data['recipe_id'])) {
            $query = "DELETE FROM `recipes` WHERE recipe_id = '{$data['recipe_id']}'";
            $result = $this->con->query($query);
            if (!$result) {
                header("HTTP/1.1 400 no such recipe_id");
                return json_encode(false);
            }
            $query = "DELETE FROM `recipe_ingredients` WHERE recipe_id = '{$data['recipe_id']}'";
            $result = $this->con->query($query);
            return json_encode(true);
        }else{
            header("HTTP/1.1 400 no");
            return json_encode(false);        
        }
    }

    function sellRecipe($data, $worker_id, $tablet){
        if (isset($data['qr'])) {
            if ($data['qr'] = true) {
                $uuid = time();
                $query = "INSERT INTO `qr_tablet`(`type`, `instance_id`, `device_id`, `qr_uuid`, `worker_id`) VALUES ('sell_recipe', '{$data['recipe_id']}', 1, '{$uuid}', {$worker_id})";
                $result = $this->con->query($query);
                $tablet->sendQR('sell_recipe', $uuid, $worker_id);
                return($result);
            }
        }else {
            $recipe = $this->con->query("SELECT price FROM recipes WHERE recipe_id = '{$data['recipe_id']}'");
            $recipePrice = $recipe->fetch_assoc()['price'];
            $query = "INSERT INTO `recipes_orders`(`customer_id`, `worker_id`, `recipe_id`, `amount`, `price`) VALUES ('{$data['customer_id']}', '{$worker_id}', '{$data['recipe_id']}', '1', '{$recipePrice}')";
            $result = $this->con->query($query);
            if ($result) {
                $recipe_order_id = $this->con->insert_id;
                $recipe_ingredients = [];
                $query = "SELECT * FROM recipe_ingredients WHERE recipe_id = '{$data['recipe_id']}'";
                $result = $this->con->query($query);
                while ($row = $result->fetch_assoc()) {
                    array_push($recipe_ingredients, $row);
                }
                foreach ($recipe_ingredients as $value) {
                    $this->con->query("INSERT INTO `recipe_ingredients_orders`(`item_id`, `customer_id`, `worker_id`, `amount`, `recipe_id`, recipe_order_id) VALUES ('{$value['item_id']}', '{$value['customer_id']}', '{$worker_id}', '{$value['amount']}', '{$value['recipe_id']}', '{$recipe_order_id}')");
                }
            }
            return json_encode(true);
            
        }


    }

    function buyRecipe($data, $customer_id, $balance){
        $recipe = $this->con->query("SELECT price FROM recipes WHERE recipe_id = '{$data['recipe_id']}'");
            $recipePrice = $recipe->fetch_assoc()['price'];
            if ($balance < $recipePrice) {
                header("HTTP/1.1 402 low balnce");
                return json_encode(false);
            }
        $query = "INSERT INTO `recipes_orders`(`customer_id`, `worker_id`, `recipe_id`, `amount`, `price`) VALUES ('{$customer_id}', '{$data['worker_id']}', '{$data['recipe_id']}', '1', '{$recipePrice}')";
        $result = $this->con->query($query);
        if ($result) {
            $recipe_order_id = $this->con->insert_id;
            $recipe_ingredients = [];
            $query = "SELECT * FROM recipe_ingredients WHERE recipe_id = '{$data['recipe_id']}'";
            $result = $this->con->query($query);
            while ($row = $result->fetch_assoc()) {
                array_push($recipe_ingredients, $row);
            }
            foreach ($recipe_ingredients as $value) {
                $this->con->query("INSERT INTO `recipe_ingredients_orders`(`item_id`, `customer_id`, `worker_id`, `amount`, `recipe_id`, recipe_order_id) VALUES ('{$value['item_id']}', '{$customer_id}', '{$data['worker_id']}', '{$value['amount']}', '{$value['recipe_id']}', '{$recipe_order_id}')");
            }
        }
        return json_encode(true);
    }
}