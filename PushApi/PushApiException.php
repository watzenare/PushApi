<?php

namespace PushApi;

use \Exception;

/**
 * Customized PushApi Exceptions
 */
class PushApiException extends Exception
{
    
    const NOT_AUTORIZED = -1;
    const DEFAULT_NO_ERRORS = 0;
    const INVALID_ACTION = 1;
    const INVALID_CALL = 2;
    const INVALID_RANGE = 3;
    const NO_DATA = 10;
    const NOT_FOUND = 11;
    const EMPTY_PARAMS = 12;
    const DB_NOT_UPDATED = 13;
    const INVALID_PARAMS = 14;
    const DUPLICATED_VALUE = 15;
    
    /**
     * Generates the exception given a code and an extra message if is passed
     * @param [int] $code    Exception code
     * @param [string] $message Additional message added to the default exception message
     */
    public function __construct($code, $message = null) {
        if (!isset($message)) {
            $message = $this->getExceptionMessage($code);
        } else {
            $message = $this->getExceptionMessage($code) . ': ' . $message;
        }
    
        // Generates the exception
        parent::__construct($message, $code);
    }

    /**
     * Transforms the integer code to a string in order to get a message of the exception
     * @param  [int] $code Exception code
     * @return [string]       Default message of $code
     */
    private function getExceptionMessage($code) {
        switch ($code) {
            case self::NOT_AUTORIZED:
                return 'No permisions to use this call';
                break;

            case self::INVALID_ACTION:
                return 'This action is invalid';
                break;

            case self::INVALID_CALL:
                return 'This call is undefined';
                break;

            case self::INVALID_RANGE:
                return 'A value contains an invalid range';
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