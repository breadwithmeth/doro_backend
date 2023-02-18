<?php
class Item{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    function getItems($data, $worker){
        //complete match
        //keyword
        $isWhereSet = false;
        if (isset($data['minimal']) && $data['minimal'] == true) {
            $query = "SELECT *  FROM 
            (SELECT i.item_id `id`, i.name `name`, i.in_stock `stock`,  c.title `category`, `i`.`category_id` `category_id`
            FROM items i 
            LEFT JOIN product_categories c ON i.category_id = c.category_id
            LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
             WHERE i.isDeleted = 0) items_temp ";
            if (isset($data['filter']['categories'])) {
                $categoryStr = implode(",", $data['filter']['categories']);
                if ($isHavingSet) {
                    $query .= " AND `category_id` in({$categoryStr})";
                }else{
                    $query .= " HAVING `category_id` in({$categoryStr})";
    
                }
            }
            $result = $this->con->query($query); 
            $resultArr = [];
            while ($row = $result->fetch_assoc()) {
                array_push($resultArr, $row);
            }
            return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
        }else{
            $query = "SELECT *  FROM 
            (SELECT 
            (
                (
                IFNULL
                    ((SELECT SUM(`s`.amount) amount  FROM `supplies` `s` WHERE `i`.`item_id` = `s`.`item_id` GROUP BY `s`.`item_id`), 0) - 

                (
                    (IFNULL
                        ((SELECT SUM(`io`.amount) amount FROM items_orders `io` WHERE `i`.`item_id` = `io`.`item_id` GROUP BY `io`.`item_id`), 0)) +
                    (IFNULL
                        ((SELECT SUM(`rio`.amount) amount FROM recipe_ingredients_orders `rio` WHERE `i`.`item_id` = `rio`.`item_id` GROUP BY `rio`.`item_id`), 0))
                )
                 
                )
            ) stock, 
            i.item_id `id`, i.name `name`, c.title `category`, `i`.`category_id` `category_id`, i.price `price`, `i`.`vendor_code`, `i`.`metric_unit_id`, `mu`.`name` `metric_unit`
            FROM items `i` 
            LEFT JOIN product_categories c ON i.category_id = c.category_id
            LEFT JOIN metric_units `mu` ON `mu`.`metric_unit_id` = `i`.`metric_unit_id`
            WHERE `i`.`isDeleted` = 0
            ) items_temp ";
        
        }
        if ($worker->is_personal) {
            $query .= " WHERE category_id in(SELECT category_id FROM workers_categories_relations WHERE position_id = '{$worker->position_id}')";
            if (isset($data['filter'])) {
                if (!empty($data['filter']['keyword'])) {
                    $isWhereSet = true;
                    if ($data['filter']['complete_match'] == true) {
                        $query .= " AND (name = '{$data['filter']['keyword']}' OR `vendor_code` = {$data['filter']['keyword']})";
                    }else{
                        // $query .= "WHERE i.name LIKE \"{$data['filter']['keyword']}%\"";
                        $temp = "'{$data['filter']['keyword']}%'";
                        //echo $temp;
                        $query .= " AND (name LIKE $temp OR `vendor_code` LIKE $temp)";
    
                    }
                } 
            }
        }else {
            
            
            if (isset($data['filter'])) {
                if (!empty($data['filter']['keyword'])) {
                    $isWhereSet = true;
                    if ($data['filter']['complete_match'] == true) {
                        $query .= "WHERE name = '{$data['filter']['keyword']}' OR `vendor_code` = {$data['filter']['keyword']}";
                    }else{
                        // $query .= "WHERE i.name LIKE \"{$data['filter']['keyword']}%\"";
                        $temp = "'{$data['filter']['keyword']}%'";
                        //echo $temp;
                        $query .= "WHERE name LIKE $temp OR `vendor_code` LIKE $temp";
    
                    }
                } 
            }
        }
        $isHavingSet = false;
        if(isset($data['filter']['in_stock'])){
            // if (!$isWhereSet) {
            //     $query .= " WHERE ";
            // }else {
            //     $query .= " AND ";
            // }
            $query .= " HAVING ";
            $isHavingSet = true;
            if($data['filter']['in_stock'] == true){
                $query .= " stock > 0";
            }elseif (($data['filter']['in_stock'] == false)) {
                $query .= " stock = 0";
            }
        }
        if (isset($data['filter']['categories'])) {
            $categoryStr = implode(",", $data['filter']['categories']);
            if ($isHavingSet) {
                $query .= " AND `category_id` in({$categoryStr})";
            }else{
                $query .= " HAVING `category_id` in({$categoryStr})";

            }
        }
        $result = $this->con->query($query); 
        // $result = $this->con->query("SELECT * FROM `items` WHERE `name` LIKE '$data[]%' ORDER BY `name` DESC ");
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function getItem($data){
        if (isset($data['id'])) {
            $query = "SELECT `i`.`item_id`, `i`.`vendor_code`, `i`.`name`, `i`.`category_id`, `c`.`title` `category_title`, `i`.`supplier_id`, `s`.`name` `supplier_name`, `i`.`in_stock`, `i`.`price` FROM `items` `i` LEFT JOIN `product_categories` `c` ON `i`.`category_id` = `c`.`category_id` LEFT JOIN `suppliers` `s` ON `s`.`supplier_id` = `i`.`supplier_id` WHERE `i`.`item_id` = {$data['id']}";
            $result = $this->con->query($query);
            $resultArr = $result->fetch_assoc();
            return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
        }
    }

