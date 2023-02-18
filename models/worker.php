<?php
class Worker{
    private $con;
    
    public $worker_id;
    public $role;
    public $isLoggedIn = false;
    public $token;
    public $organization_id;
    public $position_id;
    public $is_personal;

    function __construct($db) {
        $this->con = $db;
        //$this->token = $tokenR[];
        session_start();
        // $_SESSION['worker_id'] = 0;
        // unset($_SESSION['worker_id']);
        if (isset($_SESSION['worker_id'])) {
            $worker_id = $_SESSION['worker_id'];
            $query = "SELECT * FROM `workers` WHERE `worker_id` = {$_SESSION['worker_id']}";
            $result = $this->con->query($query);
            $result = $result->fetch_assoc();
            $this->position_id = $result['position_id'];
            $this->worker_id =  $result['worker_id'];
            $this->organization_id = $result['organization_id'];
            $this->isLoggedIn = true;
            //echo json_encode($result);
            
        }
        
            $data = json_decode(file_get_contents("php://input"), true);
        if(isset($_SERVER['HTTP_AUTH'])){
            $result = $this->checkKeyAuth(base64_decode($_SERVER['HTTP_AUTH']));
            if($result){
            	$this->worker_id = $result['worker_id'];
                $this->position_id = $result['position_id'];
                $this->worker_id =  $result['worker_id'];
                $this->organization_id = $result['organization_id'];
                $this->is_personal = $result['is_personal'];

                $this->isLoggedIn = true;
                //echo $_SERVER['HTTP_AUTH'];
            }else{
                $this->isLoggedIn = false;
            	//echo 'Ты дурак?';
            	//exit();
            	//return false;
            }
        }
        // elseif(isset($data['token'])){
        //     $result = $this->checkKeyAuth(base64_decode($data['token']));
        //     if($result){
        //     	$this->worker_id = $result['worker_id'];
        //         $this->position_id = $result['position_id'];
        //         $this->worker_id =  $result['worker_id'];
        //         $this->isLoggedIn = true;
        //         //echo $_SERVER['HTTP_AUTH'];
        //     }else{
        //         $this->isLoggedIn = false;
        //     	//echo 'Ты дурак?';
        //     	//exit();
        //     	//return false;
        //     }
        // }elseif(isset($_POST['token'])){
        //     $result = $this->checkKeyAuth(base64_decode($_POST['token']));
        //     if($result){
        //     	$this->worker_id = $result['worker_id'];
        //         $this->position_id = $result['position_id'];
        //         $this->worker_id =  $result['worker_id'];
        //         $this->isLoggedIn = true;
        //         //echo $_SERVER['HTTP_AUTH'];
        //     }else{
        //         $this->isLoggedIn = false;
        //     	//echo 'Ты дурак?';
        //     	//exit();
        //     	//return false;
        //     }
        // }
    }

    public function checkKeyAuth($login){
        $query = "SELECT workers.*, possitions.is_personal  FROM `workers` 
        LEFT JOIN possitions ON possitions.position_id = workers.position_id
        WHERE workers.login = '$login'";
        $result = $this->con->query($query);
        $result = $result->fetch_array(MYSQLI_ASSOC);



        if ($result == NULL) {
            return false;
        }else{
            //var_dump($result);
    	    return $result;
        }
    }


