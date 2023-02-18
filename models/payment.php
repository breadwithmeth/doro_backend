<?php
class Payment{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function addPayment($data, $worker_id){
        $fixedSalaryData = $data['fixed_salary'];
        $salaryRules = $data['salary_rules'];
        $query = "INSERT INTO `payment_rules_fixed`(`provider_id`, `salary`, `salary_per_shift`, `percentage_of_all_sales`, `percentage_of_their_sales`, `tax`, `worker_id`) VALUES ('{$fixedSalaryData['provider_id']}', '{$fixedSalaryData['salary']}', '{$fixedSalaryData['salary_per_shift']}', '{$fixedSalaryData['percentage_of_all_sales']}', '{$fixedSalaryData['percentage_of_their_sales']}', '{$fixedSalaryData['tax']}', '{$data['worker_id']}')";
        $resultFixedSalary = $this->con->query($query);
        if($resultFixedSalary){
            foreach ($salaryRules as $rule) {
                $query = "INSERT INTO `payment_rules`(`service_category_id`, `provider_id`, `worker_id`, `type_of_payment`, `min_amount_of_customers`, `max_amount_of_customers`, `payment`) VALUES ('{$rule['service_category_id']}', '{$data['worker_id']}', '{$worker_id}', '{$rule['type_of_payment']}', '{$rule['min_amount_of_customers']}', '{$rule['max_amount_of_customers']}', '{$rule['payment']}')";
                $this->con->query($query);
            }
            return json_encode(true);

        }else{
            return json_encode(false);
        }
    }

    function getPayment($data){
        
    }

    function getPaymentDetailsForWorker($data, $worker_id){
        if (isset($data['worker_id'])) {
            $worker_id = $data['worker_id'];
        }
        $queryFixed = "SELECT *, (salary_per_shift*amount_of_shifts)salary_for_shifts, (summary_of_sellings_single/100*percentage_of_their_sales)percentage_of_their_sales_salary, (summary_of_sellings/100*percentage_of_all_sales)percentage_of_all_sales_salary FROM (
            SELECT 
            payment_rules_fixed.*,
            ((SELECT COUNT(*) 
             FROM worker_schedule 
             WHERE worker_schedule.worker_id = '{$worker_id}' 
             AND YEAR(worker_schedule.schedule_date) = YEAR(CURRENT_DATE)
             AND MONTH(worker_schedule.schedule_date) = MONTH(CURRENT_DATE)
             )) amount_of_shifts,
             (
             IFNULL((SELECT SUM(items_orders.price) FROM items_orders WHERE 
             YEAR(items_orders.date_of_order) = YEAR(CURRENT_DATE)
             AND MONTH(items_orders.date_of_order) = MONTH(CURRENT_DATE)),0)
             +
             IFNULL((SELECT SUM(subscription_orders.price) FROM subscription_orders WHERE
             YEAR(subscription_orders.date_of_activation) = YEAR(CURRENT_DATE)
             AND MONTH(subscription_orders.date_of_activation) = MONTH(CURRENT_DATE)),0)
             +
             IFNULL((SELECT SUM(recipes_orders.price) FROM recipes_orders WHERE
             YEAR(recipes_orders.order_timestamp) = YEAR(CURRENT_DATE)
             AND MONTH(recipes_orders.order_timestamp) = MONTH(CURRENT_DATE)),0)
             +
             IFNULL((SELECT SUM(services_orders.price) FROM services_orders WHERE
             YEAR(services_orders.order_timestamp) = YEAR(CURRENT_DATE)
             AND MONTH(services_orders.order_timestamp) = MONTH(CURRENT_DATE)),0)
             ) summary_of_sellings,
    		(
             IFNULL((SELECT SUM(items_orders.price) FROM items_orders WHERE 
             YEAR(items_orders.date_of_order) = YEAR(CURRENT_DATE)
             AND MONTH(items_orders.date_of_order) = MONTH(CURRENT_DATE) AND items_orders.worker_id = '{$worker_id}'),0)
             +
             IFNULL((SELECT SUM(subscription_orders.price) FROM subscription_orders WHERE
             YEAR(subscription_orders.date_of_activation) = YEAR(CURRENT_DATE)
             AND MONTH(subscription_orders.date_of_activation) = MONTH(CURRENT_DATE) AND subscription_orders.worker_id = '{$worker_id}'),0)
             +
             IFNULL((SELECT SUM(recipes_orders.price) FROM recipes_orders WHERE
             YEAR(recipes_orders.order_timestamp) = YEAR(CURRENT_DATE)
             AND MONTH(recipes_orders.order_timestamp) = MONTH(CURRENT_DATE) AND recipes_orders.worker_id = '{$worker_id}'),0)
             +
             IFNULL((SELECT SUM(services_orders.price) FROM services_orders WHERE
             YEAR(services_orders.order_timestamp) = YEAR(CURRENT_DATE)
             AND MONTH(services_orders.order_timestamp) = MONTH(CURRENT_DATE) AND services_orders.worker_id = '{$worker_id}'),0)
             ) summary_of_sellings_single
             
             FROM payment_rules_fixed WHERE payment_rules_fixed.worker_id = '{$worker_id}'
            
            ) temp_payment
            LIMIT 1";

