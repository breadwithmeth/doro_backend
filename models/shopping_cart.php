<?php
class shopping_cart{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    
    function openShoppingCart($data, $worker, $customer){
        $shopping_cart = [];
        $query = "SELECT * FROM `shopping_carts` WHERE worker_id = '{$worker->worker_id}' AND status = 0 AND customer_id = '{$data['customer_id']}'";
        $result = $this->con->query($query)->fetch_assoc();
        if (!empty($result)) {
            $types = ["service", "subscription", "item", "recipe"];
            $shopping_cart['cart_id'] = $result['cart_id'];
            $customer->setID($data['customer_id']);
            $shopping_cart['customer_balance'] = $customer->balance;
            $shopping_cart['customer_id'] = $customer->customer_id;
            $shopping_cart['customer_name'] = $customer->name;
            $shopping_cart['summary'] = 0;

            $query = "SELECT SUM(amount) amount, relation_id,
            CASE `type` 
                WHEN \"service\" THEN (SELECT services.name FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN \"recipe\" THEN (SELECT recipes.title FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN \"subscription\" THEN (SELECT subscriptions.name FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN \"item\" THEN (SELECT items.name FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS name
                
            FROM shopping_cart_relations  WHERE cart_id = '{$shopping_cart['cart_id']}'";
            $query .= " GROUP BY type, instance_id";
            $goods = $this->con->query($query);
            $goodsArray = [];
            while ($row = $goods->fetch_assoc()) {
                array_push($goodsArray, $row);
            }
            $shopping_cart['goods'] = $goodsArray;
            $query = "SELECT SUM(price) summary FROM (SELECT *,
            CASE `type` 
                WHEN 'service' THEN (SELECT services.price * shopping_cart_relations.amount FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN 'recipe' THEN (SELECT recipes.price * shopping_cart_relations.amount FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN 'subscription' THEN (SELECT subscriptions.price* shopping_cart_relations.amount  FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN 'item' THEN (SELECT items.price * shopping_cart_relations.amount  FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS price
                
            FROM shopping_cart_relations  WHERE cart_id = '{$shopping_cart['cart_id']}') temp";
            $summary = $this->con->query($query)->fetch_assoc()['summary'];
            $shopping_cart['summary'] = $summary;

            return json_encode($shopping_cart, JSON_UNESCAPED_UNICODE);
        }else {
            $query = "INSERT INTO `shopping_carts`(`customer_id`, `worker_id`) VALUES ('{$data['customer_id']}', '{$worker->worker_id}')";
            $result = $this->con->query($query);
            $shopping_cart['cart_id'] = $this->con->insert_id;
            if ($result) {
                try {
                    $customer->setID($data['customer_id']);
                    $shopping_cart['customer_balance'] = $customer->balance;
                    $shopping_cart['customer_id'] = $customer->customer_id;
                    $shopping_cart['customer_name'] = $customer->name;
                    return json_encode($shopping_cart, JSON_UNESCAPED_UNICODE);
                        
                } catch (\Throwable $th) {
                    return $th;
                }
                
            }

            return json_encode(false);
        }
    }

    function addToShoppingCart($data, $worker, $customer){
        $query = "SELECT * FROM `shopping_carts` WHERE worker_id = '{$worker->worker_id}' AND status = 0 AND customer_id = '{$data['customer_id']}'";
        $result = $this->con->query($query)->fetch_assoc();
        // return json_encode($result);
        if ($result) {
            $customer->setID($data['customer_id']);
           

            if ($data['type'] == "service" || $data['type'] == "subscription") {
                $query = "INSERT INTO `shopping_cart_relations`(`instance_id`, `type`, `amount`, `cart_id`) VALUES ('{$data['instance_id']}', '{$data['type']}', 1, '{$result['cart_id']}')";
            }elseif ($data['type'] == "item" || $data['type'] == "recipe") {
                $query = "INSERT INTO `shopping_cart_relations`(`instance_id`, `type`, `amount`, `cart_id`) VALUES ('{$data['instance_id']}', '{$data['type']}', '{$data['amount']}', '{$result['cart_id']}')";
            }
            $resultRelation = $this->con->query($query);
            return json_encode($resultRelation);
        } else {
            return json_encode(false);
        }

    }

    function deleteFromShoppingCart($data){
        // $query = "DELETE FROM `shopping_cart_relations` WHERE `relation_id` = '{$data['relation_id']}'";
        // $query = "DELETE FROM `shopping_cart_relations` WHERE instance_id = (SELECT instance_id FROM shopping_cart_relations WHERE relation_id = '{$data['relation_id']}') AND instance_id = (SELECT `type` FROM shopping_cart_relations WHERE relation_id = '{$data['relation_id']}') AND cart_id = (SELECT `cart_id` FROM shopping_cart_relations WHERE relation_id = '{$data['relation_id']}')";
        $query = "SELECT * FROM shopping_cart_relations WHERE `relation_id` = '{$data['relation_id']}'";
        $instance_info = $this->con->query($query)->fetch_assoc();
        $query = "DELETE FROM `shopping_cart_relations` WHERE `instance_id` = '{$instance_info['instance_id']}' AND `type` = '{$instance_info['type']}' AND `cart_id` = '{$instance_info['cart_id']}'";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function sellShoppingCart($data, $worker, $tablet){
        $uuid = time();
                $query = "INSERT INTO `qr_tablet`(`type`, `instance_id`, `device_id`, `qr_uuid`, `worker_id`) VALUES ('sell_cart', '{$data['cart_id']}', 1, '{$uuid}', {$worker->worker_id})";
                $result = $this->con->query($query);
                $tablet->sendQR('sell_cart', $uuid, $worker->worker_id);
                $i = 0;
                while ($i < 10) {
                    sleep(10);
                    $query = "SELECT status FROM `shopping_carts` WHERE '{$data['cart_id']}' ";
                    $result = $this->con->query($query)->fetch_assoc();
                    if ($result['status'] != 0) {
                        return($result['status']);
                        
                    }
                }
    }

    function getShoppingCartTablet($data, $worker){
        $query = "SELECT * FROM `qr_tablet` WHERE qr_uuid = '{$data['uuid']}' AND worker_id = '{$worker->worker_id}'";
        $result = $this->con->query($query)->fetch_assoc();
        $query = "SELECT SUM(price) summary FROM (SELECT *,
            CASE `type` 
                WHEN 'service' THEN (SELECT services.price * shopping_cart_relations.amount FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN 'recipe' THEN (SELECT recipes.price * shopping_cart_relations.amount FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN 'subscription' THEN (SELECT subscriptions.price* shopping_cart_relations.amount  FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN 'item' THEN (SELECT items.price * shopping_cart_relations.amount  FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS price
                
            FROM shopping_cart_relations  WHERE cart_id = '{$result['instance_id']}') temp";
            $summary = $this->con->query($query)->fetch_assoc();
        return json_encode($summary);


    }

    function getShoppingCartCustomer($data, $customer){
        $shopping_cart = [];
        $query = "SELECT * FROM `shopping_carts` WHERE cart_id = '{$data['cart_id']}'";
        $result = $this->con->query($query)->fetch_assoc();
        $shopping_cart['cart_id'] = $result['cart_id'];
            $shopping_cart['customer_balance'] = $customer->balance;
            $shopping_cart['customer_id'] = $customer->customer_id;
            $shopping_cart['customer_name'] = $customer->name;
            $shopping_cart['summary'] = 0;

            $query = "SELECT *,
            CASE `type` 
                WHEN \"service\" THEN (SELECT services.name FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN \"recipe\" THEN (SELECT recipes.title FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN \"subscription\" THEN (SELECT subscriptions.name FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN \"item\" THEN (SELECT items.name FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS name,
            CASE `type` 
                WHEN \"service\" THEN (SELECT services.price FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN \"recipe\" THEN (SELECT recipes.price FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN \"subscription\" THEN (SELECT subscriptions.price FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN \"item\" THEN (SELECT items.price FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS price,
            CASE `type` 
                WHEN \"service\" THEN (SELECT (services.price * shopping_cart_relations.amount) price_total FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN \"recipe\" THEN (SELECT (recipes.price * shopping_cart_relations.amount) price_total FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN \"subscription\" THEN (SELECT (subscriptions.price * shopping_cart_relations.amount) price_total FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN \"item\" THEN (SELECT (items.price * shopping_cart_relations.amount) price_total FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS price_total
                
            FROM shopping_cart_relations  WHERE cart_id = '{$shopping_cart['cart_id']}'";
            $goods = $this->con->query($query);
            $goodsArray = [];
            while ($row = $goods->fetch_assoc()) {
                array_push($goodsArray, $row);
            }
            $shopping_cart['goods'] = $goodsArray;
            $query = "SELECT SUM(price) summary FROM (SELECT *,
            CASE `type` 
                WHEN 'service' THEN (SELECT services.price * shopping_cart_relations.amount FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN 'recipe' THEN (SELECT recipes.price * shopping_cart_relations.amount FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN 'subscription' THEN (SELECT subscriptions.price* shopping_cart_relations.amount  FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN 'item' THEN (SELECT items.price * shopping_cart_relations.amount  FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS price
                
            FROM shopping_cart_relations  WHERE cart_id = '{$shopping_cart['cart_id']}') temp";
            $summary = $this->con->query($query)->fetch_assoc()['summary'];
            $shopping_cart['summary'] = $summary;

            return json_encode($shopping_cart, JSON_UNESCAPED_UNICODE);
    }


    function buyShoppingCart($data, $customer, $tablet){
        try {
        
        $query = "SELECT * FROM `shopping_carts` WHERE cart_id = '{$data['cart_id']}'";
        $shopping_cart = $this->con->query($query)->fetch_assoc();
        if ($customer->customer_id = $shopping_cart['customer_id']) {
            $query = "SELECT SUM(price) summary FROM (SELECT *,
            CASE `type` 
                WHEN 'service' THEN (SELECT services.price * shopping_cart_relations.amount FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN 'recipe' THEN (SELECT recipes.price * shopping_cart_relations.amount FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN 'subscription' THEN (SELECT subscriptions.price* shopping_cart_relations.amount  FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN 'item' THEN (SELECT items.price * shopping_cart_relations.amount  FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS price
                
            FROM shopping_cart_relations  WHERE cart_id = '{$shopping_cart['cart_id']}') temp";
            $summary = $this->con->query($query)->fetch_assoc()['summary'];
            if ($summary > $customer->balance) {
                header("HTTP/1.1 402 low balance");
                    return json_encode(false);
            }else{
                $query = "SELECT *                    
                FROM shopping_cart_relations  WHERE cart_id = '{$shopping_cart['cart_id']}'";
                $result = $this->con->query($query);
                $instances = [];
                while ($row = $result->fetch_assoc()) {
                    array_push($instances, $row);
                }
                foreach ($instances as $instance) {
                    if ($instance['type']=="service") {
                        $query = "SELECT * FROM services WHERE  service_id = '{$instance['instance_id']}'";
                        $service = $this->con->query($query)->fetch_assoc();
                        $query = "INSERT INTO `services_orders`(`service_id`, `customer_id`, `price`, `worker_id`) VALUES ('{$service['service_id']}', '{$customer->customer_id}','{$service['price']}', '{$shopping_cart['worker_id']}')";
                        $result = $this->con->query($query);
                        if ($result) {
                            $service_order_id = $this->con->insert_id;
                            $query = "SELECT * FROM `service_ingredients` WHERE service_id = '{$service['service_id']}'";
                                $result = $this->con->query($query);
                                $service_ingredients = [];
                                while ($row = $result->fetch_assoc()) {
                                    array_push($service_ingredients, $row);
                                }
                                foreach ($service_ingredients as $value) {
                                    $query = "INSERT INTO `service_ingredients_orders`(`service_id`, `item_id`, `amount`, `customer_id`, `service_order_id`, `worker_id`) VALUES ('{$value['service_id']}','{$value['item_id']}','{$value['amount']}', '{$customer->customer_id}','{$service_order_id}','{$shopping_cart['worker_id']}')";
                                    $this->con->query($query);
                                }

                        }
                    }elseif ($instance['type']=="recipe") {
                        $query = "SELECT * FROM recipes WHERE  recipe_id = '{$instance['instance_id']}'";
                        $recipe = $this->con->query($query)->fetch_assoc();
                        $query = "INSERT INTO `recipes_orders`(`customer_id`, `worker_id`, `recipe_id`, `amount`, `price`) VALUES ('{$customer->customer_id}','{$shopping_cart['worker_id']}','{$recipe['recipe_id']}','{$instance['amount']}','{$recipe['price']}')";
                        $result = $this->con->query($query);
                        if ($result) {
                            $recipe_order_id = $this->con->insert_id;
                            $query = "SELECT * FROM `recipe_ingredients` WHERE recipe_id = '{$recipe['recipe_id']}'";
                            $result = $this->con->query($query);
                            $recipe_ingredients = [];
                            while ($row = $result->fetch_assoc()) {
                                array_push($recipe_ingredients, $row);
                            }
                            foreach ($recipe_ingredients as $value) {
                                $query = "INSERT INTO `recipe_ingredients_orders`(`item_id`, `customer_id`, `worker_id`, `amount`, `recipe_id`, `recipe_order_id`) VALUES ('{$value['item_id']}', '{$customer->customer_id}','{$shopping_cart['worker_id']}', '{$value['amount']}' * '{$instance['amount']}', '{$value['recipe_id']}', '{$recipe_order_id}' )";
                                    $this->con->query($query);
                            }
                        }
                    }elseif ($instance['type']=="subscription") {
                            $query = "SELECT * FROM `subscriptions` WHERE subscription_id = '{$instance['instance_id']}'";
                            $subscription = $this->con->query($query)->fetch_assoc();
                            $query = "INSERT INTO `subscription_orders`(`subscription_id`, `customer_id`, `worker_id`, `price`, `amount_of_visits`, `unlimited`, `date_of_activation`, `exploration_date`) VALUES ('{$subscription['subscription_id']}', '{$customer->customer_id}', '{$shopping_cart['worker_id']}', '{$subscription['price']}', '{$subscription['amount_of_visits']}', '{$subscription['unlimited']}',CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL {$subscription['validity']} DAY))";
                            $result = $this->con->query($query);
                    } elseif ($instance['type'] == "item") {
                        $query = "SELECT * FROM items WHERE item_id = '{$instance['instance_id']}'";
                            $item = $this->con->query($query)->fetch_assoc();
                            $query = "INSERT INTO `items_orders`(`item_id`, `customer_id`, `worker_id`, `amount`, `price`, `type`) VALUES ('{$item['item_id']}','{$customer->customer_id}', '{$shopping_cart['worker_id']}', '{$instance['amount']}', '{$item['price']}', 1)";
        
                            $result = $this->con->query($query);
                    }
                }
                    $query = "UPDATE `shopping_carts` SET `status`=1 WHERE cart_id = '{$shopping_cart['cart_id']}' ";
                    $this->con->query($query);
                    $tablet->sendQR('close', 0,$shopping_cart['worker_id']);


            }

        }
    } catch (\Throwable $th) {
        return $th;
    }
    }



     function removeShoppingCart($data){
        $query = "UPDATE `shopping_carts` SET `status`=2 WHERE cart_id = '{$data['cart_id']}' ";
        $result = $this->con->query($query);
        return json_encode($result, JSON_UNESCAPED_UNICODE);    
    }

    function getShoppingCartsCustomer($customer_id){
        $query = "SELECT shopping_carts.*, workers.last_name, workers.first_name, workers.photo FROM `shopping_carts` LEFT JOIN workers ON workers.worker_id = shopping_carts.worker_id WHERE customer_id = '{$customer_id}' ORDER BY cart_id DESC";
        $result = $this->con->query($query);
        $shopping_carts = [];
        while ($row = $result->fetch_assoc()) {
            array_push($shopping_carts, $row);
        }
        foreach ($shopping_carts as $key => $shopping_cart) {
            $query = "SELECT SUM(price) summary FROM (SELECT *,
            CASE `type` 
                WHEN 'service' THEN (SELECT services.price * shopping_cart_relations.amount FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN 'recipe' THEN (SELECT recipes.price * shopping_cart_relations.amount FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN 'subscription' THEN (SELECT subscriptions.price* shopping_cart_relations.amount  FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN 'item' THEN (SELECT items.price * shopping_cart_relations.amount  FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS price
                
            FROM shopping_cart_relations  WHERE cart_id = '{$shopping_cart['cart_id']}') temp";
            $summary = $this->con->query($query)->fetch_assoc()['summary'];
            $shopping_carts[$key]['summary'] = $summary;
            
        }
        foreach ($shopping_carts as $key => $shopping_cart) {
            $query = "SELECT *,
            CASE `type` 
                WHEN \"service\" THEN (SELECT services.name FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN \"recipe\" THEN (SELECT recipes.title FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN \"subscription\" THEN (SELECT subscriptions.name FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN \"item\" THEN (SELECT items.name FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS name,
            CASE `type` 
                WHEN \"service\" THEN (SELECT services.price FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN \"recipe\" THEN (SELECT recipes.price FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN \"subscription\" THEN (SELECT subscriptions.price FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN \"item\" THEN (SELECT items.price FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS price,
            CASE `type` 
                WHEN \"service\" THEN (SELECT (services.price * shopping_cart_relations.amount) price_total FROM services WHERE services.service_id = shopping_cart_relations.instance_id)
                WHEN \"recipe\" THEN (SELECT (recipes.price * shopping_cart_relations.amount) price_total FROM recipes WHERE recipes.recipe_id = shopping_cart_relations.instance_id)
                WHEN \"subscription\" THEN (SELECT (subscriptions.price * shopping_cart_relations.amount) price_total FROM subscriptions WHERE subscriptions.subscription_id = shopping_cart_relations.instance_id)
                WHEN \"item\" THEN (SELECT (items.price * shopping_cart_relations.amount) price_total FROM items WHERE items.item_id = shopping_cart_relations.instance_id) END AS price_total
                
            FROM shopping_cart_relations  WHERE cart_id = '{$shopping_cart['cart_id']}'";
            $goods = $this->con->query($query);
            $goodsArray = [];
            while ($row = $goods->fetch_assoc()) {
                array_push($goodsArray, $row);
            }
            $shopping_carts[$key]['goods'] = $goodsArray;
            
        }
        return json_encode($shopping_carts, JSON_UNESCAPED_UNICODE);
    }
}