    function addItem($data, $worker_id){
        if ($this->checkIfVendorCodeExist($data['vendor_code'])) {       
            if (isset($data['name']) && isset($data['category_id']) && isset($data['price']) && isset($data['in_stock']) && isset($data['supplier_id']) && !empty($data['name']) && !empty($data['category_id']) && !empty($data['price'])) {
                // $query = "INSERT INTO `items`(`name`, `category_id`, `price`, `in_stock`, `supplier_id`,`vendor_code`, `worker_id`) VALUES ('{$data['name']}','{$data['category_id']}','{$data['price']}', '{$data['in_stock']}', '{$data['supplier_id']}', {$data['vendor_code']}, {$worker_id})";
                $query = "INSERT INTO `items`(`name`, `category_id`, `price`, `vendor_code`, `worker_id`, `metric_unit_id`) VALUES ('{$data['name']}','{$data['category_id']}','{$data['price']}', {$data['vendor_code']}, {$worker_id}, '{$data['metric_unit_id']}')";
                $result = $this->con->query($query);
                if ($result) {
                    $item_id =  $this->con->insert_id;
                    $query = "INSERT INTO `supplies`(`supplier_id`, `item_id`, `worker_id`, `price_of_purchase`, `amount`) VALUES ('{$data['supplier_id']}', '{$item_id}', '{$worker_id}', '{$data['price_of_purchase']}', '{$data['in_stock']}' )";
                    $result = $this->con->query($query);
                    if ($result) {
                        header("HTTP/1.1 201 Created");
                        return json_encode($result);
                        
                    }else {
                        header("HTTP/1.1 501 failed to add to suplies table");
                        return json_encode($result);
                    }
                }else {
                    header("HTTP/1.1 501 failed to add to suplies and items table");
                    return json_encode($result);
                }
            }else{
                header("HTTP/1.1 400 Bad request");
                return json_encode(false);
            }
        }else{
            header("HTTP/1.1 409 vendor_code already exists");
            return json_encode(false);
        }

    }

    function editItem($data, $worker_id){
        // if (isset($data['name']) && isset($data['category_id']) && isset($data['price']) && isset($data['in_stock']) && isset($data['supplier_id'])  && !empty($data['name']) && !empty($data['category_id']) && !empty($data['price']) && !empty($data['item_id'])) {
            $query = "UPDATE `items` SET `name`='{$data['name']}',`category_id`='{$data['category_id']}',`price`='{$data['price']}', `metric_unit_id` = '{$data['metric_unit_id']}', `vendor_code` = '{$data['vendor_code']}' WHERE `item_id` = {$data['item_id']}";
            //echo $query;
            $result = $this->con->query($query);
            $this->addLog($worker_id, $data['item_id'], 2);
            header("HTTP/1.1 202 Accepted");
            return json_encode($result);
        // }else{
        //     header("HTTP/1.1 400 Bad request");
        //     return json_encode(false);
        // }
        $result = $this->con->query($query);
        return json_encode($result);
    }


    function removeItem($data, $worker_id){
        if (isset($data['item_id'])) {
            $query = "SELECT `item_id` FROM `items` WHERE `item_id` = {$data['item_id']}";
            $result = $this->con->query($query);
            if ($result->num_rows>0) {
                $query = "UPDATE `items` SET `isDeleted` = 1 WHERE `item_id` = {$data['item_id']}";
                $result = $this->con->query($query);
                $this->addLog($worker_id, $data['item_id'], 3);
                return json_encode($result);
            }else{
                header("HTTP/1.1 404 Not found");
                return json_encode(false);

            }
        }else {
            header("HTTP/1.1 400 Bad request");
            return json_encode(false);

        }
    }



