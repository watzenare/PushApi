<?php

namespace PushApi\Controllers;

use \medoo as Medoo;
use \PushApi\PushApiException;
use \PushApi\System\Util;


class Dao
{
    const COLUMN_ALL = '*';
    const COLUMN_ID = 'id';
    const ASCENDENT = 'ASC';
    const DESCENDENT = 'DESC';
    const ORDER = 'ORDER';
    
    protected $dbLink;
    protected $table;
    // Editable DB fields
    protected $fields = array();

    public function __construct() {
        $this->dbLink = new Medoo(
            array(
                'database_type' => 'mysql',
                'database_name' => DB_NAME,
                'server' => DB_HOST,
                'username' => DB_USERNAME,
                'password' => DB_PASSWORD,
                'charset' => 'utf8',
            )
        );
    }

    public function get($id) {
        if (!isset($id)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $query = array( 
            self::COLUMN_ID => $id
        );

        $result = $this->dbLink->select($this->table, self::COLUMN_ALL, $query);

        if (empty($result)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } else {
            return $result;
        }
    }

    public function update($id, $update) {
        if ($id == null) {
            throw new PushApiException(PushApiException::EMPTY_PARAMS);
        }

        if (!Util::validateFields($this->fields, $update)) {
            throw new PushApiException(PushApiException::INVALID_PARAMS);
        }

        // update only set fields
        foreach ($update as $key => $value) {
            if ($value == null) {
                unset($update[$key]);
            }
        }

        $query = array( 
            self::COLUMN_ID => $id
        );
        $deleted = $this->dbLink->update($this->table, $update, $query);

        if ($deleted == 1) {
            return true;
        } else {
            throw new PushApiException(PushApiException::DB_NOT_UPDATED);
        }
    }

    public function delete($id) {
        if (!isset($id)) {
            throw new PushApiException(PushApiException::EMPTY_PARAMS);
        }

        $query = array( 
            self::COLUMN_ID => $id
        );
        $deleted = $this->dbLink->delete($this->table, $query);

        if ($deleted == 1) {
            return true;
        } else {
            throw new PushApiException(PushApiException::DB_NOT_UPDATED);
        }
    }
}
