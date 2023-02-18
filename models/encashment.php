<?php
class Encashment{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    
    function add($data, $worker_id){
        if ($data['type'] == 'inc') {
            $query = "INSERT INTO `encashment`(`worker_id`, `amount`, `operation`, `note`) VALUES ('{$worker_id}', '{$data['amount']}', '{$data['operation']}', '{$data['note']}')";
        }elseif ($data['type'] == 'dec') {
            $query = "INSERT INTO `encashment`(`worker_id`, `amount`, `operation`, `note`) VALUES ('{$worker_id}', '-{$data['amount']}', '{$data['operation']}', '{$data['note']}')";

        }
        $result = $this->con->query($query);
        return json_encode($result);

    }

    function get(){
        $resultArr = [];
        $result = $this->con->query("SELECT SUM(`amount`) `sum` FROM `encashment` ");
        $total = $result->fetch_assoc();
        $resultArr['total'] = $total['sum'];
        $result = $this->con->query("SELECT *, (SELECT CONCAT(last_name, ' ', first_name) name FROM workers WHERE worker_id = encashment.worker_id) name FROM `encashment` ORDER BY `encashment`.`encashment_id` DESC");
        $enhs = [];
        while ($row = $result->fetch_assoc()) {
            array_push($enhs, $row);
        }
        $resultArr['encashment'] = $enhs;
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);

    }

}