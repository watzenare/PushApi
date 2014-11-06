<?php

namespace PushApi;

use \Exception;

/**
 * Customized PushApi Exceptions
 */
class PushApiException extends Exception
{
    
    const DEFAULT_NO_ERRORS = 0;
    const INVALID_ACTION = 1;
    const INVALID_CALL = 2;
    const NO_DATA = 10;
    const NOT_FOUND = 11;
    const EMPTY_PARAMS = 12;
    const DB_NOT_UPDATED = 13;
    const INVALID_PARAMS = 14;
    const DUPLICATED_VALUE = 15;
    
    public function __construct($code, $message = null) {
        
        if (!isset($message)) {
            $message = $this->getExceptionMessage($code);
        }
    
        // Generate the exception
        parent::__construct($message, $code);
    }

    private function getExceptionMessage($code) {
        switch ($code) {
            case self::INVALID_ACTION:
                return 'This action is invalid';
                break;

            case self::INVALID_ACTION:
                return 'This call is undefined';
                break;

            case self::NO_DATA:
                return 'No data given by parameters';
                break;

            case self::NOT_FOUND:
                return 'No results found';
                break;

            case self::EMPTY_PARAMS:
                return 'No params where given';
                break;

            case self::DB_NOT_UPDATED:
                return 'Something goes wrong and the database has not been updated';
                break;

            case self::INVALID_PARAMS:
                return 'An invalid param was given';
                break;

            case self::DUPLICATED_VALUE:
                return 'This content is already added';
                break;
            
            default:
                return 'Exception not found';
                break;
        }
    }
}