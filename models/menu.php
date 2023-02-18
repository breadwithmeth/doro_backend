<?php
class Menu{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function getMenu($worker_id){
        $layout = [];
        $menu = [];
        $menu_categories = [];
        $query = "SELECT * FROM menu_categories";
        $result = $this->con->query($query);
        while ($row = $result->fetch_assoc()) {
            array_push($menu_categories, $row);
        }
        foreach ($menu_categories as $category) {
            $menu_items = [];
            $menu_section = [];
            $query = "SELECT menu_items.* FROM menu_workers LEFT JOIN menu_items ON menu_items.menu_item_id = menu_workers.menu_item_id WHERE menu_items.menu_category_id = '{$category['menu_category_id']}' AND menu_workers.position_id = (SELECT position_id FROM workers WHERE worker_id = '{$worker_id}') GROUP BY menu_items.menu_item_id";
            $result = $this->con->query($query);
            while ($row = $result->fetch_assoc()) {
                array_push($menu_items, $row);
            }
            if (!empty($menu_items)) {
                $menu_section['category_name'] = $category['name'];
                $menu_section['menu_items'] = $menu_items;
                array_push($menu, $menu_section);
            }
        }
        $layout['menu'] = $menu;
        $query = "SELECT organizations.* FROM workers LEFT JOIN organizations ON organizations.organization_id = workers.organization_id WHERE workers.worker_id = '{$worker_id}'";
        $layout['navbar'] = $this->con->query($query)->fetch_assoc();
        return json_encode($layout);
    }

    function getMenuItems(){
        $query = "SELECT * FROM menu_items";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }
}