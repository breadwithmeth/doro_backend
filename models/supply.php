<?php
class Supply{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    
    public function getSupplies($data){
        $query = "SELECT * FROM `supplies`";
    }
 
    public function addSupplies($data, $worker_id){
        $query = "INSERT INTO `supplies`(`item_id`, `worker_id`, `price_of_purchase`, `amount`, `supplier_id`) VALUES ('{$data['item_id']}', '{$worker_id}', '{$data['price_of_purchase']}', '{$data['amount']}', '{$data['supplier_id']}')";
        $result = $this->con->query($query);
        return json_encode($result);
    }
}