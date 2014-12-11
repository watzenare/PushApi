<?php

namespace PushApi\System;

class Util
{
    /**
     * Prints perfectly all kind of data, it can print through console or normal deb
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

    public static function validateFields($validFields, $updatingFields) {
        $count = 0;
        foreach ($updatingFields as $key => $value) {
            if (in_array($key, $validFields, true)) {
                $count++;
            } else {
                return false;
            }
        }

        if ($count <= count($validFields)) {
            return true;
        } else {
            return false;
        }
    }
}