<?php
class metric_unit{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    
    function add($data, $worker_id){
        $query = "INSERT INTO `metric_units`(`name`) VALUES ('{$data['name']}')";
        $result = $this->con->query($query);
        return json_encode($result);

    }

    function get(){
        $resultArr = [];
        $query = "SELECT * FROM `metric_units`";
        $result = $this->con->query($query);
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);

    }

}