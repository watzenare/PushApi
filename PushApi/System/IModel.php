<?php

namespace PushApi\System;

use \PushApi\PushApiException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Contains the basic functionalities that each Push API model must implement to handle some basic
 * and useful methods.
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