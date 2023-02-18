<?php
class Product{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    function getProducts($data){
        if (isset($data['product_id'])) {
            $query = "SELECT * FROM `prod` WHERE `id` = {$data['product_id']}";
            $result = $this->con->query($query);
            $result = $result->fetch_assoc();
            return json_encode($result);

        }
        if (isset($data['filter-data'])) {
            $query = "SELECT * FROM `prod` WHERE `name` LIKE '{$data['filter-data']}%' OR `art` = '{$data['filter-data']}' OR  `post` LIKE '{$data['filter-data']}%'";
        }else{
            $query = "SELECT * FROM `prod`";
        }
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr);
    }



    function addProduct($data){
        $query = "INSERT INTO `prod`(`art`, `name`, `ed`, `kol`, `price`, `zakup`, `post`, `prim`, `viz`) VALUES ('{$data['articul']}', '{$data['name']}', '{$data['ed']}','{$data['amount']}','{$data['price']}','{$data['purchase_price']}','{$data['supplier']}', '{$data['note']}', 0)";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function editProduct($data){
        $query = "UPDATE `prod` SET `art`='{$data['articul']}',`name`='{$data['name']}',`ed`='{$data['ed']}',`kol`='{$data['amount']}',`price`='{$data['price']}',`zakup`='{$data['purchase_price']}',`post`='{$data['supplier']}',`prim`='{$data['note']}' WHERE `id` = {$data['product_id']}";
        $result = $this->con->query($query);
        return json_encode($result);
    }


}