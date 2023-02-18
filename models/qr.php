<?php
class QR{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function getQRLP($data){
        while (true) {
            sleep(3);
            $last_call = isset($data['timestamp']) ? (int)$data['timestamp'] : null;
            clearstatcache();
            $query = "SELECT `qr_uuid`, UNIX_TIMESTAMP(`qr_timestamp`) `timestamp`, `type` FROM `qr_tablet` ORDER BY `qr_id` DESC LIMIT 1";
            $result = $this->con->query($query);
            $row = $result->fetch_assoc();
            if ($last_call == null || $row['timestamp'] > $last_call || $row['timestamp'] != $last_call) {
                $this->con->close();
                return json_encode($row, JSON_UNESCAPED_UNICODE);
            }else{
                sleep(5);
            }
        }
    }
    function sendQR($data){
        $result = $this->con->query("SELECT * FROM `qr_tablet` WHERE `qr_uuid` = '{$data['qr_uuid']}'");
        $result = $result->fetch_assoc();
        $worker_id = $result['worker_id'];
        if ($result['type'] == 'sell_service') {
            $query = "SELECT `service_id`, `name`, `description`, `area_id`, `provider_id`, `worker_id`, `service_timestamp`, `category_id`, `provider_fee`, `isDeleted`, `price`, `validity`, `amount_of_customers`, `visits` FROM `services` WHERE `service_id` = '{$result['instance_id']}'";
            $result = $this->con->query($query);
            $result = $result->fetch_assoc();
            $result['qr_type'] =  'sell_service';
            return json_encode($result, JSON_UNESCAPED_UNICODE);
        }elseif ($result['type'] == 'start_training') {
            $query = "SELECT `t`.*, `s`.`name` FROM `trainings` `t` LEFT JOIN `services` `s` ON `s`.`service_id` = `t`.`service_id` WHERE `training_id` = '{$result['instance_id']}'";
            $result = $this->con->query($query);
            $result = $result->fetch_assoc();
            $result['qr_type'] =  'start_training';
            return json_encode($result, JSON_UNESCAPED_UNICODE);
        }elseif ($result['type'] == 'sell_recipe') {
            $query = "SELECT * FROM `recipes`  WHERE `recipe_id` = '{$result['instance_id']}'";
            $result = $this->con->query($query);
            $result = $result->fetch_assoc();
            $result['qr_type'] =  'sell_recipe';
            $result['worker_id'] = $worker_id;
            return json_encode($result, JSON_UNESCAPED_UNICODE);
        }elseif ($result['type'] == 'sell_cart') {
            $query = "SELECT * FROM `shopping_carts`  WHERE `cart_id` = '{$result['instance_id']}'";
            $result = $this->con->query($query);
            $result = $result->fetch_assoc();
            $result['qr_type'] =  'sell_cart';
            return json_encode($result, JSON_UNESCAPED_UNICODE);
        }

    }
}