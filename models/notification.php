<?php
class Notification{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function getLastNotificationByTime($data){
        while (true) {
            $last_call = isset($data['timestamp']) ? (int)$data['timestamp'] : null;
            clearstatcache();
            $query = "SELECT `log_id`,`note` `client_id`, UNIX_TIMESTAMP(`log_timestamp`) `timestamp` FROM `qr_reception_log` ORDER BY `log_id` DESC LIMIT 1";
            $result = $this->con->query($query);
            $row = $result->fetch_assoc();
            if ($last_call == null || $row['timestamp'] > $last_call) {
                return json_encode($row, JSON_UNESCAPED_UNICODE);
            }else{
                sleep(10);
            }
        }
    }

}