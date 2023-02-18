<?php
class Area{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function getAreas($data){
        if ($data['tree'] == "1") {
            $query = "SELECT name, global_area_id FROM global_areas";
            $result = $this->con->query($query);
            $resultArr = [];
            while ($row = $result->fetch_assoc()) {
                array_push($resultArr, $row);
            }
            foreach ($resultArr as $key => $value) {
                $query = "SELECT * FROM areas WHERE isDeleted = 0 AND global_area_id = '{$value['global_area_id']}'";
                $result = $this->con->query($query);
                $areas = [];
                while ($row = $result->fetch_assoc()) {
                    array_push($areas, $row);
                }
                $resultArr[$key]['areas'] = $areas;
            }
            return json_encode($resultArr, JSON_UNESCAPED_UNICODE);

        }else {
            
            
            
            
            $query = "SELECT * FROM `areas` WHERE isDeleted = 0 ";
            if (!empty($data['filter']['sort']['service_id'])) {
                $query .= " AND global_area_id in (SELECT global_area_id FROM `services` WHERE service_id = '{$data['filter']['sort']['service_id']}')";
            }
            if (isset($data['filter']['sort']['worker_schedule'])) {
                $query .= " AND area_id not in (SELECT area_id FROM worker_schedule WHERE time_start <= '{$data['filter']['sort']['worker_schedule']['time_start']}' AND time_end >= '{$data['filter']['sort']['worker_schedule']['time_end']}' AND schedule_date = '{$data['filter']['sort']['worker_schedule']['schedule_date']}')";
            }
            if (isset($data['filter']['sort']['service_schedule'])) {
                $query .= " AND area_id in (SELECT area_id FROM worker_schedule WHERE time_start <= '{$data['filter']['sort']['service_schedule']['time_start']}' AND time_end >= '{$data['filter']['sort']['service_schedule']['time_start']}' AND schedule_date = '{$data['filter']['sort']['service_schedule']['schedule_date']}' AND worker_id = '{$data['filter']['sort']['worker_id']}')";
            }
            if (!empty($data['filter']['sort']['is_global'])) {
                $query .= " AND is_public = 1";
            }

            if (!empty($data['filter']['sort']['worker_id'])) {
                $query .= " AND global_area_id in (SELECT global_area_id FROM workers WHERE worker_id = '{$data['filter']['sort']['worker_id']}')";
            }
            $result = $this->con->query($query);
            $resultArr = [];
            while ($row = $result->fetch_assoc()) {
                array_push($resultArr, $row);
            }
            return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
        }
    }

    function editArea($data){
        if (!empty($data['area_id'])) {
            $query = "UPDATE `areas` SET `title`='{$data['title']}',`description`='{$data['description']}',`global_area_id`='{$data['global_area_id']}', `is_public`='{$data['is_public']}' WHERE area_id = '{$data['area_id']}'";
            $result = $this->con->query($query);
            return json_encode($result);
            
        }elseif (!empty($data['global_area_id'])) {
            $query = "UPDATE `global_areas` SET `name`='{$data['name']}' WHERE global_area_id = '{$data['global_area_id']}'";
            $result = $this->con->query($query);
            return json_encode($result);
        }
    }

    function removeArea($data){
        $query = "UPDATE `areas` SET isDeleted = 1 WHERE area_id = '{$data['area_id']}'";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function getAreaLogLP($data){
        while (true) {
            sleep(10);
            $last_call = isset($data['timestamp']) ? (int)$data['timestamp'] : null;
            clearstatcache();
            $query = "SELECT `log_uuid`, UNIX_TIMESTAMP(`log_timestamp`) `timestamp` FROM `areas_log` ORDER BY `log_id` DESC LIMIT 1";
            $result = $this->con->query($query);
            $row = $result->fetch_assoc();
            if ($last_call == null || $row['timestamp'] > $last_call || $row['timestamp'] != $last_call) {
                $this->con->close();
                return json_encode($row, JSON_UNESCAPED_UNICODE);
            }else{
                sleep(30);
            }
        }
    }

    function setAreaId($data, $worker_id){
        $result = $this->con->query("INSERT INTO `doro2`.`areas_log` (`worker_id`, `area_id`, `log_timestamp`, `log_uuid`) VALUES ('{$worker_id}', '{$data['area_id']}', CURRENT_TIMESTAMP, UUID())");
        return json_encode($result);
    }

    function addSchedule($data, $worker_id){
        $query = "INSERT INTO `area_schedule`(`area_id`, `time_start`, `time_end`, `schedule_date`, `worker_id`, `price`, `customer_id`) VALUES ('{$data['area_id']}', '{$data['time_start']}', '{$data['time_end']}', '{$data['date']}', $worker_id, '{$data['price']}', '{$data['customer_id']}')";
        $result = $this->con->query($query);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    function getSchedule($data, $worker_id){
        $resultArr = [];
        $query = "SELECT `as`.`schedule_id`, `as`.`area_id`, DATE_FORMAT(`as`.`time_start`, '%H:%i') `time_start`,DATE_FORMAT(`as`.`time_end`, '%H:%i') `time_end`, `as`.`schedule_date`, `a`.`title`, `as`.`price`, CONCAT(`c`.`last_name`, ' ', `c`.`first_name`) `name`
        FROM `area_schedule` `as`
        LEFT JOIN `areas` `a` ON `a`.`area_id` = `as`.`area_id`
        LEFT JOIN `customers` `c` ON `c`.`customer_id` = `as`.`customer_id`
        WHERE `as`.`schedule_date` > '{$data['dateStart']}' AND `as`.`schedule_date` < '{$data['dateEnd']}'";
        $result = $this->con->query($query);
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function addGlobalArea($data, $worker){
        $query = "INSERT INTO `global_areas`(`name`,`worker_id`, `organization_id`) VALUES ('{$data['name']}', '{$worker->worker_id}', '{$worker->organization_id}')";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function addAreasToGlobalAreas($data){
        foreach ($data['areas'] as $area_id) {
            $query = "UPDATE `areas` SET `global_area_id`='{$data['global_area_id']}' WHERE area_id = '{$area_id}'";
            $this->con->query($query);
        }
        return json_encode(true);
    }

    function getGlobalAreas($data){
        $query = "SELECT name, global_area_id FROM global_areas";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function addArea($data, $worker){
        $query = "INSERT INTO `areas`(`title`, `description`, `global_area_id`, `organization_id`, `is_public`) VALUES ('{$data['title']}', '{$data['description']}', '{$data['global_area_id']}', '{$worker->organization_id}', '{$data['is_public']}')";
        $result = $this->con->query($query);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}