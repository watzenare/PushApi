<?php

namespace PushApi\Models;

use \PushApi\PushApiException;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Model of the users table, manages all the relationships and dependencies
 * that can be done on these table.
 *
 * User management requires to update Users & Devices tables. To improve this, it is updated
 * the user model in order to avoid to remember the management of the two tables outside the
 * User model.
 */
class User extends Eloquent
{
    public $timestamps = false;
    protected $fillable = array('email', 'android', 'ios');
    protected $guarded = array('id','created');
    protected $hidden = array('created');

    protected $appends = ['devices'];

    private static function getEmptyDataModel()
    {
        return [
            "id" => 0,
            "email" => [],
            "android" => [],
            "ios" => [],
        ];
    }

    /**
     * Relationship n-1 to get an instance of the subscribed table
     * @return [Subscription] Instance of Subscription model
     */
    public function subscriptions()
    {
        return $this->hasMany('\PushApi\Models\Subscription');
    }

    /**
     * Relationship n-1 to get an instance of the preferences table
     * @return [Preferences] Instance of Preferences model
     */
    public function preferences()
    {
        return $this->hasMany('\PushApi\Models\Preference');
    }

    /**
     * Relationship n-1 to get an instance of the logs table
     * @return [Log] Instance of Log model
     */
    public function logs()
    {
        return $this->hasMany('\PushApi\Models\Log');
    }

    /**
     * Relationship n-1 to get an instance of the devices table
     * @return [Device] Instance of Device model
     */
    public function devices()
    {
        return $this->hasMany('\PushApi\Models\Device');
    }

    /**
     * [checkExists description]
     * @param  [type] $id [description]
     * @return User/false
     */
    public static function checkExists($id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return false;
        }

        return $user;
    }

    /**
     * [getIdByEmail description]
     * @param  [string] $email
     * @return int/boolean  If user is found returns id, if not, returns false
     */
    public static function getIdByEmail($email)
    {
        $device = Device::where('type', Device::TYPE_EMAIL)->where('reference', $email)->first();

        if ($device) {
            return $device->user_id;
        }

        return false;
    }

    /**
     * [generateUser description]
     * @param  [User] $user User object model
     * @return array
     */
    public static function generateUser($user)
    {
        $result = self::getEmptyDataModel();
        try {
            $result['id'] = $user->id;
            $devices = $user->devices->each(function($device) use (&$result) {
                switch ($device->type) {
                    case Device::TYPE_EMAIL:
                        $result['email'][$device->id] = $device->reference;
                        break;
                    case Device::TYPE_ANDROID:
                        $result['android'][$device->id] = $device->reference;
                        break;
                    case Device::TYPE_IOS:
                        $result['ios'][$device->id] = $device->reference;
                        break;

                    default:
                        break;
                }
            });
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return $result;
    }

    /**
     * [get description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public static function get($id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        // Getting user devices info
        $userDevices = Device::where('user_id', $id)->get()->toArray();

        $model = self::getEmptyDataModel();
        $model['id'] = $user->id;

        // Filling the model with devices if user have set some of them
        foreach ($userDevices as $device) {
            $type = Device::$typeToString[$device['type']];
            $model[$type][$device['id']] = $device['reference'];
        }

        return $model;
    }

    /**
     * [create description]
     * @param  array  $attr [description]
     * @return [type]       [description]
     */
    public static function create(array $attr = array())
    {
        // Checking if user already exists
        $userId = self::getIdByEmail($attr['email']);

        if ($userId) {
            return self::get($userId);
        }

        // Creating user adding email as first value
        $user = new User;
        $user->save();

        if (!isset($user->id)) {
            return false;
        }

        $added = Device::addDevice($user->id, Device::TYPE_EMAIL, $attr['email']);

        $model = self::getEmptyDataModel();
        $model['id'] = $user->id;

        if ($added) {
            array_push($model['email'], $attr['email']);
        }

        return $model;
    }

    /**
     * Increases the device type counter of the target user.
     * @param  [int] $userId
     * @param  [int] $deviceType
     * @return boolean
     */
    public static function incrementDevice($userId, $deviceType)
    {
        // Updating user device counter
        $user = User::findOrFail($userId);
        $user->increment(Device::$typeToString[$deviceType]);
        $user->update();

        return true;
    }

    /**
     * Decreases the device type counter of the target user.
     * @param  [int] $userId
     * @param  [int] $deviceType
     * @return boolean
     */
    public static function decrementDevice($userId, $deviceType)
    {
        // Updating user device counter
        $user = User::findOrFail($userId);
        $user->decrement(Device::$typeToString[$deviceType]);
        $user->update();

        return true;
    }

    /**
     * [remove description]
     * @param  [int] $id
     * @return boolean
     * @throws PushApiException
     */
    public static function remove($id)
    {
        // It must be deleted all devices first in order to destroy the DB relationship
        if (!Device::deleteAllDevices($id)) {
            return false;
        }

        try {
            $user = User::findOrFail($id);
            $user->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return true;
    }

    public static function getUsers($limit = 50, $page = 1)
    {
        $result = [
            'users' => []
        ];
        $skip = 0;
        // Updating the page offset
        if ($page != 1) {
            $skip = $page * $limit;
        }

        $result['limit'] = (int) $limit;
        $result['page'] = (int) $page;

        try {
            $users = User::orderBy('id', 'asc')->take($limit)->offset($skip)->get();
            foreach ($users as $user) {
                $result['users'][] = self::generateUser($user);
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $result['totalInPage'] = sizeof($users);

        return $result;
    }
}