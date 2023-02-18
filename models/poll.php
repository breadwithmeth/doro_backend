<?php
class Poll{
    private $con;
    private $table = "";
    function __construct($db) {
        $this->con = $db;
    }

    function createPoll($data, $worker_id){
        $query = "INSERT INTO `polls`(`name`, `description`, `worker_id`) VALUES ('{$data['name']}','{$data['description']}','{$worker_id}')";
        $result = $this->con->query($query);
        if($result){
            $poll_id = $this->con->insert_id;
            foreach ($data['questions'] as $question) {
                $query = "INSERT INTO `poll_questions`(`title`, `description`, `poll_id`) VALUES ('{$question['title']}','{$question['description']}','{$poll_id}')";
                $resultQuestion = $this->con->query($query);
                if ($resultQuestion) {
                    $question_id = $this->con->insert_id;
                    foreach ($question['answers'] as $answer) {
                        $query = "INSERT INTO `poll_answers`(`title`, `description`, `question_id`) VALUES ('{$answer['title']}','{$answer['description']}','{$question_id}')";
                        $resultAnswer = $this->con->query($query);
                    }
                }
            }
        }
        
    }

    function getPolls($data){
        if (isset($data['poll_id'])) {
            $query = "SELECT * FROM `polls` WHERE poll_id = '{$data['poll_id']}'";
            $poll = $this->con->query($query)->fetch_assoc();
            $query = "SELECT * FROM poll_questions WHERE poll_id = '{$data['poll_id']}'";

            $result = $this->con->query($query);
            $questions = [];

            while ($row=$result->fetch_assoc()) {
                array_push($questions, $row);
            }

            foreach ($questions as $key => $value) {
                $query = "SELECT *, (SELECT COUNT(*) FROM poll_answers_customers WHERE poll_answers_customers.answer_id = poll_answers.answer_id) amount_of_answers FROM poll_answers WHERE question_id = '{$value['question_id']}'";
                $result = $this->con->query($query);
                $answers = [];
                while ($row=$result->fetch_assoc()) {
                    array_push($answers, $row);
                }
                $questions[$key]['answers'] = $answers;

            }
            $poll['questions'] = $questions;
            return json_encode($poll, JSON_UNESCAPED_UNICODE);

        }else {
            
            $query = "SELECT * FROM `polls` ";
            $result = $this->con->query($query);
            $resultArr = [];
            while ($row = $result->fetch_assoc()) {
                array_push($resultArr, $row);
            }
            return json_encode($resultArr, JSON_UNESCAPED_UNICODE);
        }
    }

    function answerPoll(){
        
    }

    function getPollCustomer($data, $customer_id){
        $query = "SELECT * 
        FROM polls 
        WHERE poll_id NOT IN(SELECT  poll_questions.poll_id
        FROM poll_answers_customers 
        LEFT JOIN poll_answers ON poll_answers.answer_id = poll_answers_customers.answer_id 
        LEFT JOIN poll_questions ON poll_questions.question_id = poll_answers.question_id 
        WHERE poll_answers_customers.customer_id = '{$customer_id}') AND polls.log_timestamp > (SELECT customers.date_of_registration FROM customers WHERE customers.customer_id = '{$customer_id}')
        ORDER BY poll_id DESC
        LIMIT 1";
        $poll = $this->con->query($query)->fetch_assoc();
        $query = "SELECT * FROM poll_questions WHERE poll_id = '{$poll['poll_id']}'";

            $result = $this->con->query($query);
            $questions = [];

            while ($row=$result->fetch_assoc()) {
                array_push($questions, $row);
            }

            foreach ($questions as $key => $value) {
                $query = "SELECT *, (SELECT COUNT(*) FROM poll_answers_customers WHERE poll_answers_customers.answer_id = poll_answers.answer_id) amount_of_answers FROM poll_answers WHERE question_id = '{$value['question_id']}'";
                $result = $this->con->query($query);
                $answers = [];
                while ($row=$result->fetch_assoc()) {
                    array_push($answers, $row);
                }
                $questions[$key]['answers'] = $answers;

            }
            $poll['questions'] = $questions;
            return json_encode($poll, JSON_UNESCAPED_UNICODE);
    }

    function sendPollAnswers($data, $customer_id){
        foreach ($data["answers"] as $value) {
            $query = "INSERT INTO `poll_answers_customers`(`answer_id`, `customer_id`) VALUES ('{$value}', '{$customer_id}')";
            $this->con->query($query);
        }
    }
    

}