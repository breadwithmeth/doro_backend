<?php
class Organization{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function getOrganizations(){
        $query = "SELECT * FROM `organizations`";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }
    
}