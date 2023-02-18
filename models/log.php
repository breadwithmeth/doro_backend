<?php
class Log{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    function addLog($worker_id, $item_id, $action){
        $query = "INSERT INTO `items_log`(`worker_id`, `item_id`, `action`) VALUES ($worker_id, $item_id, $action)";
        $this->con->query($query);
    }

}