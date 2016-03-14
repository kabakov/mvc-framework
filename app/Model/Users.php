<?php

namespace  App\Model;

class Users extends Model
{
    public function __construct() {
        parent::__construct();
        @session_start();
    }

    public function createUserDB($data) {
        return $this->connection->insert($data, $this->table);
    }

    public function getHash($user) {
        return $this->connection->selectOneValue('password', $this->table, "username = '$user'");
    }
}