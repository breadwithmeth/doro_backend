<?php



//         // create curl resource
//         $ch = curl_init();

//         // set url
//         curl_setopt($ch, CURLOPT_URL, "https://random-word-api.herokuapp.com/word?number=200");

//         //return the transfer as a string
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

//         // $output contains the output string
//         $output = curl_exec($ch);

//         // close curl resource to free up system resources
//         curl_close($ch); 
//         $wordArray =  json_decode($output);
//   // DB Params
//    $user = 'doro';
//    $password = '5N6x1X3u';
//    $database = 'dorodb';
//    $host = 'localhost';
//    $port = 8889;
//    $conn;
//     $conn = null;
//     $conn = new mysqli($host, $user, $password, $database);
//     $conn->set_charset("utf8mb4");
//     $query = "INSERT INTO `items` (`vendor_code`, `name`, `category_id`, `price`, `in_stock`) VALUES";
//     foreach ($wordArray as $value) {
        
//         $vendor_code = rand(100, 1000);
//         $cat = rand(0, 5);
//         $price = rand(0, 10) * 100;
//         $st = rand(100, 200);
//         $query .= "( {$vendor_code}, '{$value}', {$cat}, {$price}, {$st}), ";
//     }
//     echo $query;

//     $conn->query($query);

    
  

