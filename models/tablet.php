<?php
class Tablet{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function addTablet($data, $worker_id){
        $query = "INSERT INTO `worker_tabler`(`worker_id`, `tablet_uuid`) VALUES ('{$worker_id}', '{$data['uuid']}')";
        $result = $this->con->query($query);
        return $result;
    }

    function sendQR($type, $uuid, $worker_id){
        $query = "SELECT * FROM `worker_tabler` WHERE `worker_id` = '{$worker_id}' ORDER BY `relation_id` DESC LIMIT 1";
        $result = $this->con->query($query);
        $row = $result->fetch_assoc();
        $tablet = $row['tablet_uuid'];

        $content = array(
            "en" => $type
            );
        
        $fields = array(
            'app_id' => "084f7684-70b9-440a-b775-68de73f2680a",
            //'include_player_ids' => array("e3a3f3f1-dff5-402d-aea8-c3a9f15992d6"),
            'include_player_ids' => array($tablet),

            'data' => array("type" => $type, "uuid"=>"{$uuid}"),
            'contents' => $content
        );
        
        $fields = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
}