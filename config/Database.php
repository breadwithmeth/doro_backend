<?php
class Database
{
  // DB Params
  private $user = 'doro';
  private $password = '5N6x1X3u';
  private $database = 'doro2';
  private $host = 'localhost';
  private $port = 8889;
  private $conn;

  // DB Connect
  public function connect()
  {
    $this->conn = null;
    $this->conn = new mysqli($this->host, $this->user, $this->password, $this->database);
    $this->conn->set_charset("utf8mb4_unicode_ci");

    return $this->conn;
  }
}
