<?php
class News{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function createNews($data, $worker_id){
        $query = "INSERT INTO `news`(`title`, `worker_id`, `cover`) VALUES ( '{$data['title']}','{$worker_id}', '{$data['cover']}')";
        $result = $this->con->query($query);
        if ($result) {
            $news_id = $this->con->insert_id;
            foreach ($data['sections'] as $section) {
                $query = "INSERT INTO `news_parts`(`section`, `news_id`, `section_order`, `type`) VALUES ('{$section['section']}', '{$news_id}', '{$section['section_order']}', '{$section['type']}')";
                $this->con->query($query);
            }
        }
        return json_encode($result);
    }

    function getNews($data){
        if (!empty($data['news_id'])) {
            $query = "SELECT * FROM news WHERE news_id = '{$data['news_id']}'";
            $news = $this->con->query($query)->fetch_assoc();
            $query = "SELECT * FROM news_parts WHERE news_id = '{$data['news_id']}' ORDER BY section_order";
            $result = $this->con->query($query);
            $newsTemp = [];
            while ($row = $result->fetch_assoc()) {
                array_push($newsTemp, $row);
            }
            $news['sections'] = $newsTemp;
            return json_encode($news, JSON_UNESCAPED_UNICODE);
        }
        $resultArr = [];
        $query = "SELECT *, LEFT((SELECT news_parts.section FROM news_parts WHERE news_parts.news_id = n.news_id AND news_parts.type = 'text' LIMIT 1),100) description FROM `news` `n` ORDER BY `news_timestamp` DESC";
        $result = $this->con->query($query);
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

    function uploadMedia($worker_id){
        // var_dump($_FILES);
        $target_dir = $_SERVER["DOCUMENT_ROOT"] . "media/";
        $target_dir = "../../media/";
                    $file = $_FILES['media']['name'];
                    $path = pathinfo($file);
                    $filename = $path['filename'];
                    $ext = $path['extension'];
                    $temp_name = $_FILES['media']['tmp_name'];
                    $new_file_name = uniqid().".".$ext;
                    $path_filename_ext = $target_dir.$new_file_name;
                    move_uploaded_file($temp_name,$path_filename_ext);

        $query = "INSERT INTO `news_media`( `name`, `path`, `worker_id`) VALUES ('{$filename}','{$new_file_name}','{$worker_id}')";
        $result = $this->con->query($query);
        return json_encode($result);            
         
    }

    function getNewsMedia(){
        $query = "SELECT `media_id`, `name`, CONCAT('https://new.doro.kz/media/',path) path, `worker_id` FROM `news_media` ORDER BY media_id DESC";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
    }

}