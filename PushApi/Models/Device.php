<?php

namespace PushApi\Models;

use \PushApi\PushApiException;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Model of the devices table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Device extends Eloquent
{
    const TYPE_EMAIL = 1;
    const TYPE_ANDROID = 2;
    const TYPE_IOS = 3;

    public static $typeToString = [
        self::TYPE_EMAIL => 'email',
        self::TYPE_ANDROID => 'android',
        self::TYPE_IOS => 'ios',
    ];

    public static $stringToType = [
        'email' => self::TYPE_EMAIL,
        'android' => self::TYPE_ANDROID,
        'ios' => self::TYPE_IOS,
    ];

    public $timestamps = false;
    protected $fillable = array('type', 'user_id', 'reference');
    protected $guarded = array('id', 'created');
    protected $hidden = array('created');

    private static function getEmptyDataModel()
    {
        return [
            "id" => 0,
            "type" => "",
            "reference" => "",
        ];
    }

    /**
     * Relationship 1-n to get an instance of the users table
     * @return [User] Instance of User model
     */
    public function user()
    {
        return $this->belongsTo('\PushApi\Models\User');
    }

    /**
     * [checkExists description]
     * @param  [type] $id [description]
     * @return Device/false
     */
    public static function checkExists($id)
    {
        try {
            $device = Device::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return false;
        }

        return $device;
    }

    /**
     * [getIdByReference description]
     * @param  [string] $userId
     * @param  [string] $reference
     * @return int/boolean  If user is found returns id, if not, returns false
     */
    public static function getIdByReference($userId, $reference)
    {
        $model = self::getEmptyDataModel();
        $device = Device::where('user_id', $userId)->where('reference', $reference)->first();

        if ($device) {
            $model['id'] = $device->id;
            $model['type'] = self::$typeToString[$device->type];
            $model['reference'] = $reference;
            return $model;
        }

        return false;
    }

    /**
     * [getDeviceByReference description]
     * @param  [string] $userId
     * @param  [string] $reference
     * @return int/boolean  If user is found returns id, if not, returns false
     */
    public static function getFullDeviceInfoByReference($reference)
    {
        $model = self::getEmptyDataModel();
        $device = Device::where('reference', $reference)->first();

        if ($device) {
            $model['id'] = $device->id;
            $model['type'] = self::$typeToString[$device->type];
            $model['user_id'] = $device->user_id;
            $model['reference'] = $reference;
            return $model;
        }

        return false;
    }

    /**
     * [getIdByReference description]
     * @param  [string] $userId
     * @param  [string] $reference
     * @return int/boolean  If user is found returns id, if not, returns false
     */
    public static function get($userId, $deviceId)
    {
        $model = self::getEmptyDataModel();

        $device = Device::where('id', $deviceId)->where('user_id', $userId)->first();

        if ($device) {
            $model['id'] = $device->id;
            $model['type'] = self::$typeToString[$device->type];
            $model['reference'] = $device->reference;
            return $model;
        }

        return false;
    }

    /**
     * Adds a new device refering the user and increases its devices counter.
     * It prevents to add duplicated values and updates smartphones device ids when a new user
     * is using the same smartphone.
     * @param  int $userId
     * @param  int/string $deviceType
     * @param string $reference
     * @return boolean
     */
    public static function addDevice($userId, $deviceType, $reference)
    {
        if (gettype($deviceType) == 'string' && isset(self::$stringToType[$deviceType])) {
            $deviceType = self::$stringToType[$deviceType];
        }

        if (!User::checkExists($userId)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        // If device is a smartphone, it can be used by more than one user. If it has not removed
        // its notification after letting another user use its smartphone, it should be prevented
        // to send notifications of the previous user.
        if (($device = self::getFullDeviceInfoByReference($reference)) && ($device != self::TYPE_EMAIL)) {
            // Removing data from the previous user
            self::removeDeviceById($device['user_id'], $device['id']);
        }

        // Adding device to user
        $device = new Device();
        $device->type = $deviceType;
        $device->user_id = $userId;
        $device->reference = $reference;

        // Saving the device and preventing exception if it is duplicated
        try {
            $device->save();
        } catch (QueryException $e) {
            return false;
        }

        if (User::incrementDevice($userId, $deviceType)) {
            return true;
        }

        return false;
    }

    /**
     * Deletes a device given that fits with all the params and decreases user devices counter.
     * Prevents to delete the last email address because user should hava at least 1 email registered.
     * @param  int $userId
     * @param  int/string $deviceType
     * @param  string $reference
     * @return boolean
     */
    public static function removeDeviceByParams($userId, $deviceType, $reference)
    {
        if (gettype($deviceType) == 'string' && isset(self::$stringToType[$deviceType])) {
            $deviceType = self::$stringToType[$deviceType];
        }

        if (!$user = User::checkExists($userId)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        // Preventing to delete the last email
        if ($deviceType == self::TYPE_EMAIL) {
            if ($user->email == 1) {
                return false;
            }
        }

        // Searching if device exists and remove it (decrementing user counter value)
        $device = Device::where('user_id', $userId)->where('type', $deviceType)->where('reference', $reference)->first();
        if ($device) {
            $device->delete();

            if (User::decrementDevice($userId, $deviceType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deletes a device using reference ids and decreases user devices counter.
     * Prevents to delete the last email address because user should hava at least 1 email registered.
     * @param  int $userId
     * @param  int $deviceId
     * @return boolean
     */
    public static function removeDeviceById($userId, $deviceId)
    {
        if (!$user = User::checkExists($userId)) {
            throw new PushApiException(PushApiException::NOT_FOUND, "User not found.");
        }

        if (!$device = Device::checkExists($deviceId)) {
            throw new PushApiException(PushApiException::NOT_FOUND, "Device not found.");
        }

        // Preventing to delete the last email
        if ($device->type == self::TYPE_EMAIL) {
            if ($user->email == 1) {
                return false;
            }
        }

        // Removing the device and decrementing user counter value
        $device->delete();

        if (User::decrementDevice($userId, $device->type)) {
            return true;
        }

        return false;
    }

     /**
     * Deletes all devices of the target user id.
     * @
     * @param  [type] $userId [description]
     * @return boolean
     */
    public static function deleteAllDevices($userId)
    {
        $devices = Device::where('user_id', $userId)->get();

        foreach ($devices as $device) {
            $device->delete();
        }

        return true;
    }
}