        // $queryDyn = "SELECT service_sessions.schedule_id, services.category_id, payment_rules.rule_id, COUNT(service_sessions.schedule_id) amount_of_services,
        // CASE service_categories.is_personal 
        //     WHEN 1 THEN (CASE payment_rules.type_of_payment WHEN 1 THEN (SELECT services.price/100*payment_rules.payment) WHEN 2 THEN payment_rules.payment END)
        //     WHEN 0 THEN (CASE payment_rules.type_of_payment WHEN 2 THEN (SELECT temp_d_payment.payment FROM (SELECT temp_payment.min_amount_of_customers, temp_payment.max_amount_of_customers, temp_payment.payment, temp_payment.service_category_id FROM payment_rules temp_payment WHERE temp_payment.provider_id = '{$worker_id}') temp_d_payment WHERE COUNT(service_sessions.schedule_id) >= temp_d_payment.min_amount_of_customers AND COUNT(service_sessions.schedule_id) <= temp_d_payment.max_amount_of_customers AND temp_d_payment.service_category_id = services.category_id) END)
        // END as payment_per_services
        
        // FROM service_sessions 
        // LEFT JOIN service_schedule ON service_schedule.schedule_id = service_sessions.schedule_id
        // LEFT JOIN services ON services.service_id = service_schedule.service_id
        // LEFT JOIN service_categories ON services.category_id = service_categories.category_id
        // LEFT JOIN payment_rules ON payment_rules.service_category_id = services.category_id AND payment_rules.provider_id = '{$worker_id}'
        // WHERE YEAR(service_sessions.log_timestamp) =  YEAR(CURRENT_DATE) AND MONTH(service_sessions.log_timestamp) =  MONTH(CURRENT_DATE)
        // GROUP BY service_sessions.schedule_id

        $queryDyn = "
        SELECT session_count.*, 
        CASE session_count.is_personal
            WHEN '1' THEN 
                (SELECT CASE payment_rules.type_of_payment
                 WHEN '1' THEN (SELECT (session_count.price / 100) * payment_rules.payment)
                 WHEN '2' THEN payment_rules.payment
                 END as payment_per_service
                 FROM payment_rules WHERE payment_rules.service_category_id = session_count.category_id AND payment_rules.provider_id = '{$worker_id}')
            WHEN '0' THEN
                (SELECT payment_rules.payment FROM payment_rules WHERE payment_rules.service_category_id = session_count.category_id AND payment_rules.provider_id = '{$worker_id}' AND payment_rules.min_amount_of_customers <= session_count.amount_of_sessions  AND payment_rules.max_amount_of_customers >= session_count.amount_of_sessions)
            END as payment
        FROM (SELECT service_schedule.schedule_id, service_categories.is_personal, services.name, services.price, service_categories.category_id,
        (SELECT COUNT(*) 
         FROM service_sessions
         WHERE service_sessions.schedule_id = service_schedule.schedule_id) amount_of_sessions 
        FROM service_schedule 
        LEFT JOIN services ON services.service_id = service_schedule.service_id
        LEFT JOIN service_categories ON service_categories.category_id = services.category_id
        WHERE service_schedule.provider_id = '{$worker_id}') session_count
        
