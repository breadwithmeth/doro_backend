<?php
class Position{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function getPositions(){
        $query = "SELECT * FROM `possitions`";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function addPosition($data){
        if (!empty($data['title'])) {
            $query = "INSERT INTO `possitions`(`title`, `is_personal`) VALUES ('{$data['title']}', '{$data['is_personal']}')";
            $result = $this->con->query($query);
            if ($result) {
                $last_id = $this->con->insert_id;
                if (!empty($data['menu_items'])) {
                    foreach ($data['menu_items'] as $menu_item) {
                        $query = "INSERT INTO `menu_workers`(`menu_item_id`, `position_id`) VALUES ('{$menu_item}', '{$last_id}')";
                        $this->con->query($query);
                    }
                }
            }
            return json_encode($result);
        }
    }

    function editPosition($data){
        if (isset($data['position_id']) && isset($data['title'])) {
            $query = "UPDATE `possitions` SET `title`='{$data['title']}', is_personal = '{$data['is_personal']}' WHERE `position_id` = '{$data['position_id']}'";
            $result = $this->con->query($query);
            if ($result) {
                if (!empty($data['menu_relations_delete'])) {
                    foreach ($data['menu_relations_delete'] as $relation_id) {
                        $query = "DELETE FROM `menu_workers` WHERE `relation_id` = '{$relation_id}'";
                        $this->con->query($query);
                    }
                }
                if (!empty($data['menu_relations_add'])) {
                    foreach ($data['menu_relations_add'] as $menu_item_id) {
                        $query = "INSERT INTO `menu_workers`(`menu_item_id`, `position_id`) VALUES ('{$menu_item_id}', '{$data['position_id']}')";
                        $this->con->query($query);
                    }
                }
            }
            return json_encode($result);
        }else {
            header("HTTP/1.1 400 Some fields are empty");   
            return json_encode(false);
        }
    }

    function deletePosition($data){
        if (isset($data['position_id'])) {
            $query = "DELETE FROM `possitions` WHERE `position_id` = '{$data['position_id']}'";
            $result = $this->con->query($query);
            return json_encode($result);

        }else {
            header("HTTP/1.1 400 Some fields are empty");   
            return json_encode(false);
        }
    }

    function getPosition($data){
        $query = "SELECT position_id, title FROM possitions WHERE position_id = '{$data['position_id']}'";
        $position = $this->con->query($query)->fetch_assoc();
        $query = "SELECT mw.relation_id, mw.menu_item_id, mi.name  FROM `menu_workers` mw 
        LEFT JOIN menu_items mi ON mi.menu_item_id = mw.menu_item_id
        WHERE mw.position_id = '{$data['position_id']}'";
        $result = $this->con->query($query);
        $relations = [];
        while ($row = $result->fetch_assoc()) {
            array_push($relations, $row);
        }
        
        $position['relation'] = $relations;
        return json_encode($position, JSON_UNESCAPED_UNICODE);
    }

}