<?php
class Training{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    function addTraining($data){
        if (!empty($data['time_start']) && !empty($data['time_end']) && !empty($data['training_date'])) {
            $query = "INSERT INTO `trainings`(`worker_id`, `training_start`, `training_end`, `training_date`, `training_category_id`, service_id) VALUES ({$data['worker_id']},'{$data['time_start']}','{$data['time_end']}', '{$data['training_date']}',1, '{$data['service_id']}')";
            $result = $this->con->query($query);
            return json_encode($result);
        }else{
            header("HTTP/1.1 400 something went wrong!");   
            return json_encode(false);
        }
    }

    function getTrainigs($data){
        $query = "SELECT `t`.`training_date`, DATE_FORMAT(`t`.`training_start`, '%H:%i') `training_start`, DATE_FORMAT(`t`.`training_end`, '%H:%i') training_end, `s`.`name`, `s`.`amount_of_customers`, `w`.`last_name`, `w`.`first_name`, `a`.`title`, `t`.`training_id`, `w`.`worker_id`, `a`.`area_id`
        FROM `trainings` `t` 
        LEFT JOIN `services` `s` ON `s`.`service_id` = `t`.`service_id` 
        LEFT JOIN `workers` `w` ON `t`.`worker_id` = `w`.`worker_id` 
        LEFT JOIN `areas` `a` ON `a`.`area_id` = `s`.`area_id`
        WHERE training_date >= '{$data['startDate']}' AND training_date <= '{$data['endDate']}'";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function getTrainigsScheduleCustomer($data, $customer_id){
        //$query = "SELECT training_id, training_date FROM `trainings` WHERE `training_date` >= CURRENT_DATE AND `training_date` <= (SELECT DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY))";

        $query = "SELECT `t`.`training_date`, DATE_FORMAT(`t`.`training_start`, '%H:%i') `training_start`, DATE_FORMAT(`t`.`training_end`, '%H:%i') training_end, `s`.`name`, `s`.`amount_of_customers`, `w`.`last_name`, `w`.`first_name`, `a`.`title`, (SELECT COUNT(`ts`.`training_id`) FROM `training_sessions` `ts` WHERE `ts`.`training_id` = `t`.`training_id`) as `amount_of_requests`
        FROM `trainings` `t` 
        LEFT JOIN `services` `s` ON `s`.`service_id` = `t`.`service_id` 
        LEFT JOIN `workers` `w` ON `t`.`worker_id` = `w`.`worker_id` 
        LEFT JOIN `areas` `a` ON `a`.`area_id` = `s`.`area_id`
        WHERE (SELECT COUNT(`ts`.`training_id`) FROM `training_sessions` `ts` WHERE `ts`.`training_id` = `t`.`training_id`)  < `s`.`amount_of_customers`  AND  `training_date` >= CURRENT_DATE AND `training_date` <= (SELECT DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY))";





        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function getQRTrainingSession($data, $worker_id, $tablet){
        $uuid = time();
        $query = "INSERT INTO `qr_tablet`(`type`, `instance_id`, `device_id`, `qr_uuid`, `worker_id`) VALUES ('start_training', '{$data['training_id']}', 1, UUID(), {$worker_id})";
        $result = $this->con->query($query);
        if ($result) {
            $tablet->sendQR('start_training', $uuid, $worker_id);
            return $uuid;
        }else{
            header("HTTP/1.1 400 something went wrong!");   
            return false;
        }
            
        
    }

