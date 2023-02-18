<?php
class Transaction{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function getTransactions($data){
        
        $resultTransacations = [];
        $query = "
        SELECT orders.*, CONCAT(workers.last_name, ' ', workers.first_name) worker_name, CONCAT(customers.last_name, ' ', customers.first_name) customer_name, customers.customer_id FROM 
       (SELECT items_orders.amount amount, items_orders.price price, items_orders.date_of_order as `date`, items_orders.customer_id customer_id, items_orders.worker_id worker_id, items.name `name`, 'продажа товара' `type`, (items_orders.price * items_orders.amount) summary
       FROM items_orders
       LEFT JOIN items ON items.item_id = items_orders.item_id
       UNION ALL
       SELECT recipes_orders.amount amount,recipes_orders.price price, recipes_orders.order_timestamp as `date`, recipes_orders.customer_id customer_id, recipes_orders.worker_id worker_id, recipes.title `name`, 'продажа рецепта' `type`,  (recipes_orders.amount*recipes_orders.price) summary
       FROM recipes_orders
       LEFT JOIN recipes ON recipes.recipe_id = recipes_orders.recipe_id
       UNION ALL
       SELECT '1' amount, services_orders.price price, services_orders.order_timestamp as `date`, services_orders.customer_id customer_id, services_orders.worker_id worker_id, services.name `name`, 'продажа услуги' `type`, (services_orders.price * 1) summary
       FROM services_orders
       LEFT JOIN services ON services.service_id = services_orders.service_id
       WHERE is_visited = 1
       UNION ALL
       SELECT '1' amount, subscription_orders.price price, subscription_orders.order_timestamp as `date`, subscription_orders.customer_id customer_id, subscription_orders.worker_id worker_id, subscriptions.name `name`, 'продажа абонемента' `type`, 
       (subscription_orders.price*1) summary
       FROM subscription_orders
       LEFT JOIN subscriptions ON subscription_orders.subscription_id = subscriptions.subscription_id
        UNION ALL
       SELECT '1' as amount, customer_balance.price as price, customer_balance.log_timestamp as `date`, customer_balance.customer_id customer_id, customer_balance.worker_id worker_id, ' ' `name`, 'пополнение баланса' `type`,  (customer_balance.price*1) summary
       FROM customer_balance
       
       ) orders
       LEFT JOIN workers ON workers.worker_id = orders.worker_id
       LEFT JOIN customers ON customers.customer_id = orders.customer_id 
        WHERE DATE(`date`) >= '{$data['dateStart']}' AND DATE(`date`) <= '{$data['dateEnd']}'
        
        ";
        if (!empty($data['customer_id'])) {
            $query .= " AND orders.customer_id = '{$data['customer_id']}'";
        }
        if (!empty($data['worker_id'])) {
            $query .= " AND orders.worker_id = '{$data['worker_id']}'";
        }
        $query .= " ORDER BY `orders`.`date` DESC";
        $result = $this->con->query($query);
        $resultArr = [];
        while ($row = $result->fetch_assoc()) {
            array_push($resultArr, $row);
        }
        $resultTransacations['transactions'] = $resultArr;
        return json_encode($resultTransacations, JSON_UNESCAPED_UNICODE);
    }
    
}