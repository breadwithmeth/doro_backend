<?php
class Category{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }
    
    function addCategory($data){
        if (isset($data['title'])) {
            $query = "INSERT INTO `product_categories`(`title`) VALUES ('{$data['title']}')";
            $result = $this->con->query($query);
            return json_encode($result);
        }else{
            header("HTTP/1.1 400 Bad request");
            return json_encode(false);
        }
    }
    
    function getCategories($data, $worker){
        if ($worker->is_personal) {
            $query = "SELECT `category_id` `id`, `title` FROM `product_categories` WHERE category_id in(SELECT category_id FROM workers_categories_relations WHERE position_id = '{$worker->position_id}')";

        }else{
        
            $query = "SELECT `category_id` `id`, `title` FROM `product_categories`";
        }
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }


    function addAccessCategory($data){
        $postion_id = $data['position_id'];
        if (!empty($data['add'])) {
            foreach ($data['add'] as $value) {
                $query = "INSERT INTO `workers_categories_relations`(`position_id`, `category_id`) VALUES ('{$postion_id}', '{$value}')";
                $this->con->query($query);
            }
        }
        if (!empty($data['delete'])) {
            foreach ($data['delete'] as $value) {
                $query = "DELETE FROM `workers_categories_relations` WHERE relation_id = '{$value}'";
                $this->con->query($query);
            }
        }
        return json_encode(true);
    }
    function removeAccessCategory($data){
        if (isset($data['relation_id'])) {
            $query = "UPDATE `workers_categories_relations` SET `has_access`=0, `access_denied_date`= CURRENT_TIMESTAMP WHERE `relation_id` = {$data['relation_id']}";
            $result = $this->con->query($query);
            return json_encode($result, JSON_UNESCAPED_UNICODE);
        }else{
            header("HTTP/1.1 400 try again");
            return json_encode("Я для кого блять синтаксис в документе написал",JSON_UNESCAPED_UNICODE);
        }
    }

    function getAccessCategories($data){
        $query = "SELECT `r`.`relation_id` `relation_id`, `c`.`title` category, `r`.`position_id` `position_id`, `c`.`category_id`, p.title position
        FROM `workers_categories_relations` `r` 
        LEFT JOIN `product_categories` `c` on `r`.`category_id` = `c`.`category_id`
        LEFT JOIN `possitions` `p` on `p`.`position_id` = `r`.`position_id`
        ";
        if (!empty($data['position_id'])) {
            $query .= " WHERE r.position_id = '{$data['position_id']}'";
        }
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);

    }


    function editCategory($data){
        if (isset($data['title']) && isset($data['id'])) {
            $query = "UPDATE `product_categories` SET `title`='{$data['title']}' WHERE `category_id` = {$data['id']}";
            $result = $this->con->query($query);
            return json_encode($result);
        }else {
            header("HTTP/1.1 400 Bad request");
            return json_encode(false);
        }
    }

    function getCategory($data){
        $resultArr = [];
        $query = "SELECT * FROM `product_categories` WHERE `category_id` = {$data['category_id']}";
        $result = $this->con->query($query);
        $result = $result->fetch_assoc();
        $resultArr['title']=$result['title'];
        $query = "SELECT * FROM `workers` WHERE `position_id` IN(SELECT `position_id` FROM `workers_categories_relations` WHERE `category_id` = {$data['category_id']})";
        $result = $this->con->query($query);
        $resultWorkers = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultWorkers, ['worker_id'=>$row['worker_id'], 'worker_name'=>$row['first_name']]);
        }
        $resultArr['workers']= $resultWorkers;
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);

    }
}