    function addLog($worker_id, $item_id, $action){
        $query = "INSERT INTO `items_log`(`worker_id`, `item_id`, `action`) VALUES ($worker_id, $item_id, $action)";
        $this->con->query($query);
    }



    function getItemLog(){
        $query = "SELECT `all`.`worker_id`, `all`.`action`, `all`.`item_id`,`i`.`vendor_code`, `i`.`name`, `w`.`login`, `w`.`first_name`, `w`.`last_name`, DATE_FORMAT(`all`.`log_timestamp`, '%d.%m.%Y %H:%i:%S') `log_timestamp` FROM (
            SELECT `worker_id`, `item_id`, 'Изменено' `action`, `log_timestamp` FROM `items_log` WHERE `action` = 2
            UNION ALL
            SELECT `worker_id`, `item_id`, 'Удалено' `action`, `log_timestamp` FROM `items_log` WHERE `action` = 3
            UNION ALL
            SELECT `worker_id`, `item_id`, 'Создано' `action`,`item_timestamp` `log_timestamp` FROM `items`
            ORDER BY `log_timestamp`  DESC
        ) `all` LEFT JOIN `items` `i` ON `all`.`item_id` = `i`.`item_id` LEFT JOIN `workers` `w` ON `all`.`worker_id` = `w`.`worker_id`";    
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row=$result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);

    }

    function getWithdrawal($data){
        $query = "
        SELECT orders.*, CONCAT(customers.last_name, \" \", customers.first_name) customer_name, CONCAT(workers.last_name, \" \", workers.first_name) worker_name FROM (SELECT items_orders.item_id item_id, items_orders.worker_id worker_id, items_orders.customer_id customer_id, items_orders.price price, items_orders.amount amount, items_orders.date_of_order date_of_order, \"item\" type_of_order, order_id, note FROM items_orders
        UNION ALL
        SELECT recipe_ingredients_orders.item_id item_id, recipe_ingredients_orders.worker_id worker_id, recipe_ingredients_orders.customer_id customer_id, \"0\" price, recipe_ingredients_orders.amount amount, recipe_ingredients_orders.date_of_order date_of_order, \"recipe\" type_of_order, order_id, CONCAT(\"Покупка рецепта #\",order_id) note FROM recipe_ingredients_orders
        UNION ALL
        SELECT service_ingredients_orders.item_id item_id, service_ingredients_orders.worker_id worker_id, service_ingredients_orders.customer_id customer_id, \"0\" price, service_ingredients_orders.amount amount, service_ingredients_orders.date_of_order date_of_order, \"recipe\" type_of_order, order_id, CONCAT(\"Покупка услуги #\",order_id) note FROM service_ingredients_orders) orders
        LEFT JOIN customers ON customers.customer_id = orders.customer_id
        LEFT JOIN workers ON workers.worker_id = orders.worker_id
        WHERE item_id = 1
        ORDER BY date_of_order DESC
        ";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function sellItem($data, $worker_id, $customer){
        $customer->setID($data['customer_id']);
        $balance = $customer->balance;
        if($balance > $data['price']){
            $query = "INSERT INTO `items_orders`(`item_id`, `customer_id`, `worker_id`, `amount`, `price`, `type`) VALUES ('{$data['item_id']}', '{$data['customer_id']}', '{$worker_id}', '{$data['amount']}', '{$data['price']}', 1)";
        
            $result = $this->con->query($query);
            return json_encode($result);
        }else {
            return json_encode(false);
        }
    }

    function withdrawItem($data, $worker_id){
        if (empty($data['customer_id'])) {
            $data[' '] = 0;
        }
        $query = "INSERT INTO `items_orders`(`item_id`, `customer_id`, `worker_id`, `amount`, `price`, `type`, `note`) VALUES ('{$data['item_id']}', 0, '{$worker_id}', '{$data['amount']}', '{$data['price']}', '{$data['type']}', '{$data['note']}')";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    

    function addSupplies($data){
    
    }









    //Возвращает False если продукт с таким артиклем уже существует
    function checkIfVendorCodeExist($vendor_code){
        $query = "SELECT `item_id` FROM `items` WHERE `vendor_code` = '$vendor_code'";
        $result = $this->con->query($query);
        if ($result->num_rows === 0) {
            return true;
        }else{
            return false;
        }
    }


}