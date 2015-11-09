<?php

namespace PushApi\System;

use \PushApi\PushApiException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Contains the basic functions that all notifications emisors must implement
 * in order to get a better message definition.
 */
interface IModel
{
    /**
     * Returns the basic displayable Class model.
     * @return array
     */
    public static function getEmptyDataModel();

    /**
     * Checks if ClassModel data structure exists and returns it if true.
     * @param  int $id ClassModel structure id
     * @return ClassModel/false
     */
    public static function checkExists($id);

    /**
     * Generates an ClasModel given a data object.
     * @param  User $object User object model
     * @return array
     */
    public static function generateFromModel($object);
}