        ";

$queryDynSym = "SELECT  SUM(temp_payment.payment_per_services) sum_dyn FROM 
(SELECT service_sessions.schedule_id, services.category_id, payment_rules.rule_id, COUNT(service_sessions.schedule_id),
CASE service_categories.is_personal 
    WHEN 1 THEN (CASE payment_rules.type_of_payment WHEN 1 THEN (SELECT services.price/100*payment_rules.payment) WHEN 2 THEN payment_rules.payment END)
    WHEN 0 THEN (CASE payment_rules.type_of_payment WHEN 2 THEN (SELECT temp_d_payment.payment FROM (SELECT temp_payment.min_amount_of_customers, temp_payment.max_amount_of_customers, temp_payment.payment, temp_payment.service_category_id FROM payment_rules temp_payment WHERE temp_payment.provider_id = '{$worker_id}') temp_d_payment WHERE COUNT(service_sessions.schedule_id) >= temp_d_payment.min_amount_of_customers AND COUNT(service_sessions.schedule_id) <= temp_d_payment.max_amount_of_customers AND temp_d_payment.service_category_id = services.category_id) END)
END as payment_per_services

FROM service_sessions 
LEFT JOIN service_schedule ON service_schedule.schedule_id = service_sessions.schedule_id
LEFT JOIN services ON services.service_id = service_schedule.service_id
LEFT JOIN service_categories ON services.category_id = service_categories.category_id
LEFT JOIN payment_rules ON payment_rules.service_category_id = services.category_id AND payment_rules.provider_id = '{$worker_id}'
GROUP BY service_sessions.schedule_id) temp_payment


";
        $resultFixed = $this->con->query($queryFixed);
        $resultDyn = $this->con->query($queryDyn);
        $resultDynSym = $this->con->query($queryDynSym);
        $result = [];
        $result['fixed'] = $resultFixed->fetch_assoc();
        $resultArrDyn = [];
        while ($row = $resultDyn->fetch_assoc()) {
            array_push($resultArrDyn, $row);
        }
        $dynSum = 0;
        foreach ($resultArrDyn as $value) {
            $dynSum = $dynSum + $value['payment'];
        }
        $result['dyn']['summary'] = $dynSum;
        $result['dyn']['details'] = $resultArrDyn;
        $result['summary'] = round($result['fixed']['salary'] + $result['fixed']['salary_for_shifts'] + $result['fixed']['percentage_of_their_sales_salary'] + $result['fixed']['percentage_of_all_sales_salary'] + $result['dyn']['summary']['sum_dyn']);
        $result['summary_after_taxes'] = round($result['summary'] -($result['summary'] / 100 * $result['fixed']['tax']));
        return json_encode($result, JSON_UNESCAPED_UNICODE);

    }
    

    function getPaymentDetailsForAllWorkers($data){
        $dataEnd = $data['end'];
        $dataStart = $data['start'];
        $paymentDetails = [];
        $query = "SELECT * FROM `workers` WHERE idDeleted = 0";
        if (!empty($data['filter']['keyword'])) {
            $query .= " AND CONCAT(last_name, \" \", first_name) LIKE '%{$data['filter']['keyword']}%'";
        }
        $result = $this->con->query($query);
        $workers = [];
        while ($row = $result->fetch_assoc()) {
            array_push($workers, $row);
        }
        foreach ($workers as $value) {
            $worker_id = $value['worker_id'];

        $queryFixed = "SELECT *, (salary_per_shift*amount_of_shifts)salary_for_shifts, (summary_of_sellings_single/100*percentage_of_their_sales)percentage_of_their_sales_salary, (summary_of_sellings/100*percentage_of_all_sales)percentage_of_all_sales_salary FROM (
            SELECT 
            payment_rules_fixed.*,
            ((SELECT COUNT(*) 
             FROM worker_schedule 
             WHERE worker_schedule.worker_id = '{$worker_id}' 
             AND worker_schedule.schedule_date <= '{$dataEnd}' 
             AND worker_schedule.schedule_date >= '{$dataStart}'

             )) amount_of_shifts,
             (
             IFNULL((SELECT SUM(items_orders.price) FROM items_orders WHERE 
             items_orders.date_of_order <= '{$dataEnd}' AND items_orders.date_of_order >= '{$dataStart}'),0)
             +
             IFNULL((SELECT SUM(subscription_orders.price) FROM subscription_orders WHERE
             
             subscription_orders.date_of_activation <= '{$dataEnd}' AND subscription_orders.date_of_activation >= '{$dataStart}'),0)
             +
             IFNULL((SELECT SUM(recipes_orders.price) FROM recipes_orders WHERE
             recipes_orders.order_timestamp <= '{$dataEnd}' AND recipes_orders.order_timestamp >= '{$dataStart}'),0)
             +
             IFNULL((SELECT SUM(services_orders.price) FROM services_orders WHERE
             services_orders.order_timestamp >= '{$dataStart}' AND services_orders.order_timestamp <= '{$dataEnd}'),0)
             ) summary_of_sellings,
    		(
             IFNULL((SELECT SUM(items_orders.price) FROM items_orders WHERE 
             items_orders.date_of_order <= '{$dataEnd}' AND items_orders.date_of_order >= '{$dataStart}' AND items_orders.worker_id = '{$worker_id}'),0)
             +
             IFNULL((SELECT SUM(subscription_orders.price) FROM subscription_orders WHERE
             subscription_orders.date_of_activation <= '{$dataEnd}' AND subscription_orders.date_of_activation >= '{$dataStart}' AND subscription_orders.worker_id = '{$worker_id}'),0)
             +
             IFNULL((SELECT SUM(recipes_orders.price) FROM recipes_orders WHERE
             recipes_orders.order_timestamp <= '{$dataEnd}' AND recipes_orders.order_timestamp >= '{$dataStart}' AND recipes_orders.worker_id = '{$worker_id}'),0)
             +
             IFNULL((SELECT SUM(services_orders.price) FROM services_orders WHERE
             services_orders.order_timestamp >= '{$dataStart}' AND services_orders.order_timestamp <= '{$dataEnd}' AND services_orders.worker_id = '{$worker_id}'),0)
             ) summary_of_sellings_single
             
             FROM payment_rules_fixed WHERE payment_rules_fixed.worker_id = '{$worker_id}'
            
            ) temp_payment
            LIMIT 1";
            $queryDynSym = "SELECT  SUM(temp_payment.payment_per_services) sum_dyn FROM 
            (SELECT service_sessions.schedule_id, services.category_id, payment_rules.rule_id, COUNT(service_sessions.schedule_id),
            CASE service_categories.is_personal 
                WHEN 1 THEN (CASE payment_rules.type_of_payment WHEN 1 THEN (SELECT services.price/100*payment_rules.payment) WHEN 2 THEN payment_rules.payment END)
                WHEN 0 THEN (CASE payment_rules.type_of_payment WHEN 2 THEN (SELECT temp_d_payment.payment FROM (SELECT temp_payment.min_amount_of_customers, temp_payment.max_amount_of_customers, temp_payment.payment, temp_payment.service_category_id FROM payment_rules temp_payment WHERE temp_payment.provider_id = '{$worker_id}') temp_d_payment WHERE COUNT(service_sessions.schedule_id) >= temp_d_payment.min_amount_of_customers AND COUNT(service_sessions.schedule_id) <= temp_d_payment.max_amount_of_customers AND temp_d_payment.service_category_id = services.category_id) END)
            END as payment_per_services
            
            FROM service_sessions 
            LEFT JOIN service_schedule ON service_schedule.schedule_id = service_sessions.schedule_id
            LEFT JOIN services ON services.service_id = service_schedule.service_id
            LEFT JOIN service_categories ON services.category_id = service_categories.category_id
            LEFT JOIN payment_rules ON payment_rules.service_category_id = services.category_id AND payment_rules.provider_id = '{$worker_id}'
            WHERE service_schedule.schedule_date <= '{$dataEnd}' AND service_schedule.schedule_date >= '{$dataStart}'
            GROUP BY service_sessions.schedule_id) temp_payment
            
            
            ";
        $query = "SELECT *, ROUND(percentage_of_all_sales_salary + percentage_of_their_sales_salary + salary_for_shifts + salary + sum_dyn) summary_before_taxes, 
        ROUND((percentage_of_all_sales_salary + percentage_of_their_sales_salary + salary_for_shifts + salary + sum_dyn) - (((percentage_of_all_sales_salary + percentage_of_their_sales_salary + salary_for_shifts + salary + sum_dyn)/100)*tax)) summary_after_taxes  FROM ({$queryDynSym}) dyn_sum, ({$queryFixed}) fixed_sum";
        $result = $this->con->query($query);
            $payment = $result->fetch_assoc();
            if ($payment == null) {
                array_push($paymentDetails, ["worker_id" => $worker_id, "worker_name" =>$value['last_name']." ".$value['first_name'],"error" => "недостаточно данных для подсчета зарплаты"]);
            }else {
                $payment['worker_name'] = $value['last_name'] . " " . $value['first_name'];
                array_push($paymentDetails, $payment);
                
            }

    }


        return json_encode($paymentDetails, JSON_UNESCAPED_UNICODE);
    }

    function paySalary($data, $worker_id){
        $query = "INSERT INTO `payments`(`provider_id`, `worker_id`, `amount`, `payment_date`, `note`) VALUES ('{$data['worker_id']}', '{$worker_id}', '{$data['amount']}', '{$data['payment_date']}', '{$data['note']}')";
        $result = $this->con->query($query);
        return json_encode($result);
    }
}