    function getTrainigsCustomer($data, $customer_id){
        if ($data["today"]) {
            // $query = "SELECT `t`.`training_date`, DATE_FORMAT(`t`.`training_start`, '%H:%i') `training_start`, DATE_FORMAT(`t`.`training_end`, '%H:%i') training_end, `s`.`name`, `s`.`amount_of_customers`, `a`.`title`, `s`.`description`
            // FROM `trainings` `t` 
            // LEFT JOIN `services_orders` `so` ON `so`.`service_id` = `t`.`service_id`
            // LEFT JOIN `services` `s` ON `s`.`service_id` = `t`.`service_id` 
            // LEFT JOIN `areas` `a` ON `a`.`area_id` = `s`.`area_id`
            // WHERE `t`.`training_date` = CURRENT_DATE AND `t`.`training_start` > CURRENT_TIME AND `so`.`visits` > 0 AND `so`.`customer_id` = '{$customer_id}' AND `so`.`exploration_date` > CURRENT_DATE";
            $query = "SELECT `t`.*, `s`.`name`, `s`.`description`, `w`.`photo`, `w`.`first_name`, `w`.`last_name`, DATE_FORMAT(`t`.`training_start`, '%H:%i') `training_start_format`, DATE_FORMAT(`t`.`training_end`, '%H:%i') training_end_format
			FROM training_sessions `ts`
            LEFT JOIN trainings t on ts.training_id = t.training_id
            LEFT JOIN `services` `s` ON `t`.`service_id` = `s`.`service_id`
            LEFT JOIN `workers` `w` ON `w`.`worker_id` = `t`.`worker_id`
            WHERE ts.customer_id = '{$customer_id}' AND t.training_date >= CURRENT_DATE";
            $result = $this->con->query($query);
            $resultArr = [];
            while ($row = $result->fetch_assoc()) {
                array_push($resultArr, $row);
            }
            return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
        }
    }

    function joinTraining($data, $customer_id){
        
        if (true) {
            $query = "SELECT order_id, `visits_left` FROM `services_orders` WHERE `customer_id` = '{$customer_id}' AND `service_id` IN(SELECT `service_id` FROM `trainings` WHERE `training_id` = '{$data['training_id']}') AND visits_left > 0 ORDER BY `services_orders`.`exploration_date` ASC LIMIT 1";
            $result = $this->con->query($query);
            $row = $result->fetch_assoc();
            if ($row['visits_left'] > 0) {
                $result = $this->con->query("UPDATE `services_orders` SET `visits_left` = visits_left - 1 WHERE order_id = '{$row['order_id']}'");
                $query = "INSERT INTO `training_sessions`(`training_id`, `customer_id`) VALUES ('{$data['training_id']}', '{$customer_id}')";
                $result = $this->con->query($query);
            }
            return $result;
        }
    
    }

    function enrollTraining($data, $customer_id){
        
        if (true) {
            $query = "SELECT order_id, `visits_left` FROM `services_orders` WHERE `customer_id` = '{$customer_id}' AND `service_id` IN(SELECT `service_id` FROM `trainings` WHERE `training_id` = '{$data['training_id']}') AND visits_left > 0 ORDER BY `services_orders`.`exploration_date` ASC LIMIT 1";
            $result = $this->con->query($query);
            $row = $result->fetch_assoc();
            if ($row['visits_left'] > 0) {
                $result = $this->con->query("UPDATE `services_orders` SET `visits_left` = visits_left - 1 WHERE order_id = '{$row['order_id']}'");
                $query = "INSERT INTO `training_sessions`(`training_id`, `customer_id`) VALUES ('{$data['training_id']}', '{$customer_id}')";
                $result = $this->con->query($query);
            }
            return $result;
        }
    
    }


