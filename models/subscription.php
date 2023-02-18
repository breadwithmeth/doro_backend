<?php
class Subscription{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function addSubscription($data, $worker_id){
        $query = "INSERT INTO `subscriptions`(`worker_id`, `price`, `amount_of_visits`, `validity`, `unlimited`, `name`, `organization_id`, is_personal, provider_id) VALUES ('{$worker_id}', '{$data['price']}', '{$data['amount_of_visits']}', '{$data['validity']}', '{$data['unlimited']}', '{$data['name']}', '{$data['organization_id']}', '{$data['is_personal']}', '{$data['provider_id']}' )";
        // return $query;
        $result = $this->con->query($query);
        if ($result) {
            $subscription_id = $this->con->insert_id;
            if (isset($data['allowed_services'])) {
                foreach ($data['allowed_services'] as $value) {
                    $this->con->query("INSERT INTO `subscription_services`(`subscription_id`, `service_id`) VALUES ('{$subscription_id}', '{$value}')");
                }              
            }
            if (isset($data['allowed_organization'])) {
                foreach ($data['allowed_organization'] as $value) {
                    $this->con->query("INSERT INTO  `subscription_organizations`(`subscription_id`, `organization_id`) VALUES ('{$subscription_id}', '{$value}')");
                }              
            }
            if (isset($data['allowed_areas'])) {
                foreach ($data['allowed_areas'] as $value) {
                    $this->con->query("INSERT INTO `subscription_areas`(`subscription_id`, `area_id`) VALUES ('{$subscription_id}', '{$value}')");
                }              
            }
            return json_encode($result);
        }
    }

    function getSubscription($data){
        if (!empty($data['subscription_id'])) {
            $query = "SELECT * FROM subscriptions WHERE subscription_id = '{$data['subscription_id']}'";
            $subscription = $this->con->query($query)->fetch_assoc();
            $query = "SELECT ss.*, s.name service, sc.name category FROM subscription_services  ss 
            LEFT JOIN services s ON s.service_id = ss.service_id
            LEFT JOIN service_categories sc ON sc.category_id = s.category_id
            WHERE ss.subscription_id = '{$data['subscription_id']}'
            ";
            $result = $this->con->query($query);
            $services = [];
            while ($row = $result->fetch_assoc()) {
                array_push($services, $row);

            }
            $subscription['services'] = $services;
            return json_encode($subscription, JSON_UNESCAPED_UNICODE);

        }else{
        
            $query = "SELECT * FROM subscriptions Where isDeleted = 0";
            $result = $this->con->query($query);
            $resultArr = [];
            while ($row = $result->fetch_assoc()) {
                array_push($resultArr, $row);
            }
            return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
        
        }
    }

    function sellSubscription($data, $worker){
        $query = "SELECT * FROM subscriptions WHERE subscription_id = '{$data['subscription_id']}'";
        $subsciption = $this->con->query($query)->fetch_assoc();

        $query = "INSERT INTO `subscription_orders`(`subscription_id`, `customer_id`, `worker_id`, `price`, `amount_of_visits`, `unlimited`, `date_of_activation`, `exploration_date`) VALUES ('{$data['subscription_id']}', '{$data['customer_id']}', '{$worker->worker_id}', '{$subsciption['price']}', '{$subsciption['amount_of_visits']}', '{$subsciption['unlimited']}',CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL {$subsciption['validity']} DAY))";
        $result = $this->con->query($query);
        return json_encode($result);
    }
    
    function preSellSubscription($data, $worker){
        $query = "SELECT * FROM subscriptions WHERE subscription_id = '{$data['subscription_id']}'";
        $subsciption = $this->con->query($query)->fetch_assoc();

        $query = "INSERT INTO `subscription_orders`(`subscription_id`, `customer_id`, `worker_id`, `price`, `amount_of_visits`, `unlimited`, `date_of_activation`, `exploration_date`) VALUES ('{$data['subscription_id']}', '{$data['customer_id']}', '{$worker->worker_id}', '{$subsciption['price']}', '{$subsciption['amount_of_visits']}', '{$subsciption['unlimited']}','{$data['date_of_activation']}', DATE_ADD('{$data['date_of_activation']}', INTERVAL {$subsciption['validity']} DAY))";
        $result = $this->con->query($query);
        return json_encode($result);
    }

    function removeSubscription($data){
        $query = "UPDATE subscriptions SET isDeleted = 1 WHERE subscription_id = '{$data['subscription_id']}'";
        $ramil = $this->con->query($query);
        return json_encode($ramil);
        
    
    }
    
    function getSubscriptionsCustomer($data, $customer_id){
        $query = "SELECT subscription_orders.*, subscriptions.name, (SELECT COUNT(*) FROM service_sessions WHERE reason_order_id = subscription_orders.order_id AND reason_type = \"sub\") amount_of_vasted, DATE_ADD(subscription_orders.exploration_date, INTERVAL (SELECT SUM(DATEDIFF(customer_freezings.date_end, customer_freezings.date_start)) FROM customer_freezings WHERE customer_freezings.customer_id = '{$customer_id}' AND customer_freezings.date_start >= subscription_orders.date_of_activation) DAY) exploration_date_after_freezing
        FROM `subscription_orders`
        LEFT JOIN subscriptions ON subscription_orders.subscription_id = subscriptions.subscription_id
        WHERE subscription_orders.customer_id = '{$customer_id}' AND DATE(subscription_orders.date_of_activation) <= CURRENT_DATE  AND DATE(subscription_orders.exploration_date) >= CURRENT_DATE 
        ";
        $subs = [];
        $result = $this->con->query($query);
        while ($row = $result->fetch_assoc()) {
            array_push($subs, $row);
        }
        return json_encode($subs, JSON_UNESCAPED_UNICODE);
    }

}