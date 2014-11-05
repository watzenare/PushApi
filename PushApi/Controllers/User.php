<?php

namespace PushApi\Controllers;

use \PushApi\Controllers\Dao;
use \PushApi\PushApiException;


class User extends Dao
{
    protected $table = 'users';

    protected $fields = array(
        'username',
        'email',
        'userId'
    );

    public function __construct() {
        parent::__construct();
    }

    public function createUser($data) {

        $result = $this->dbLink->insert($this->table, $data);
        $error = $this->dbLink->error();
        // Duplicate DB entry
        if ($error[1] != 1062) {
            return $result;
        } else {
            return false;
        }
    }

    public function getUserByUsername($username = null) {
        if ($username == null) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $query = array(
            self::COL_USERNAME => $username
        );

        return $this->dbLink->select($this->table, self::COLUMN_ALL, $query);
    }

    public function getUsers() {
        $query = array(
            self::ORDER => array(
                    self::COLUMN_ID . ' ' . self::ASCENDENT
                )
        );

        $result = $this->dbLink->select($this->table, self::COLUMN_ALL, $query);

        if ($result) {
            return $result;
        } else {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
    }
}