<?php
class Supplier{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    
    public function getSupplies($data){
        $query = "SELECT * FROM `suppliers` ";
        $result = $this->con->query($query);
        $resuktArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resuktArr,$row);
        }
        return json_encode($resuktArr, JSON_UNESCAPED_UNICODE);
    }
 
    public function addSupplies($data, $worker_id){
        $query = "INSERT INTO `suppliers`(`name`, `contact`, `address`) VALUES ('{$data['name']}','{$data['contact']}','{$data['address']}')";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function editSupplier($data){
        $query = "UPDATE `suppliers` SET `name`='{$data['name']}',`contact`='{$data['contact']}',`address`='{$data['address']}' WHERE supplier_id = '{$data['supplier_id']}'";
        $result = $this->con->query($query);
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}