    function cancelEnrollTraining($data, $customer_id){
        $query = "DELETE FROM training_sessions WHERE training_id = '{$data['training_id']}' AND customer_id = '{$customer_id}'";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function getTrainingsForDay($data, $customer_id){
        // $query = "SELECT `t`.`training_date`, DATE_FORMAT(`t`.`training_start`, '%H:%i') `training_start`, DATE_FORMAT(`t`.`training_end`, '%H:%i') training_end, `s`.`name`, `s`.`amount_of_customers`, `w`.`last_name`, `w`.`first_name`, `a`.`title`
        // FROM `trainings` `t` 
        // LEFT JOIN `services` `s` ON `s`.`service_id` = `t`.`service_id` 
        // LEFT JOIN `workers` `w` ON `t`.`worker_id` = `w`.`worker_id` 
        // LEFT JOIN `areas` `a` ON `a`.`area_id` = `s`.`area_id`
        // WHERE training_date = '{$data['date']}'";

            $query = "SELECT `t`.`training_date`, DATE_FORMAT(`t`.`training_start`, '%H:%i') `training_start`, DATE_FORMAT(`t`.`training_end`, '%H:%i') training_end, `s`.`name`, `s`.`amount_of_customers`, `w`.`last_name`, `w`.`first_name`, `a`.`title`, (SELECT COUNT(`ts`.`training_id`) FROM `training_sessions` `ts` WHERE `ts`.`training_id` = `t`.`training_id`) as `amount_of_requests`,
            (SELECT 'true' FROM `training_sessions` WHERE `training_sessions`.`customer_id` = '{$customer_id}' AND `training_sessions`.`training_id` = `t`.`training_id`) as is_enrolled, `t`.`training_id`
            FROM `trainings` `t` 
            LEFT JOIN `services` `s` ON `s`.`service_id` = `t`.`service_id` 
            LEFT JOIN `workers` `w` ON `t`.`worker_id` = `w`.`worker_id` 
            LEFT JOIN `areas` `a` ON `a`.`area_id` = `s`.`area_id`
            WHERE (SELECT COUNT(`ts`.`training_id`) FROM `training_sessions` `ts` WHERE `ts`.`training_id` = `t`.`training_id`)  < `s`.`amount_of_customers` AND `training_date` =  '{$data['date']}'";
        
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function getStat(){
        $query = "SELECT COUNT(*) amount, CONCAT(YEAR(`training_date`),'-' ,MONTH(`training_date`)) `date`, service_id serviceid, (SELECT name FROM services WHERE service_id = serviceid) service

        FROM trainings 
        GROUP BY YEAR(`training_date`), MONTH(`training_date`), `service_id`";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    
    }
}





// SELECT `t`.`training_date`, DATE_FORMAT(`t`.`training_start`, '%H:%i') `training_start`, DATE_FORMAT(`t`.`training_end`, '%H:%i') training_end, `s`.`name`, `s`.`amount_of_customers`, `w`.`last_name`, `w`.`first_name`, `a`.`title`, (SELECT COUNT(`ts`.`training_id`) FROM `training_sessions` `ts` WHERE `ts`.`training_id` = `t`.`training_id`) as `amount_of_requests`
//         FROM `trainings` `t` 
//         LEFT JOIN `services` `s` ON `s`.`service_id` = `t`.`service_id` 
//         LEFT JOIN `workers` `w` ON `t`.`worker_id` = `w`.`worker_id` 
//         LEFT JOIN `areas` `a` ON `a`.`area_id` = `s`.`area_id`
//         WHERE (SELECT COUNT(`ts`.`training_id`) FROM `training_sessions` `ts` WHERE `ts`.`training_id` = `t`.`training_id`)  < `s`.`amount_of_customers`



// SELECT `t`.`training_id`,`t`.`training_date`, DATE_FORMAT(`t`.`training_start`, '%H:%i') `training_start`, DATE_FORMAT(`t`.`training_end`, '%H:%i') training_end, `s`.`name`, `s`.`amount_of_customers`, `w`.`last_name`, `w`.`first_name`, `a`.`title`, 
// (SELECT COUNT(`ts`.`training_id`) FROM `training_sessions` `ts` 
//  WHERE `ts`.`training_id` = `t`.`training_id`) as `amount_of_requests`, (SELECT "true" FROM `training_sessions` WHERE `training_sessions`.`customer_id` = 5 AND `training_sessions`.`training_id` = `t`.`training_id`) as is_enrolled
//         FROM `trainings` `t` 
//         LEFT JOIN `services` `s` ON `s`.`service_id` = `t`.`service_id` 
//         LEFT JOIN `workers` `w` ON `t`.`worker_id` = `w`.`worker_id` 
//         LEFT JOIN `areas` `a` ON `a`.`area_id` = `s`.`area_id`
        
//         WHERE (SELECT COUNT(`ts`.`training_id`) FROM `training_sessions` `ts` WHERE `ts`.`training_id` = `t`.`training_id`)  < `s`.`amount_of_customers` AND  `training_date` >= CURRENT_DATE AND `training_date` <= (SELECT DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY))


