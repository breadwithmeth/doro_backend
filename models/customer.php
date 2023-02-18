<?php
class Customer{
    private $con;
    private $table = "";
    public $customer_id;
    public $isLoggedIn = false;
    public $token;
    public $balance;
    public $name;

    function __construct($db) {
        $this->con = $db;
        if(isset($_SERVER['HTTP_AUTH'])){
            $result = $this->checkKeyAuth(base64_decode($_SERVER['HTTP_AUTH']));
            if($result){
            	$this->customer_id = $result['customer_id'];
                $this->isLoggedIn = true;
                $this->balance = $result['balance'];
                //echo $_SERVER['HTTP_AUTH'];
            }else{
                $this->isLoggedIn = false;
            	//echo 'Ты дурак?';
            	//exit();
            	//return false;
            }
        }
    }
    public function checkKeyAuth($login){
        $query = "SELECT c.*, ((SELECT SUM(price) amount FROM customer_balance cb WHERE cb.customer_id = c.customer_id) - (
            IFNULL((SELECT SUM(io.price*io.amount) amount FROM items_orders io WHERE io.customer_id = c.customer_id),0) +
            IFNULL((SELECT SUM(ro.price*ro.amount) amount FROM recipes_orders ro WHERE ro.customer_id = c.customer_id),0)
            +IFNULL((SELECT SUM(price) amount FROM subscription_orders so WHERE so.customer_id = c.customer_id),0)
            +IFNULL((SELECT SUM(price) amount FROM services_orders seo WHERE seo.customer_id = c.customer_id),0)
        
        ))
        
         `balance`  FROM `customers` c WHERE c.login = '$login'";
        $result = $this->con->query($query);
        $result = $result->fetch_array(MYSQLI_ASSOC);



        if ($result == NULL) {
            return false;
        }else{
            //var_dump($result);
    	    return $result;
        }
    }
    function login($login =null, $password = null, $qr_uuid = ""){
        if (!empty($qr_uuid)) {
        $result = $this->con->query("SELECT * FROM `qr_tablet` WHERE `qr_uuid` = '{$qr_uuid}'");
        $result = $result->fetch_assoc();
        if ($result['type'] == 'customer_auth') {
            $query = "SELECT `service_id`, `name`, `description`, `area_id`, `provider_id`, `worker_id`, `service_timestamp`, `category_id`, `provider_fee`, `isDeleted`, `price`, `validity`, `amount_of_customers`, `visits` FROM `services` WHERE `service_id` = '{$result['instance_id']}'";
            $query = "SELECT `customer_id`, `first_name`, `middle_name`, `last_name`, `balance`, `date_of_birth`, `login`, `isDeleted`, `password` FROM `customers`  WHERE `customer_id` = '{$result['instance_id']}' AND `isDeleted` = 0";
            $result = $this->con->query($query);
            $result = $result->fetch_assoc();
            $result['qr_type'] =  'customer_auth';
            $result['key'] = base64_encode($result['login']);
            return json_encode($result, JSON_UNESCAPED_UNICODE);
        }
        }
        if (empty($password)) {
            echo false;
            header("HTTP/1.1 400 login is empty");

        }elseif (empty($login)) {
            echo false;
            header("HTTP/1.1 400 password is empty");

        // }
        // elseif (empty($area_id)) {
        //     echo false;
        //     header("HTTP/1.1 400 area_id is empty");
        }else{

            $query = "SELECT `customer_id`, `first_name`, `middle_name`, `last_name`, `balance`, `date_of_birth`, `login`, `isDeleted`, `password` FROM `customers`  WHERE `login` = '$login' AND `isDeleted` = 0";
            $result = $this->con->query($query);
            $result = $result->fetch_assoc();
            if ($result == NULL) {
                header("HTTP/1.1 403 User not found");
                return json_encode(false);
            }elseif (password_verify($password, $result['password'])) {
                if ($result['isDeleted'] == 0) {
                    $uuid = crypt(time(), "3c6b8f7676");
                    $this->customer_id = $result['customer_id'];
                    $this->balance = $result['balance'];
                    $this->isLoggedIn = true; 
                    //$this->con->query("INSERT INTO `doro2`.`areas_log` (`worker_id`, `area_id`, `log_timestamp`, `log_uuid`) VALUES ('{$this->worker_id}', '{$area_id}', CURRENT_TIMESTAMP, UUID())");
                    $result['key'] = base64_encode($result['login']);
                    return json_encode($result, JSON_UNESCAPED_UNICODE);
                    // return json_encode(['key'=>base64_encode($result['login']), 'first_name'=>$result['first_name'], 'last_name'=>$result['last_name'], 'middle_name'=>$result['middle_name'], 'photo'=>$result['photo'], 'access_level'=>$result['access_level'], 'title'=>$result['title']]);      
                }else{
                    header("HTTP/1.1 403 User is delited");
                    return json_encode(true);
                }
            }else{
                header("HTTP/1.1 403 Access denied");
                return json_encode(false);

            }
            

        }
        
    }

    function getHistoryCustomerWorker($data){
        if ($data['filter']['sort'] == "by_item") {
            $query = "SELECT CONCAT(workers.last_name, ' ', workers.first_name) worker_name, items_orders.*, items.name, (items_orders.price * items_orders.amount) summary, date_of_order order_timestamp FROM items_orders 
            LEFT JOIN items ON items.item_id = items_orders.item_id  
            LEFT JOIN workers ON workers.worker_id = items_orders.worker_id
            WHERE customer_id = '{$data['customer_id']}' AND items_orders.price > 0 ";
            if (!empty($data['filter']['item_id'])) {
                $query .= " AND items_orders.item_id = '{$data['filter']['item_id']}'";
            }
        }elseif ($data['filter']['sort'] == "by_subscription") {
            $query = "SELECT CONCAT(workers.last_name, ' ', workers.first_name) worker_name, subscription_orders.*, subscriptions.name,subscription_orders.price summary, 1 amount, order_timestamp  FROM subscription_orders 
            LEFT JOIN subscriptions ON subscriptions.subscription_id = subscription_orders.subscription_id  
            LEFT JOIN workers ON workers.worker_id = subscription_orders.worker_id
            WHERE customer_id = '{$data['customer_id']}' AND subscription_orders.price > 0  ";
            if (!empty($data['filter']['subscription_id'])) {
                $query .= " AND subscription_orders.subscription_id = '{$data['filter']['subscription_id']}'";

            }
        }elseif ($data['filter']['sort'] == "by_service") {
            $query = "SELECT CONCAT(workers.last_name, ' ', workers.first_name) worker_name, services_orders.*, services.name, services_orders.price summary, 1 amount, order_timestamp FROM services_orders 
            LEFT JOIN services ON services.service_id = services_orders.service_id  
            LEFT JOIN workers ON workers.worker_id = services_orders.worker_id
            WHERE customer_id = '{$data['customer_id']}' AND services_orders.price > 0  ";
            if (!empty($data['filter']['service_id'])) {
                $query .= " AND services_orders.service_id = '{$data['filter']['service_id']}'";

            }
        }elseif ($data['filter']['sort'] == "by_recipe") {
            $query = "SELECT CONCAT(workers.last_name, ' ', workers.first_name) worker_name, recipes_orders.*, recipes.title `name`, (recipes_orders.price * recipes_orders.amount) summary, order_timestamp  FROM recipes_orders 
            LEFT JOIN recipes ON recipes.recipe_id = recipes_orders.recipe_id  
            LEFT JOIN workers ON workers.worker_id = recipes_orders.worker_id
            WHERE customer_id = '{$data['customer_id']}' AND recipes_orders.price > 0  ";
            if (!empty($data['filter']['recipe_id'])) {
                $query .= " AND recipes_orders.recipe_id = '{$data['filter']['recipe_id']}'";

            }
        }elseif ($data['filter']['sort'] == "by_balance") {
            $query = "SELECT CONCAT(workers.last_name, ' ', workers.first_name) worker_name, customer_balance.*,(customer_balance.price * 1) summary, log_timestamp order_timestamp, customer_balance.balance_id order_id  FROM customer_balance 
            LEFT JOIN workers ON workers.worker_id = customer_balance.worker_id
            WHERE customer_id = '{$data['customer_id']}' AND customer_balance.price > 0  ";
            
        }
        $query .= " HAVING DATE(order_timestamp) <= '{$data['dateEnd']}' AND DATE(order_timestamp) >= '{$data['dateStart']}'";
        if  (!empty($data['filter']['keyword'])) {
            if ($data['filter']['sort'] != "by_balance") 
         {
            $query .= " AND name LIKE '{$data['filter']['keyword']}%'";
                
            }
         }
        if (!empty($data['filter']['worker_id'])) {
            $query .= " AND worker_id = 1";
        }
        // $query .= " LIMIT 10 OFFSET 10";
        $query .= " ORDER BY order_id DESC";
        $result = $this->con->query($query);
        $resultArr = [];
        $summary = 0;
            while ($row=$result->fetch_assoc()) {
            $summary = $summary + $row['summary'];
                array_push($resultArr, $row);
            }
        $resultArr2 = [];
        $resultArr2['history'] = $resultArr;
        $resultArr2['summary'] = $summary;

        return json_encode($resultArr2, JSON_UNESCAPED_UNICODE);

    }

    function getSessionsCustomer($data){
        $query = "SELECT service_sessions.*,services.name,service_schedule.provider_id, CONCAT(workers.last_name,' ',workers.first_name) worker_name, services.name service_name,services.is_personal,areas.title area_name,service_categories.name category
        FROM service_sessions
        LEFT JOIN service_schedule ON service_sessions.schedule_id = service_schedule.schedule_id
        LEFT JOIN services ON service_schedule.service_id = services.service_id
        LEFT JOIN workers ON service_schedule.provider_id = workers.worker_id
        LEFT JOIN areas ON service_schedule.area_id = areas.area_id
        LEFT JOIN service_categories ON service_categories.category_id = services.category_id
        WHERE customer_id = '{$data['customer_id']}'";
         $result = $this->con->query($query);
         $resultArr = [];
             while ($row=$result->fetch_assoc()) {
                 array_push($resultArr, $row);
             }
             return json_encode($resultArr, JSON_UNESCAPED_UNICODE);

    }


    function setID($customer_id){
        $query = "SELECT c.*, ((SELECT SUM(price) amount FROM customer_balance cb WHERE cb.customer_id = c.customer_id) - (
            IFNULL((SELECT SUM(io.price*io.amount) amount FROM items_orders io WHERE io.customer_id = c.customer_id),0) +
            IFNULL((SELECT SUM(ro.price*ro.amount) amount FROM recipes_orders ro WHERE ro.customer_id = c.customer_id),0)
            +IFNULL((SELECT SUM(price) amount FROM subscription_orders so WHERE so.customer_id = c.customer_id),0)
            +IFNULL((SELECT SUM(price) amount FROM services_orders seo WHERE seo.customer_id = c.customer_id),0)
        
        ))
        
         `balance`  FROM `customers` c WHERE c.customer_id = '$customer_id'";
        $result = $this->con->query($query);
        $result = $result->fetch_array(MYSQLI_ASSOC);



        if ($result == NULL) {
            return false;
        }else{
            $this->customer_id = $result['customer_id'];
                $this->isLoggedIn = true;
                $this->balance = $result['balance'];
            $this->name = $result['last_name'] . " " . $result['first_name'];
    	    return true;
        }
    }

    function addCustomer($data, $worker_id, $tablet){
        if (isset($data['first_name']) && isset($data['middle_name']) && isset($data['last_name']) && isset($data['date_of_birth']) && isset($data['login']) && isset($data['password'])) {
            $token = "123";
            $password = password_hash($data['password'], PASSWORD_DEFAULT);
            $query = "INSERT INTO `customers`(`first_name`, `middle_name`, `last_name`, `balance`, `date_of_birth`, `login`, `password`, `token`, `worker_id`) VALUES ('{$data['first_name']}','{$data['middle_name']}','{$data['last_name']}','{$data['balance']}','{$data['date_of_birth']}','{$data['login']}', '{$password}','{$token}',{$worker_id})";
            $result = $this->con->query($query);
            $last_id = $this->con->insert_id;
            if ($result) {
                $last_id = $this->con->insert_id;
                $uuid = time();
                $query = "INSERT INTO `qr_tablet`(`type`, `instance_id`, `device_id`, `qr_uuid`, `worker_id`) VALUES ('customer_auth', '{$last_id}', 1, '{$uuid}', {$worker_id})";
                $result = $this->con->query($query);
                $tablet->sendQR('customer_auth', $uuid, $worker_id);
            }
            return json_encode($result);
        }else{
            header("HTTP/1.1 400 Some fields are empty");   
            return json_encode(false);
        }
    }

    function getCustomer($data, $customer_id = null){
        if ($customer_id == null) {
            $customer_id = $data['customer_id'];
            $query = "SELECT `c`.`customer_id`, `c`.`first_name`, `c`.`middle_name`, `c`.`last_name`,
            
            ((SELECT SUM(price) amount FROM customer_balance cb WHERE cb.customer_id = c.customer_id) - (
                IFNULL((SELECT SUM(io.price*io.amount) amount FROM items_orders io WHERE io.customer_id = c.customer_id),0) +
                IFNULL((SELECT SUM(ro.price*ro.amount) amount FROM recipes_orders ro WHERE ro.customer_id = c.customer_id),0)
                +IFNULL((SELECT SUM(price) amount FROM subscription_orders so WHERE so.customer_id = c.customer_id),0)
                +IFNULL((SELECT SUM(price) amount FROM services_orders seo WHERE seo.customer_id = c.customer_id),0)
            
            ))
            
             `balance`,    `c`.`date_of_birth`, `c`.`login`, `c`.`date_of_registration`, `c`.`photo`,  IFNULL((SELECT true FROM customer_freezings WHERE customer_freezings.customer_id = '{$customer_id}' AND CURRENT_DATE between date_start AND date_end LIMIT 1), 0) is_freezed,
            CASE `c`.`gender`
            WHEN '1' THEN 'мужчина'
            WHEN '0' THEN 'женщина'
            END as `gender` FROM `customers` `c` WHERE `c`.`customer_id` = '{$data['customer_id']}' AND `c`.`isDeleted` = 0";
        }else{
            $query = "SELECT `c`.`customer_id`, `c`.`first_name`, `c`.`middle_name`, `c`.`last_name`,
             ((SELECT SUM(price) amount FROM customer_balance cb WHERE cb.customer_id = c.customer_id) - (
                IFNULL((SELECT SUM(io.price*io.amount) amount FROM items_orders io WHERE io.customer_id = c.customer_id),0) +
                IFNULL((SELECT SUM(ro.price*ro.amount) amount FROM recipes_orders ro WHERE ro.customer_id = c.customer_id),0)
                +IFNULL((SELECT SUM(price) amount FROM subscription_orders so WHERE so.customer_id = c.customer_id),0)
                +IFNULL((SELECT SUM(price) amount FROM services_orders seo WHERE seo.customer_id = c.customer_id),0)
            
            ))
            
             `balance`,  `c`.`date_of_birth`, `c`.`login`, `c`.`date_of_registration`, `c`.`photo`,
            CASE `c`.`gender`
            WHEN '1' THEN 'мужчина'
            WHEN '0' THEN 'женщина'
            END as `gender` FROM `customers` `c` WHERE `c`.`customer_id` = '{$customer_id}' AND `c`.`isDeleted` = 0";
        }
        // return $query;
        $result = $this->con->query($query)->fetch_assoc();
        
        $result['stat'] = $this->getCustomerStat($customer_id);
        $result['sessions'] = $this->getSessionByCustomer($customer_id);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
    function getCustomers($data){
        
        $query = "SELECT `customer_id`, CONCAT( `last_name`, ' ', `first_name`, ' ', `middle_name`) as `name`, `date_of_birth`, `login`, `date_of_registration`, CASE `gender`
        WHEN '1' THEN 'мужчина'
        WHEN '0' THEN 'женщина'
        END as `gender`,
             ((SELECT SUM(price) amount FROM customer_balance cb WHERE cb.customer_id = customers.customer_id) - (
                IFNULL((SELECT SUM(io.price*io.amount) amount FROM items_orders io WHERE io.customer_id = customers.customer_id),0) +
                IFNULL((SELECT SUM(ro.price*ro.amount) amount FROM recipes_orders ro WHERE ro.customer_id = customers.customer_id),0)
                +IFNULL((SELECT SUM(price) amount FROM subscription_orders so WHERE so.customer_id = customers.customer_id),0)
                +IFNULL((SELECT SUM(price) amount FROM services_orders seo WHERE seo.customer_id = customers.customer_id),0)
            
            ))
            
             `balance`, IFNULL((SELECT true FROM customer_freezings WHERE customer_freezings.customer_id = customers.customer_id AND CURRENT_DATE between date_start AND date_end LIMIT 1), 0) is_freezed  FROM `customers` WHERE  `isDeleted` = 0";

        if (!empty($data['filter']['sort']['service_id'])) {
            $query .= " AND customer_id in (SELECT subscription_orders.customer_id FROM subscription_services
            LEFT JOIN subscription_orders ON subscription_orders.subscription_id = subscription_services.subscription_id
            WHERE subscription_orders.customer_id = customers.customer_id AND subscription_services.service_id = '{$data['filter']['sort']['service_id']}'
            UNION ALL
            SELECT services_orders.customer_id FROM services_orders LEFT JOIN services ON services.service_id = services_orders.service_id WHERE services_orders.customer_id = customers.customer_id  AND services_orders.is_visited = 0 AND services_orders.service_id = '{$data['filter']['sort']['service_id']}'
            )
            
            ";
        }
        if (!empty($data['filter']['birthday'])) {
            $query .= " AND date_of_birth = CURRENT_DATE";
        }
        if (!empty($data['filter']['keyword'])) {
            $query .= " HAVING `name` LIKE \"{$data['filter']['keyword']}%\" OR `login` LIKE \"{$data['filter']['keyword']}%\"";
        }
        

        $result = $this->con->query($query);
        $resultArr = [];
        while ($row=$result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        if (!empty($data['filter']['sort']['service_id'])) {
            $tempReasons = [];
            foreach ($resultArr as $key => $customer) {
                $query = "SELECT subscription_orders.order_id, subscriptions.name, 'sub' type FROM subscription_orders
                LEFT JOIN subscription_services ON subscription_orders.subscription_id = subscription_services.subscription_id
                LEFT JOIN subscriptions ON subscription_orders.subscription_id = subscriptions.subscription_id
                WHERE subscription_orders.customer_id = '{$customer['customer_id']}' AND subscription_services.service_id = '{$data['filter']['sort']['service_id']}' GROUP BY subscription_orders.order_id
                UNION ALL
                SELECT services_orders.order_id, services.name, 'service' type FROM services_orders LEFT JOIN services ON services.service_id = services_orders.service_id WHERE services_orders.customer_id =  '{$customer['customer_id']}'  AND services_orders.is_visited = 0 AND services_orders.service_id = '{$data['filter']['sort']['service_id']}'
                ";
                
                $result = $this->con->query($query);
                $tempArr = [];
                while ($row=$result->fetch_assoc()) {
                    array_push($tempArr, $row);
                }
                $resultArr[$key]['reasons'] = $tempArr;
            }


        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function editCustomer($data){
        // if (!empty($data['password'])) {
        //     $password = password_hash($data['password'], PASSWORD_DEFAULT);
        //     $query = "UPDATE `customers` SET `first_name`='{$data['first_name']}',`middle_name`='{$data['middle_name']}',`last_name`='{$data['last_name']}',`balance`='{$data['balance']}',`date_of_birth`='{$data['date_of_birth']}',`login`='{$data['login']}',`password`='{$password}', `gender`='{$data['gender']}'  WHERE `customer_id` = '{$data['customer_id']}' AND `isDeleted` = 0";
        //     $result = $this->con->query($query);
        //     return json_encode($result);
        // }else{
        //     $query = "UPDATE `customers` SET `first_name`='{$data['first_name']}',`middle_name`='{$data['middle_name']}',`last_name`='{$data['last_name']}',`balance`='{$data['balance']}',`date_of_birth`='{$data['date_of_birth']}',`login`='{$data['login']}', `gender`='{$data['gender']}'  WHERE `customer_id` = '{$data['customer_id']}'";
        //     $result = $this->con->query($query);
        //     return json_encode($result);
        // }
        $query = "UPDATE `customers` SET ";
            foreach ($data as $key => $value) {
                if ($key == "password") {
                    if ($value) {
                        $hashed_password = password_hash($value, PASSWORD_DEFAULT);
                        $query .= "`{$key}` = \"{$hashed_password}\",";
                    }
                }elseif($key != "token") {
                    $query .= "`{$key}` = \"{$value}\",";
                }
            }
            $query = substr($query, 0, -1);
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
                    $query .= ", `photo` = 'https://new.doro.kz/media/{$new_file_name}'";
                    
                }else{
                    header("HTTP/1.1 413 file is too big!");
                    return json_encode(false);
                }
            }
            $query .= " WHERE `customer_id` = '{$data['customer_id']}'";
            // return $query;
            $result = $this->con->query($query);
            if ($result) {
                return json_encode($result);
            }else{
                header("HTTP/1.1 400 check it");   
                return json_encode(false);
            
            }
    }
    function addReview($data, $worker_id){
        $query = "INSERT INTO `customer_reviews`(`worker_id`, `customer_id`, `comment`, `rating`) VALUES ('{$worker_id}', '{$data['customer_id']}', '{$data['comment']}', '{$data['rating']}')";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function getReviews($data){
        $query = "SELECT `cr`.*, `w`.`login` `worker_login` FROM `customer_reviews` `cr` LEFT JOIN `workers` `w` ON `w`.`worker_id` = `cr`.`worker_id` WHERE `cr`.`customer_id` = '{$data['customer_id']}'";
        $result = $this->con->query($query);
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            array_push($reviews, $row);
        }
        $result = $this->con->query("SELECT AVG(`rating`) `rating` FROM `customer_reviews` WHERE `customer_id` = '{$data['customer_id']}'");
        $rating = $result->fetch_assoc();
        $resultArr = [];
        $resultArr['reviews'] = $reviews;
        $resultArr['rating'] = $rating['rating'];
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function addPhotoCustomer(){
        
    }

    function getStat(){
        $resultArr = [];
        $query = "SELECT (SELECT COUNT(*) FROM customers WHERE gender = 0) male, (SELECT COUNT(*) FROM customers WHERE gender = 1) female";
        $result = $this->con->query($query)->fetch_assoc();
        $resultArr['by_age'] = $result;
        
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function addBalance($data, $worker_id){
        $query = "INSERT INTO `customer_balance`(`customer_id`, `worker_id`, `price`,type_of_payment) VALUES ('{$data['customer_id']}','{$worker_id}' ,'{$data['amount']}',{$data['type_of_payment']})";
        $result = $this->con->query($query);
        return json_encode($result);
    
    }

    function getCustomerStat($customer_id){
        $resultArr = [];
        $tempArr = [];
        $query = "SELECT COUNT(*) amount, items.* FROM items_orders LEFT JOIN items ON items.item_id = items_orders.item_id WHERE customer_id = '{$customer_id}' GROUP BY item_id ORDER BY COUNT(*) DESC";
        $result = $this->con->query($query);
        while ($row = $result->fetch_assoc()) {
            array_push($tempArr, $row);
        }
        
        $resultArr['by_items'] = $tempArr;
        $tempArr = [];

        $query = "SELECT COUNT(*) amount, services.* FROM services_orders LEFT JOIN services ON services.service_id = services_orders.service_id WHERE customer_id = '{$customer_id}' GROUP BY service_id ORDER BY COUNT(*) DESC";
        $result = $this->con->query($query);
        while ($row = $result->fetch_assoc()) {
            array_push($tempArr, $row);
        }
        $resultArr['by_services'] = $tempArr;
        $tempArr = [];

        $query = "SELECT COUNT(*) amount, subscriptions.* FROM subscription_orders LEFT JOIN subscriptions ON subscriptions.subscription_id = subscription_orders.subscription_id WHERE customer_id = '{$customer_id}' GROUP BY subscription_id ORDER BY COUNT(*) DESC";
        $result = $this->con->query($query);
        while ($row = $result->fetch_assoc()) {
            array_push($tempArr, $row);
        }
        $resultArr['by_subscription'] = $tempArr;

        return $resultArr;
    }

    function getSessionByCustomer($customer_id){
        $query = "SELECT COUNT(*) amount, service_schedule.service_id, services.name FROM service_sessions 
        LEFT JOIN service_schedule ON service_schedule.schedule_id = service_sessions.schedule_id 
        LEFT JOIN services ON services.service_id = service_schedule.service_id
        WHERE service_sessions.customer_id = '{$customer_id}' GROUP BY service_schedule.service_id ORDER BY COUNT(*) DESC";
        $tempArr = [];
        $result = $this->con->query($query);

        while ($row = $result->fetch_assoc()) {
            array_push($tempArr, $row);
        }
        return $tempArr;
    }

    function getSessions($data){
        $query = "SELECT service_sessions.customer_id, service_schedule.schedule_date, service_schedule.time_start, services.name, service_schedule.provider_id, CONCAT(workers.last_name, ' ', workers.first_name) provider_name, service_sessions.status,
        CASE service_sessions.reason_type
            WHEN 'sub' THEN (SELECT subscriptions.name FROM subscription_orders LEFT JOIN subscriptions ON subscriptions.subscription_id WHERE subscription_orders.order_id = service_sessions.reason_order_id LIMIT 1)  
            WHEN 'service' THEN (SELECT services.name FROM services_orders LEFT JOIN services ON services.service_id = services_orders.service_id WHERE services_orders.order_id = service_sessions.reason_order_id LIMIT 1)
            END as reason
        FROM service_sessions 
        LEFT JOIN service_schedule ON service_schedule.schedule_id = service_sessions.schedule_id 
        LEFT JOIN services ON service_schedule.service_id = services.service_id
        LEFT JOIN workers ON workers.worker_id = service_schedule.provider_id
        WHERE service_sessions.customer_id = '{$data['customer_id']}'
        ";
        $tempArr = [];
        $result = $this->con->query($query);

        while ($row = $result->fetch_assoc()) {
            array_push($tempArr, $row);
        }
        return json_encode($tempArr, JSON_UNESCAPED_UNICODE);
    }

    function freezeCustomer($data, $worker_id){
        $query = "INSERT INTO `customer_freezings`(`customer_id`, `date_start`, `date_end`, `worker_id`) VALUES ('{$data['customer_id']}', '{$data['date_start']}', '{$data['date_end']}','{$worker_id}')";
        $result = $this->con->query($query);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    function unFreezeCustomer($data){

    
    }

    function getFreezingsCustomer($data){
        $query = "SELECT * FROM customer_freezings WHERE customer_id = '{$data['customer_id']}'";
        $tempArr = [];
        $result = $this->con->query($query);

        while ($row = $result->fetch_assoc()) {
            array_push($tempArr, $row);
        }
        return json_encode($tempArr, JSON_UNESCAPED_UNICODE);
    }
}

