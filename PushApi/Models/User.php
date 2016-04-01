<?php

namespace PushApi\Models;

use \Slim\Log;
use \PushApi\PushApi;
use \PushApi\System\IModel;
use \PushApi\PushApiException;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Model of the users table, manages all the relationships and dependencies
 * that can be done on these table.
 *
 * User management requires to update Users & Devices tables. To improve this, it is updated
 * the user model in order to avoid to remember the management of the two tables outside the
 * User model.
 */
class User extends Eloquent implements IModel
{
    public $timestamps = false;
    protected $fillable = array('email', 'android', 'ios');
    protected $guarded = array('id','created');
    protected $hidden = array('created');

    public static function getEmptyDataModel()
    {
        return [
            "id" => 0,
            "email" => [],
            "android" => [],
            "ios" => [],
        ];
    }

    /**
     * Relationship n-1 to get an instance of the subscribed table.
     * @return Subscription Instance of Subscription model.
     */
    public function subscriptions()
    {
        return $this->hasMany('\PushApi\Models\Subscription');
    }

    /**
     * Relationship n-1 to get an instance of the preferences table.
     * @return Preferences Instance of Preferences model.
     */
    public function preferences()
    {
        return $this->hasMany('\PushApi\Models\Preference');
    }

    /**
     * Relationship n-1 to get an instance of the logs table.
     * @return Log Instance of Log model.
     */
    public function logs()
    {
        return $this->hasMany('\PushApi\Models\Log');
    }

    /**
     * Relationship n-1 to get an instance of the devices table.
     * @return Device Instance of Device model.
     */
    public function devices()
    {
        return $this->hasMany('\PushApi\Models\Device');
    }

    /**
     * Checks if user exists and returns it if true.
     * @param  int $id User id.
     * @return User/false
     */
    public static function checkExists($id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $user;
    }

    /**
     * Generates an user given its object data merging it with the devices that its owning.
     * @param  User $user User object model.
     * @return array
     */
    public static function generateFromModel($user)
    {
        $result = self::getEmptyDataModel();
        try {
            $result['id'] = $user->id;
            $user->devices->each(function($device) use (&$result) {
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
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }

    /**
     * Obtains the user identification given its email reference.
     * @param  string $email
     * @return int/boolean  If user is found returns id, if not, returns false.
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
     * Obtains all information about target user given its id.
     * @param  int $id User identification.
     * @return array
     */
    public static function getUser($id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
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
     * Creates a new user given its email reference.
     * @param  string  $email User email reference.
     * @return User/boolean
     */
    public static function createUser($email)
    {
        // Checking if user already exists
        $userId = self::getIdByEmail($email);

        if ($userId) {
            return self::getUser($userId);
        }

        // Creating user adding email as first value
        $user = new User;
        $user->save();

        if (!isset($user->id)) {
            return false;
        }

        $added = Device::addDevice($user->id, Device::TYPE_EMAIL, $email);

        $model = self::getEmptyDataModel();
        $model['id'] = $user->id;

        if ($added) {
            array_push($model['email'], $email);
            return $model;
        }

        PushApi::log(__METHOD__ . " - Error: " . PushApiException::ACTION_FAILED, Log::DEBUG);
        return false;
    }

    /**
     * Increases the device type counter of the target user.
     * @param  int $userId
     * @param  int $deviceType
     * @return boolean
     */
    public static function incrementDevice($userId, $deviceType)
    {
        // Updating user device counter
        try {
            $user = User::findOrFail($userId);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }
        $user->increment(Device::$typeToString[$deviceType]);
        $user->update();

        return true;
    }

    /**
     * Decreases the device type counter of the target user.
     * @param  int $userId
     * @param  int $deviceType
     * @return boolean
     */
    public static function decrementDevice($userId, $deviceType)
    {
        // Updating user device counter
        try {
            $user = User::findOrFail($userId);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }
        $user->decrement(Device::$typeToString[$deviceType]);
        $user->update();

        return true;
    }

    /**
     * Deletes the target user given its id and all DB relationships that it has (devices owning, preferences...).
     * @param  int $id
     * @return boolean
     */
    public static function remove($id)
    {
        // It must be deleted all devices first in order to destroy the DB relationship
        if (!Device::deleteAllDevices($id)) {
            PushApi::log(__METHOD__ . " - User can not be removed, some of the devices can not be removed", Log::WARN);
            return false;
        }

        // It must be deleted all preferences related with target user
        if (!Preference::deleteAllUserPreferences($id)) {
            PushApi::log(__METHOD__ . " - User can not be removed, some of the preferences can not be removed", Log::WARN);
            return false;
        }

        // It must be deleted all subscriptions related with target user
        if (!Subscription::deleteAllUserSubscriptions($id)) {
            PushApi::log(__METHOD__ . " - User can not be removed, some of the subscriptions can not be removed", Log::WARN);
            return false;
        }

        try {
            $user = User::findOrFail($id);
            $user->delete();
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return true;
    }

    /**
     * Obtains all users registered in Push API with all its devices registered. It can be searched
     * giving limit and page values.
     * @param  int $limit Max results per page.
     * @param  int $page  Page to display.
     * @return array
     */
    public static function getUsers($limit = 10, $page = 1)
    {
        $result = [
            'users' => []
        ];
        $skip = 0;

        // Updating the page offset
        if ($page != 1) {
            $skip = ($page - 1) * $limit;
        }

        $result['limit'] = (int) $limit;
        $result['page'] = (int) $page;

        try {
            $users = User::orderBy('id', 'asc')->take($limit)->offset($skip)->get();

            $result['totalInPage'] = sizeof($users);

            foreach ($users as $user) {
                $result['users'][] = self::generateFromModel($user);
            }
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }
}