    function login($login, $password, $area_id){
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
            $query = "SELECT `w`.`login`, `w`.`password`, `w`.`first_name`, `w`.`middle_name`, `w`.`last_name`, `w`.`idDeleted`, `w`.`photo`, `w`.`position_id`, `p`.`title`, `p`.`access_level`, o.logo, w.worker_id FROM `workers` `w` 
            LEFT JOIN `possitions` `p` ON `w`.`position_id` = `p`.`position_id` 
            LEFT JOIN `organizations` `o` ON o.organization_id = w.organization_id
            WHERE `w`.`login` = '$login'";
            $result = $this->con->query($query);
            $result = $result->fetch_assoc();
            if ($result == NULL) {
                header("HTTP/1.1 403 User not found");
                return json_encode(false);
            }elseif (password_verify($password, $result['password'])) {
                if ($result['idDeleted'] == "0") {
                    $uuid = crypt(time(), "3c6b8f7676");
                    $this->worker_id = $result['worker_id'];
                    $this->position_id = $result['position_id'];
                    $this->isLoggedIn = true; 
                    //$this->con->query("INSERT INTO `doro2`.`areas_log` (`worker_id`, `area_id`, `log_timestamp`, `log_uuid`) VALUES ('{$this->worker_id}', '{$area_id}', CURRENT_TIMESTAMP, UUID())");
                    return json_encode(['key'=>base64_encode($result['login']), 'first_name'=>$result['first_name'], 'last_name'=>$result['last_name'], 'middle_name'=>$result['middle_name'], 'photo'=>$result['photo'], 'access_level'=>$result['access_level'], 'title'=>$result['title'], 'logo'=>$result['logo'], 'worker_id'=>$result['worker_id']]);      
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

    function getWorkers($data){
        $query = "SELECT `w`.`worker_id`,`w`.`position_id`,`w`.`login`,`w`.`first_name`,`w`.`middle_name`,`w`.`last_name`,`w`.`contact`, `p`.`title`, `w`.`gender` `gender_id`, `w`.`date_of_birth`, 
        CASE `w`.`gender`
        WHEN '0' THEN 'мужчина'
        WHEN '1' THEN 'женщина'
        END as `gender` FROM `workers` `w` LEFT JOIN `possitions` `p` ON `p`.`position_id`=`w`.`position_id` WHERE `w`.`idDeleted` = 0";
        if (!empty($data['filter']['avaibility']['time']) && !empty($data['filter']['avaibility']['date'])) {
            $time = str_replace(': ', ':',$data['filter']['avaibility']['time']);
            // $time = preg_replace(
            //     '/\s(?=([^"]*"[^"]*")*[^"]*$)/', ''
            //     , $time
            // );
            $time = substr($time, 0, 2).":".substr($time, -2);
            
            // $time =  strtotime($time);
            // $time = date("H:i", $time);
            // $time = addslashes($time);  
            $query .= " AND w.worker_id in (SELECT worker_id FROM worker_schedule WHERE worker_schedule.schedule_date = '{$data['filter']['avaibility']['date']}'  AND worker_schedule.time_start <= \"$time\" AND worker_schedule.time_end >= \"$time\")";
        }
        
        if (!empty($data['keyword'])) {
            $query .= " AND w.last_name LIKE '{$data['keyword']}%'";
        }
        if (!empty($data['filter']['position_id'])) {
            $query .= " AND w.position_id = '{$data['filter']['position_id']}'";
        }
        
        if (!empty($data['filter']['service_id'])) {
            $query .= " AND w.global_area_id in (SELECT global_area_id FROM services WHERE services.service_id = '{$data['filter']['service_id']}')";
        }
        // return $query;
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        

        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
        
    }

    function addWorker($data){
        $keys = [];
        $values = [];
        if (!empty($data['login']) && !empty($data['password']) &&  !empty($data['first_name']) && !empty($data['middle_name']) && !empty($data['last_name']) && !empty($data['contact'])) {
            $result = $this->con->query("SELECT * FROM workers WHERE login = '{$data['login']}'");
            $tempLogin = [];
            while ($row = $result->fetch_assoc()) {
                array_push($tempLogin, $row);
            }
            if (count($tempLogin) > 0) {
                return false;
            }
            foreach ($data as $key => $value) {
                if ($key == "password") {
                    array_push($keys, $key);
                    array_push($values, "\"".password_hash($value, PASSWORD_DEFAULT)."\"");
                }elseif($key != "token") {
                    array_push($keys, $key);
                    array_push($values, "\"".$value."\"");
                }
            }
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
                    array_push($keys, 'photo');
                    array_push($values, "\"https://new.doro.kz/media/{$new_file_name}\"");
                    
                }else{
                    header("HTTP/1.1 413 file is too big!");
                    return json_encode(false);
                }
            }
            $keyStr = implode(",", $keys);
            $valueStr = implode(",", $values);
            $query = "INSERT INTO `workers`({$keyStr}) VALUES ({$valueStr})";
            // return $query;
            $result = $this->con->query($query);
            if ($result) {
                return json_encode(["last_id"=>$this->con->insert_id]);
            }else{
                header("HTTP/1.1 400 Some fields are ");   
                return json_encode(false);
            
            }
        }else{
            header("HTTP/1.1 400 Some fields are empty");   
            return json_encode(false);
        }

    }

    function getWorker($data){
        if (isset($data['worker_id'])) {
            $query = "SELECT `w`.`worker_id`, `w`.`position_id`, `p`.`title`, `p`.`access_level`,`w`.`login`, `w`.`first_name`, `w`.`middle_name`, `w`.`last_name`, `w`.`contact`, `w`.`idDeleted`, `w`.`gender` `gender_id`, `w`.`date_of_birth`, `w`.`date_of_registration`, `w`.`photo`,
            -- global_areas.name,
            CASE `w`.`gender`
                WHEN '0' THEN 'мужчина'
                WHEN '1' THEN 'женщина'
                END as `gender`
             FROM `workers` `w` LEFT JOIN `possitions` `p` ON `p`.`position_id` = `w`.position_id 
            --  LEFT JOIN global_areas ON global_areas.global_area_id = w.global_area_id
             WHERE `worker_id` = {$data['worker_id']}";
        }else {
            $query = "SELECT `w`.`worker_id`, `w`.`position_id`, `p`.`title`, `p`.`access_level`,`w`.`login`, `w`.`first_name`, `w`.`middle_name`, `w`.`last_name`, `w`.`contact`, `w`.`idDeleted`, `w`.`gender` `gender_id`, `w`.`date_of_birth`, `w`.`date_of_registration`, `w`.`photo`,
            -- global_areas.name,
            CASE `w`.`gender`
                WHEN '0' THEN 'мужчина'
                WHEN '1' THEN 'женщина'
                END as `gender`
            FROM `workers` `w` LEFT JOIN `possitions` `p` ON `p`.`position_id` = `w`.position_id 
            -- LEFT JOIN global_areas ON global_areas.global_area_id = w.global_area_id

            WHERE `worker_id` = {$this->worker_id}";
        }
        $result = $this->con->query($query);
        $workerArr = $result->fetch_assoc();
        $query = "SELECT * FROM `worker_schedule` WHERE worker_id = '{$workerArr['worker_id']}' AND schedule_date >= CURRENT_DATE ORDER BY `schedule_date` ASC LIMIT 10";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        $workerArr['worker_schedule'] = $resultArr;
        $query = "SELECT `ss`.`schedule_id`, `ss`.`service_id`, `ss`.`amount_of_customers`, `ss`.`provider_id`, `ss`.`worker_id`, `ss`.`schedule_date`, `ss`.`time_start`, `ss`.`duration`, `ss`.`log_timestamp`, `ss`.`note`, `ss`.`note_for_customer`, `s`.`name`,  `ss`.`area_id`, `a`.`title`, CONCAT(`wp`.`last_name`, ' ', `wp`.`first_name`) `provider_name`, (SELECT COUNT(*) FROM service_sessions WHERE service_sessions.schedule_id = `ss`.`schedule_id`) amount_of_enrolled, (SELECT title FROM `possitions` WHERE position_id = wp.position_id) position, COUNT(ss.schedule_id) amount_of_services, IF(COUNT(ss.schedule_id) > 1, 'group', 'single') schedule_type,

        (SELECT GROUP_CONCAT(CONCAT(wtemp.last_name, ' ', wtemp.first_name)) FROM service_schedule sstemp  LEFT JOIN workers wtemp ON  sstemp.provider_id = wtemp.worker_id WHERE sstemp.time_start = ss.time_start AND sstemp.schedule_date = ss.schedule_date AND ss.service_id = sstemp.service_id) provider_names, s.color
           FROM `service_schedule` `ss`
                LEFT JOIN services `s` ON `s`.`service_id` = `ss`.`service_id`
                LEFT JOIN areas `a` ON `a`.`area_id` = `ss`.`area_id`
                LEFT JOIN workers `wp` ON `wp`.`worker_id` = `ss`.`provider_id`
                LEFT JOIN workers `w` ON `w`.`worker_id` = `ss`.`worker_id`
        WHERE `ss`.`schedule_date` >= CURRENT_DATE AND `ss`.`provider_id` = '{$workerArr['worker_id']}'  ORDER BY `schedule_date` ASC LIMIT 10
                ";
        
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        $workerArr['service_schedule'] = $resultArr;

        $query = "SELECT subscription_orders.*, (SELECT COUNT(*) FROM service_sessions WHERE reason_order_id = subscription_orders.order_id AND reason_type = 'sub') amount_of_vasted, DATE_ADD(subscription_orders.exploration_date, INTERVAL (SELECT SUM(DATEDIFF(customer_freezings.date_end, customer_freezings.date_start)) FROM customer_freezings WHERE customer_freezings.customer_id = subscription_orders.customer_id AND customer_freezings.date_start >= subscription_orders.date_of_activation) DAY) exploration_date_after_freezing, customers.first_name, customers.last_name,subscriptions.name
        FROM subscription_orders 
        LEFT JOIN customers ON customers.customer_id = subscription_orders.customer_id
        LEFT JOIN subscriptions ON subscriptions.subscription_id = subscription_orders.subscription_id

        WHERE subscription_orders.subscription_id in (SELECT subscriptions.subscription_id FROM subscriptions WHERE subscriptions.provider_id = '{$workerArr['worker_id']}' )  AND DATE(subscription_orders.date_of_activation) <= CURRENT_DATE  AND DATE(subscription_orders.exploration_date) >= CURRENT_DATE ";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        $workerArr['current_customers'] = $resultArr;
        return json_encode($workerArr, JSON_UNESCAPED_UNICODE);
    }

    function editWorker($data){
        $keys = [];
        $values = [];
        // if (!empty($data['login']) && !empty($data['first_name']) && !empty($data['middle_name']) && !empty($data['last_name']) && !empty($data['contact']) && !empty($data['worker_id'])) {
            $query = "UPDATE `workers` SET ";
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
            $query .= " WHERE `worker_id` = '{$data['worker_id']}'";
            // return $query;
            $result = $this->con->query($query);
            if ($result) {
                return json_encode($result);
            }else{
                header("HTTP/1.1 400 check it");   
                return json_encode(false);
            
            }
        // }else{
        //     header("HTTP/1.1 400 Some fields are empty");   
        //     return json_encode(false);
        // }

    }

    function removeWorker($data){
        if (!empty($data['worker_id'])) {
            $query = "UPDATE `workers` SET `idDeleted`=1  WHERE `worker_id` = '{$data['worker_id']}'";
            $result = $this->con->query($query);
            return json_encode($result);
        }
    }

    function addSchedule($data){
        foreach ($data['workers'] as $value) {
            if (!empty($value['worker_id']) && !empty($value['area_id'])) {
                $query = "INSERT INTO `worker_schedule`(`worker_id`, `time_start`, `time_end`, `schedule_date`,area_id) VALUES ('{$value['worker_id']}','{$data['time_start']}', '{$data['time_end']}', '{$data['date']}', '{$value['area_id']}')";
                $result = $this->con->query($query);
            }
        }
        return json_encode($result);
    }

    function getSchedule($data, $worker){
        $query = "SELECT DATE_FORMAT(`ws`.`time_start`, '%H:%i') `time_start`,DATE_FORMAT(`ws`.`time_end`, '%H:%i') `time_end`,`ws`.`schedule_id`, `ws`.`worker_id`, `ws`.`schedule_date`,  CONCAT(`w`.`last_name`, ' ', `w`.`first_name`) `name`, (SELECT title FROM `possitions` WHERE position_id = w.position_id) position, areas.title area_title
        FROM `worker_schedule` `ws`
        LEFT JOIN workers `w` ON `w`.`worker_id` = `ws`.`worker_id`
        LEFT JOIN areas ON ws.area_id = areas.area_id
        WHERE DATE(`ws`.`schedule_date`) >= '{$data['dateStart']}' AND DATE(`ws`.`schedule_date`) <= '{$data['dateEnd']}'";
        if ($worker->is_personal == 1) {
            $query .= " AND ws.worker_id = '{$worker->worker_id}'";
        }
        if (!empty($data['filter']['position_id'])) {
            $query .= " AND w.position_id = '{$data['filter']['position_id']}'";
        }
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr);
    }
}