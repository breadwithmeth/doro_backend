<?php
class Service{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    function addService($data, $worker){
        $query = "INSERT INTO `services`(`name`, `description`,`worker_id`, `category_id`, `is_paid`, `price`, `is_personal`, `organization_id`, `global_area_id`, `color`) VALUES ('{$data['name']}', '{$data['description']}', '{$worker->worker_id}','{$data['category_id']}', '{$data['is_paid']}', '{$data['price']}', '{$data['is_personal']}', '{$worker->organization_id}', '{$data['global_area_id']}', '{$data['color']}')";
        $result = $this->con->query($query);
        if($result && !empty($data['ingredients'])){
            $service_id = $this->con->insert_id;
            foreach ($data['ingredients'] as $ingredient) {
                $query = "INSERT INTO `service_ingredients`(`service_id`, `item_id`, `amount`) VALUES ('{$service_id}', '{$ingredient['item_id']}', '{$ingredient['amount']}')";
                $resultI = $this->con->query($query);
                
            }
            foreach ($data['subscriptions'] as $value) {
                $this->con->query("INSERT INTO `subscription_services`(`subscription_id`, `service_id`) VALUES ('{$value}', '{$service_id}')");
            }
        }
        return json_encode($result);
    }

    function removeService($data){
        $query = "UPDATE `services` SET `isDeleted`=1 WHERE service_id = '{$data['service_id']}'";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function getServices($data, $worker){
        $query = "SELECT services.service_id, services.name, services.price, services.description, services.global_area_id, service_categories.is_personal is_personal FROM `services` 
        LEFT JOIN service_categories ON service_categories.category_id = services.category_id
        WHERE organization_id = '{$worker->organization_id}' AND isDeleted = 0 ";
        if (isset($data['filter'])) {
            if (isset($data['filter']['by_category'])) {
                // $query .= " AND category_id = '{$data['filter']['by_category']}'";
                $categories = implode(", ", $data['filter']['by_category']);
                $query .= " AND services.category_id in ($categories)";
            }
            if (isset($data['filter']['by_organization'])) {
                // $query .= " AND category_id = '{$data['filter']['by_category']}'";
                $organizations = implode(", ", $data['filter']['by_organization']);
                $query .= " AND services.organization_id in ($organizations)";
            }
            if (isset($data['filter']['keyword'])) {
                // $query .= " AND category_id = '{$data['filter']['by_category']}'";
                $organizations = implode(", ", $data['filter']['by_organization']);
                $query .= " AND services.name LIKE '{$data['filter']['keyword']}%'";
            }
        }
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row=$result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function sellService($data, $worker_id, $tablet, $customer){
        if (isset($data['qr']) && $data['qr'] == true) {
            if ($data['qr'] = true) {
                $uuid = time();
                $query = "INSERT INTO `qr_tablet`(`type`, `instance_id`, `device_id`, `qr_uuid`, `worker_id`) VALUES ('sell_service', '{$data['service_id']}', 1, '{$uuid}', {$worker_id})";
                $result = $this->con->query($query);
                $tablet->sendQR('sell_service', $uuid, $worker_id);
                return($result);
            }
        }else{
            $customer->setID($data['customer_id']);
            echo $customer->balance;

        }

    
    }

    function buyService($data, $customer_id, $balance){
        $query = "SELECT `service_id`, `name`, `description`, `area_id`, `provider_id`, `worker_id`, `service_timestamp`, `category_id`, `provider_fee`, `isDeleted`, `price`, `validity`, `amount_of_customers`, `visits` FROM `services` WHERE service_id = '{$data['service_id']}'";
        $service = $this->con->query($query)->fetch_assoc();
        // return json_encode($service);
        if ($service['price'] <= $balance) {
                $this->con->autocommit(FALSE);
                $this->con->query("INSERT INTO `services_orders`(`service_id`, `customer_id`, `exploration_date`, `visits`, `visits_left`) VALUES ('{$data['service_id']}','{$customer_id}',DATE_ADD(current_timestamp(), INTERVAL {$service['validity']} DAY),'{$service['visits']}', '{$service['visits']}');");
                $this->con->query("UPDATE `customers` SET `balance` = `balance` - '{$service['price']}' WHERE `customer_id` = '{$customer_id}'");
                if (!$this->con -> commit()) {
                    header("HTTP/1.1 501 i dunno whats going on");
                    return false;
                  }else{
                    return true;
                }
            //    $query = "
            //    START TRANSACTION;
            //    INSERT INTO `services_orders`(`service_id`, `customer_id`, `exploration_date`, `visits`) VALUES ('{$data['service_id']}','{$customer_id}',DATE_ADD(CURRENT_DATE, INTERVAL {$service['validity']} DAY),'{$service['visits']}');
            //    UPDATE `customers` SET `balance` = `balance` - '{$service['price']}' WHERE `customer_id` = '{$customer_id}';
            //    COMMIT;";
            //    return $query;
            //    $result = $this->con->query($query);
            //    return json_encode($result);
        }else{
            header("HTTP/1.1 402 low balnce");
            return json_encode(false);
        }
    }

    function getServicesCustomer($data, $customer_id){
        $query = "SELECT `s`.`name`, `s`.`description`, `s`.`area_id`, `s`.`provider_id` 
        FROM `services_orders` `so` 
        LEFT JOIN `services` `s` ON `s`.`service_id` = `so`.`service_id` LEFT JOIN `areas` `a` ON `a`.`area_id` = `s`.`area_id`  WHERE `so`.customer_id = '{$customer_id}' AND `so`.`exploration_date` > CURRENT_DATE";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row=$result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }
    function editService($data){
        
    }


    function getServiceCategories(){
        $query = "SELECT * FROM service_categories";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr,$row);
        }
        return json_encode($resultArr);
    }

    function addServiceCategory($data, $worker_id){
        $query = "INSERT INTO `service_categories`(`name`, `is_personal`, `worker_id`) VALUES ('{$data['name']}', '{$data['is_personal']}', '{$worker_id}')";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function addServiceSchedule($data, $worker_id){
        if (isset($data['workers'])) {
            foreach ($data['workers'] as $worker) {
                $query = "INSERT INTO `service_schedule`(`service_id`, `amount_of_customers`, `provider_id`, `worker_id`, `schedule_date`, `time_start`, `duration`, `note`, `note_for_customer`, `area_id`) VALUES ('{$data['service_id']}', '{$data['amount_of_customers']}', '{$worker['worker_id']}', '{$worker_id}', '{$data['schedule_date']}', '{$data['time_start']}', '{$data['duration']}', '{$data['note']}', '{$data['note_for_customer']}', '{$worker['area_id']}')";
                $result = $this->con->query($query);
                $schedule_id=$this->con->insert_id;
                if ($result) {
                    $query = "INSERT INTO `service_sessions`(`schedule_id`, `customer_id`, `worker_id`, `reason_order_id`, `reason_type`, area_id, provider_id) VALUES ('{$schedule_id}', '{$worker['customer_id']}', '{$worker_id}', '{$worker['reasons']['order_id']}', '{$worker['reasons']['type']}', '{$worker['area_id']}', '{$worker['worker_id']}')";
                    $result = $this->con->query($query);
                }
            }
        }else {
            
            $query = "INSERT INTO `service_schedule`(`service_id`, `amount_of_customers`, `provider_id`, `worker_id`, `schedule_date`, `time_start`, `duration`, `note`, `note_for_customer`, `area_id`) VALUES ('{$data['service_id']}', '{$data['amount_of_customers']}', '{$data['provider_id']}', '{$worker_id}', '{$data['schedule_date']}', '{$data['time_start']}', '{$data['duration']}', '{$data['note']}', '{$data['note_for_customer']}', '{$data['area_id']}')";
            $result = $this->con->query($query);
            $schedule_id=$this->con->insert_id;
            if ($result) {
                foreach ($data['customers'] as $value) {
    
                    $query = "INSERT INTO `service_sessions`(`schedule_id`, `customer_id`, `worker_id`, `reason_order_id`, `reason_type`, area_id, provider_id) VALUES ('{$schedule_id}', '{$value['customer_id']}', '{$worker_id}', '{$value['reasons']['order_id']}', '{$value['reasons']['type']}', '{$value['area_id']}', '{$value['worker_id']}')";
                    $result = $this->con->query($query);
                    
                }
    
    
                if ($result) {
                    return json_encode($result, JSON_UNESCAPED_UNICODE);
                    
                }else {
                    header("HTTP/1.1 501 customer not added");
                    return json_encode(false, JSON_UNESCAPED_UNICODE);
    
                }
    
            }
        }
        return json_encode(true);
    }

    function getServiceSchedule($data, $worker){
        $resultArr = [];
        // $query = "SELECT `ss`.`schedule_id`, `ss`.`service_id`, `ss`.`amount_of_customers`, `ss`.`provider_id`, `ss`.`worker_id`, `ss`.`schedule_date`, `ss`.`time_start`, `ss`.`duration`, `ss`.`log_timestamp`, `ss`.`note`, `ss`.`note_for_customer`, `s`.`name`,  `ss`.`area_id`, `a`.`title`, CONCAT(`wp`.`last_name`, \" \", `wp`.`first_name`) `provider_name`, (SELECT COUNT(*) FROM service_sessions WHERE service_sessions.schedule_id = `ss`.`schedule_id`) amount_of_enrolled, (SELECT title FROM `possitions` WHERE position_id = wp.position_id) position
        // FROM `service_schedule` `ss`
        // LEFT JOIN services `s` ON `s`.`service_id` = `ss`.`service_id`
        // LEFT JOIN areas `a` ON `a`.`area_id` = `ss`.`area_id`
        // LEFT JOIN workers `wp` ON `wp`.`worker_id` = `ss`.`provider_id`
        // LEFT JOIN workers `w` ON `w`.`worker_id` = `ss`.`worker_id`";

        // $query = "SELECT `ss`.`schedule_id`, `ss`.`service_id`, `ss`.`amount_of_customers`, `ss`.`provider_id`, `ss`.`worker_id`, `ss`.`schedule_date`, `ss`.`time_start`, `ss`.`duration`, `ss`.`log_timestamp`, `ss`.`note`, `ss`.`note_for_customer`, `s`.`name`,  `ss`.`area_id`, `a`.`title`, CONCAT(`wp`.`last_name`, ' ', `wp`.`first_name`) `provider_name`, (SELECT COUNT(*) FROM service_sessions WHERE service_sessions.schedule_id = `ss`.`schedule_id`) amount_of_enrolled, (SELECT title FROM `possitions` WHERE position_id = wp.position_id) position, COUNT(ss.schedule_id) amount_of_services, IF(COUNT(ss.schedule_id) > 1, 'group', 'single') schedule_type
        // FROM `service_schedule` `ss`
        // LEFT JOIN services `s` ON `s`.`service_id` = `ss`.`service_id`
        // LEFT JOIN areas `a` ON `a`.`area_id` = `ss`.`area_id`
        // LEFT JOIN workers `wp` ON `wp`.`worker_id` = `ss`.`provider_id`
        // LEFT JOIN workers `w` ON `w`.`worker_id` = `ss`.`worker_id`
        // ";

        $query = "SELECT `ss`.`schedule_id`, `ss`.`service_id`, `ss`.`amount_of_customers`, `ss`.`provider_id`, `ss`.`worker_id`, `ss`.`schedule_date`, `ss`.`time_start`, `ss`.`duration`, `ss`.`log_timestamp`, `ss`.`note`, `ss`.`note_for_customer`, `s`.`name`,  `ss`.`area_id`, `a`.`title`, CONCAT(`wp`.`last_name`, ' ', `wp`.`first_name`) `provider_name`, (SELECT COUNT(*) FROM service_sessions WHERE service_sessions.schedule_id = `ss`.`schedule_id`) amount_of_enrolled, (SELECT title FROM `possitions` WHERE position_id = wp.position_id) position, COUNT(ss.schedule_id) amount_of_services, IF(COUNT(ss.schedule_id) > 1, 'group', 'single') schedule_type,

        (SELECT GROUP_CONCAT(CONCAT(wtemp.last_name, ' ', wtemp.first_name)) FROM service_schedule sstemp  LEFT JOIN workers wtemp ON  sstemp.provider_id = wtemp.worker_id WHERE sstemp.time_start = ss.time_start AND sstemp.schedule_date = ss.schedule_date AND ss.service_id = sstemp.service_id) provider_names, s.color
           FROM `service_schedule` `ss`
                LEFT JOIN services `s` ON `s`.`service_id` = `ss`.`service_id`
                LEFT JOIN areas `a` ON `a`.`area_id` = `ss`.`area_id`
                LEFT JOIN workers `wp` ON `wp`.`worker_id` = `ss`.`provider_id`
                LEFT JOIN workers `w` ON `w`.`worker_id` = `ss`.`worker_id`
                ";

        $query .= " WHERE `s`.`organization_id` = '{$worker->organization_id}' AND DATE(`ss`.`schedule_date`) <= '{$data['dateEnd']}' AND DATE(`ss`.`schedule_date`) >= '{$data['dateStart']}'
        ";
        if (isset($data['filter'])) {
            if (!empty($data['filter']['by_category'])) {
                $categories = implode(",", $data['filter']['by_category']);
                $query .= " AND `s`.category_id in ($categories)";
            }
            if (!empty($data['filter']['keyword'])) {
                $query .= " AND `s`.`name` LIKE '{$data['filter']['keyword']}%'";
            }
            if (!empty($data['filter']['position_id'])) {
                $query .= " AND wp.position_id = '{$data['filter']['position_id']}'";
            }
        }

        $query .= " GROUP BY ss.service_id, ss.time_start, ss.schedule_date";
        $result = $this->con->query($query);
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr);
    }

    function getServiceScheduleSingle($data){
        if (isset($data['schedule_id'])) {
            $query = "SELECT service_schedule.*, CONCAT(workers.last_name, ' ', workers.first_name) provider_name,  
            services.name service_name, areas.title area_name
            FROM service_schedule 
            LEFT JOIN workers on workers.worker_id = service_schedule.provider_id
            LEFT JOIN services on service_schedule.service_id = services.service_id
            LEFT JOIN areas on areas.area_id = service_schedule.area_id
            WHERE schedule_id = '{$data['schedule_id']}'";
            $schedule = $this->con->query($query)->fetch_assoc();
            $query = "SELECT service_sessions.*, CONCAT(customers.last_name, ' ', customers.first_name) customer_name,
            CASE service_sessions.reason_type 
                WHEN 'service' THEN (SELECT CONCAT(services.name, '#', services_orders.order_id) FROM services_orders LEFT JOIN services ON services.service_id = services_orders.service_id WHERE services_orders.order_id = service_sessions.reason_order_id)
                WHEN 'sub' THEN (SELECT CONCAT(subscriptions.name, '#', subscription_orders.order_id)  FROM subscription_orders LEFT JOIN subscriptions ON subscriptions.subscription_id = subscription_orders.subscription_id WHERE subscription_orders.order_id =service_sessions.reason_order_id)
            END as reason
            FROM `service_sessions`
            LEFT JOIN customers ON customers.customer_id = service_sessions.customer_id
            WHERE service_sessions.schedule_id = '{$data['schedule_id']}'";
            $tempArr = [];
            $result = $this->con->query($query);
            while ($row = $result->fetch_assoc()) {
                array_push($tempArr, $row);
            }
    
            $schedule['enrolled'] = $tempArr;
            return json_encode($schedule, JSON_UNESCAPED_UNICODE);
        }elseif (isset($data['service_id'])) {
            $query = "SELECT service_schedule.*, CONCAT(workers.last_name, ' ', workers.first_name) provider_name,  
            services.name service_name, areas.title area_name
            FROM service_schedule 
            LEFT JOIN workers on workers.worker_id = service_schedule.provider_id
            LEFT JOIN services on service_schedule.service_id = services.service_id
            LEFT JOIN areas on areas.area_id = service_schedule.area_id
            WHERE service_schedule.service_id = '{$data['service_id']}' AND schedule_date = '{$data['schedule_date']}' AND  time_start = '{$data['time_start']}'";
            $scheduleArr = [];
            $schedule = $this->con->query($query);
            while ($row = $schedule->fetch_assoc()) {
                array_push($scheduleArr, $row);
            }
            foreach ($scheduleArr as $key => $value) {
                $query = "SELECT service_sessions.*, CONCAT(customers.last_name, ' ', customers.first_name) customer_name,
                 CASE service_sessions.reason_type 
                WHEN 'service' THEN (SELECT CONCAT(services.name, '#', services_orders.order_id) FROM services_orders LEFT JOIN services ON services.service_id = services_orders.service_id WHERE services_orders.order_id = service_sessions.reason_order_id)
                WHEN 'sub' THEN (SELECT CONCAT(subscriptions.name, '#', subscription_orders.order_id)  FROM subscription_orders LEFT JOIN subscriptions ON subscriptions.subscription_id = subscription_orders.subscription_id WHERE subscription_orders.order_id =service_sessions.reason_order_id)
                END as reason
                FROM `service_sessions`
                LEFT JOIN customers ON customers.customer_id = service_sessions.customer_id
                WHERE service_sessions.schedule_id = '{$value['schedule_id']}'";
                $tempArr = [];
                $result = $this->con->query($query);
                while ($row = $result->fetch_assoc()) {
                    array_push($tempArr, $row);
                }
    
            $scheduleArr[$key]['enrolled'] = $tempArr;
            }
            return json_encode($scheduleArr, JSON_UNESCAPED_UNICODE);

        }

    }

    function cancelEnrollServiceWorker($data){
        $query = "DELETE FROM `service_sessions` WHERE schedule_id = '{$data['schedule_id']}' AND customer_id = '{$data['customer_id']}'";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function getServiceSessionsForCustomer($data, $customer_id){
        
    }

    function getServiceScheduleForCustomer($data, $customer_id){
        $query = "SELECT `t`.*, `s`.`name`, `s`.`description`, `w`.`photo`, `w`.`first_name`, `w`.`last_name`, DATE_FORMAT(`t`.`training_start`, '%H:%i') `training_start_format`, DATE_FORMAT(`t`.`training_end`, '%H:%i') training_end_format
			FROM training_sessions `ts`
            LEFT JOIN trainings t on ts.training_id = t.training_id
            LEFT JOIN `services` `s` ON `t`.`service_id` = `s`.`service_id`
            LEFT JOIN `workers` `w` ON `w`.`worker_id` = `t`.`worker_id`
            WHERE ts.customer_id = '{$customer_id}' AND t.training_date >= CURRENT_DATE";
        $query = "SELECT ss.schedule_id, ss.schedule_date `training_date`
        FROM service_schedule ss
        LEFT JOIN services s ON s.service_id = ss.service_id
        WHERE ss.service_id in
        (SELECT sser.service_id FROM subscription_services sser WHERE sser.subscription_id in (SELECT so.subscription_id FROM subscription_orders so WHERE so.customer_id = $customer_id AND so.exploration_date >= CURRENT_DATE)) AND ss.schedule_date >= CURRENT_DATE 
        ";
            $result = $this->con->query($query);
            $resultArr = [];
            while ($row = $result->fetch_assoc()) {
                array_push($resultArr, $row);
            }
            return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function getServicesForDay($data, $customer_id){
        // $query = "SELECT  s.*, ss.*
        // FROM service_schedule ss
        // LEFT JOIN services s ON s.service_id = ss.service_id
        // WHERE ss.service_id in
        // (SELECT sser.service_id FROM subscription_services sser WHERE sser.subscription_id in (SELECT so.subscription_id FROM subscription_orders so WHERE so.customer_id = $customer_id AND so.exploration_date >= CURRENT_DATE)) AND ss.schedule_date = '{$data['date']}' 
        // ";
        $query = "SELECT * FROM service_categories";
        $result = $this->con->query($query);
        $categories = [];
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($categories, $row);
        }
        foreach ($categories as $value) {
            $query = "SELECT  s.*, ss.*, (SELECT COUNT(*) FROM service_sessions WHERE service_sessions.schedule_id = ss.schedule_id) amount_of_requests, (SELECT 'true' FROM service_sessions WHERE service_sessions.schedule_id = ss.schedule_id AND service_sessions.customer_id = '{$customer_id}') is_enrolled, DATE_FORMAT(`ss`.`time_start`, '%H:%i') `training_start`, (SELECT CONCAT(workers.last_name, \" \", LEFT(workers.first_name,1), \".\", LEFT(workers.middle_name,1), \".\") FROM workers WHERE workers.worker_id = ss.provider_id) provider_name,
            (SELECT name FROM organizations WHERE organizations.organization_id = s.organization_id) organization_name
            FROM service_schedule ss
            LEFT JOIN services s ON s.service_id = ss.service_id
            WHERE ss.service_id in
            (SELECT sser.service_id FROM subscription_services sser WHERE sser.subscription_id in (SELECT so.subscription_id FROM subscription_orders so WHERE so.customer_id = '{$customer_id}' AND so.exploration_date >= CURRENT_DATE)) AND ss.schedule_date = '{$data['date']}' AND s.category_id = '{$value['category_id']}'";
                $result = $this->con->query($query);
                $services = [];
                while ($row = $result->fetch_assoc()) {
                    array_push($services, $row);
                }
                $temp = [];
                if (!empty($services)) {
                    $temp['category_name'] = $value['name'];
                    $temp['services'] = $services;
                    array_push($resultArr, $temp);
                }

            
        }
            return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }


    function getServiceByScheduleId($data, $customer){
        $query = "SELECT  s.*, ss.*, (SELECT COUNT(*) FROM service_sessions WHERE service_sessions.schedule_id = ss.schedule_id) amount_of_requests, (SELECT 'true' FROM service_sessions WHERE service_sessions.schedule_id = ss.schedule_id AND service_sessions.customer_id = '{$customer->customer_id}') is_enrolled, DATE_FORMAT(`ss`.`time_start`, '%H:%i') `training_start`, (SELECT CONCAT(workers.last_name, \" \", LEFT(workers.first_name,1), \".\", LEFT(workers.middle_name,1), \".\") FROM workers WHERE workers.worker_id = ss.provider_id) provider_name, (SELECT workers.photo FROM workers WHERE workers.worker_id = ss.provider_id) provider_photo,
        (SELECT name FROM organizations WHERE organizations.organization_id = s.organization_id) organization_name, (SELECT title FROM areas WHERE areas.area_id = ss.area_id) area_name
        FROM service_schedule ss
        LEFT JOIN services s ON s.service_id = ss.service_id
        WHERE ss.schedule_id = '{$data['schedule_id']}'";
        $resultArr = $this->con->query($query)->fetch_assoc();

        $query = "SELECT subscription_orders.*, subscriptions.*, 'sub' type FROM subscription_orders
                LEFT JOIN subscription_services ON subscription_orders.subscription_id = subscription_services.subscription_id
                LEFT JOIN subscriptions ON subscription_orders.subscription_id = subscriptions.subscription_id
                WHERE subscription_orders.customer_id = '{$customer->customer_id}' AND subscription_services.service_id in (SELECT service_id FROM service_schedule WHERE schedule_id = '{$data['schedule_id']}') GROUP BY subscription_orders.order_id";
                $result = $this->con->query($query);
                $tempArr = [];
                while ($row=$result->fetch_assoc()) {
                    array_push($tempArr, $row);
                }
        $resultArr['reasons'] = $tempArr;
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function editServices(){
        
    }

    function enrollService($data, $customer){
        $query = "INSERT INTO `service_sessions`(`schedule_id`, `customer_id`) VALUES ('{$data['schedule_id']}', '{$customer->customer_id}')";
        $query = "INSERT INTO `service_sessions`(`schedule_id`, `customer_id`, `reason_order_id`, `reason_type`) VALUES ('{$data['schedule_id']}', '{$customer->customer_id}', '{$data['order_id']}', '{$data['type']}')";
        $result = $this->con->query($query);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    function cancelEnrollService($data, $customer){
        $query = "DELETE FROM `service_sessions` WHERE schedule_id = '{$data['schedule_id']}' AND customer_id = '{$customer->customer_id}'";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function addServicePersonal($data, $worker, $customer){
        $customer->setID($data['customer_id']);
        $balance = $customer->balance;
        $query = "SELECT * FROM services WHERE service_id = '{$data['service_id']}'";
        $service = $this->con->query($query)->fetch_assoc();
        if ($service['price']<=$balance) {
            $query = "INSERT INTO `services_orders`(`service_id`, `customer_id`, `price`, `worker_id`) VALUES ('{$service['service_id']}', '{$customer->customer_id}','{$service['price']}', '{$worker->worker_id}')";
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
                        $query = "INSERT INTO `service_ingredients_orders`(`service_id`, `item_id`, `amount`, `customer_id`, `service_order_id`, `worker_id`) VALUES ('{$value['service_id']}','{$value['item_id']}','{$value['amount']}', '{$customer->customer_id}','{$service_order_id}','{$worker->worker_id}')";
                        $this->con->query($query);
                    }
                    $query = "INSERT INTO `service_schedule`(`service_id`, `amount_of_customers`, `provider_id`, `worker_id`, `schedule_date`, `time_start`, `duration`, `note`, `note_for_customer`, `area_id`) VALUES ('{$data['service_id']}', 1, '{$data['provider_id']}', '{$worker->worker_id}', '{$data['schedule_date']}', '{$data['time_start']}', '{$data['duration']}', '{$data['note']}', '{$data['note_for_customer']}', '{$data['area_id']}')";
                    $result = $this->con->query($query);
                    if ($result) {
                        $schedule_id = $this->con->insert_id;
                        $query = "INSERT INTO `service_sessions`(`schedule_id`, `customer_id`) VALUES ('{$schedule_id}', '{$customer->customer_id}')";
                        $result = $this->con->query($query);
                        return json_encode($result, JSON_UNESCAPED_UNICODE);

                    }

                    

            }
        }else{
            return json_encode(false);
        }
    }
    function addServiceSession($data, $worker_id){
        $query = "SELECT * FROM `service_sessions` WHERE schedule_id = '{$data['schedule_id']}' AND customer_id = '{$data['customer_id']}'";
        $result = $this->con->query($query)->fetch_assoc();
        if (empty($result)) {
            // foreach ($data['customers'] as $value) {
                
                $query = "INSERT INTO `service_sessions`(`schedule_id`, `customer_id`, `worker_id`, `reason_order_id`, `reason_type`) VALUES ('{$data['schedule_id']}', '{$data['customer_id']}', '{$worker_id}', '{$data['order_id']}', '{$data['type']}')";
                $result = $this->con->query($query);
                
            // }
            return json_encode($result, JSON_UNESCAPED_UNICODE);
        }else{
            return json_encode(false, JSON_UNESCAPED_UNICODE);

        }
    }
    function visitService($data, $tablet, $worker){
        $query = "UPDATE service_sessions SET status = 1 WHERE session_id = '{$data['session_id']}'";
        $result = $this->con->query($query);
        return json_encode($result);
    }
    function startService($data, $tablet, $worker){
        $uuid = time();
                $query = "INSERT INTO `qr_tablet`(`type`, `instance_id`, `device_id`, `qr_uuid`, `worker_id`) VALUES ('start_training', '{$data['schedule_id']}', 1, '{$uuid}', {$worker->worker_id})";
                $result = $this->con->query($query);
                $tablet->sendQR('start_training', $uuid, $worker->worker_id);
                return true;
    }

    function getStatistics(){
        
    }
}