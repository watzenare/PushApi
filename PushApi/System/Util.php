<?php

namespace PushApi\System;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Container class used to store different useful functions
 */
class Util
{
    /**
     * Prints perfectly all kind of data. Very useful when debugging and data is unreadable
     */
    public static function p()
    {
        $consolePrint = false;

        if ($_SERVER['HTTP_HOST'] == null) {
            $consolePrint = true;
        }

        if (!$consolePrint) {
            echo '<pre>';
        }
        $args = func_get_args();

        foreach ($args as $var)
        {
            if ($var == null || $var == '') {
                var_dump($var);
            } elseif (is_array($var) || is_object($var)) {
                print_r($var);
            } else {
                echo $var;
            }
            if (!$consolePrint) {
                echo '<br>';
            } else {
                echo "\n";
            }
        }
        if (!$consolePrint) {
            echo '</pre>';
        }
    }
}