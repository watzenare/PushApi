<?php

namespace PushApi\System;

use \Exception;

/**
 * Customized PushApi Exceptions
 */
class PushApiException extends Exception
{
    
    const DEFAULT_NO_ERRORS = 0;
    const NO_DATA = 10;
    const NOT_FOUND = 11;
    const EMPTY_PARAMS = 12;
    
    public function __construct($code, $message = null) {
        
        if (!isset($message)) {
            $message = $this->getExceptionMessage($code);
        }
    
        // Generate the exception
        parent::__construct($message, $code);
    }

    private function getExceptionMessage($code) {
        switch ($code) {
            case self::NO_DATA:
                return 'No data given by parameters';
                break;

            case self::NOT_FOUND:
                return 'No results found';
                break;

            case self::EMPTY_PARAMS:
                return 'No params where given';
                break;
            
            default:
                return 'Exception not found';
                break;
        }
    }
}