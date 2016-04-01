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
 * Model of the subscriptions table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Subscription extends Eloquent implements IModel
{
    public $timestamps = false;
    protected $fillable = array('user_id', 'channel_id');
    protected $hidden = array('created');

    /**
     * Returns the basic displayable Subscription model.
     * @return array
     */
    public static function getEmptyDataModel()
    {
        return [
            "id" => 0,
            "user_id" => 0,
            "channel_id" => 0,
        ];
    }

    /**
     * Relationship 1-n to get an instance of the users table.
     * @return User Instance of User model.
     */
    public function user()
    {
        return $this->belongsTo('\PushApi\Models\User');
    }

    /**
     * Relationship 1-n to get an instance of the channels table.
     * @return Channel Instance of Channel model.
     */
    public function channel()
    {
        return $this->belongsTo('\PushApi\Models\Channel');
    }

/**
     * Checks if user exists and returns it if true.
     * @param  int $id
     * @return Subscription/false
     */
    public static function checkExists($id)
    {
        try {
            $subscription = Subscription::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $subscription;
    }

    /**
     * Checks if it is set the subscription between user and channel.
     * @param  int $idUser
     * @param  int $idChannel
     * @return Subscription/false
     */
    public static function checkExistsUserSubscription($idUser, $idChannel)
    {
        try {
            $subscription = User::findOrFail($idUser)->subscriptions()->where('channel_id', $idChannel)->first();
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        if ($subscription) {
            return $subscription;
        }

        return false;
    }

    public static function generateFromModel($subscription)
    {
        $result = self::getEmptyDataModel();

        try {
            $result['id'] = (int) $subscription->id;
            $result['user_id'] = (int) $subscription->user_id;
            $result['channel_id'] = (int) $subscription->channel_id;
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }

    /**
     * Gets the target user subscription.
     * @param  int $idUser
     * @param  int $idChannel
     * @return array
     */
    public static function getSubscription($idUser, $idChannel)
    {
        $subscription = self::checkExistsUserSubscription($idUser, $idChannel);

        if (!$subscription) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return self::generateFromModel($subscription);
    }

    /**
     * Creates a new user subscription.
     * @param  int $idUser
     * @param  int $idChannel
     * @return array
     */
    public static function createSubscription($idUser, $idChannel)
    {
        // Checking if subscription is already set
        if (self::checkExistsUserSubscription($idUser, $idChannel)) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::DUPLICATED_VALUE, Log::DEBUG);
            return false;
        }

        if (User::checkExists($idUser) && Channel::checkExists($idChannel)) {
            $subscription = new Subscription;
            $subscription->user_id = (int) $idUser;
            $subscription->channel_id = (int) $idChannel;
            $subscription->save();
            return $subscription;
        } else {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }
    }

    /**
     * Remove the target user subscription.
     * @param  int $idUser
     * @param  int $idChannel
     * @return boolean
     */
    public static function remove($idUser, $idChannel)
    {
        $subscription = self::checkExistsUserSubscription($idUser, $idChannel);

        if (!$subscription) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        try {
            $subscription->delete();
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return true;
    }

    /**
     * Obtains all user subscriptions set by the user.
     * @param  int  $idUser
     * @return array
     */
    public static function getSubscriptions($idUser)
    {
        $result = [];

        try {
            $subscriptions = User::findOrFail($idUser)->subscriptions()->orderBy('id', 'asc')->get();
            foreach ($subscriptions as $subscription) {
                $result[] = self::generateFromModel($subscription);
            }
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }

    /**
     * Deletes all subscriptions related with the target channel.
     * @param  int $idChannel
     * @return boolean
     */
    public static function deleteAllChannelSubscriptions($idChannel)
    {
        $subscriptions = Subscription::where('channel_id', $idChannel)->get();

        foreach ($subscriptions as $subscription) {
            $subscription->delete();
        }

        return true;
    }

    /**
     * Deletes all subscriptions related with the target user.
     * @param  int $idUser
     * @return boolean
     */
    public static function deleteAllUserSubscriptions($idUser)
    {
        $subscriptions = Subscription::where('user_id', $idUser)->get();

        foreach ($subscriptions as $subscription) {
            $subscription->delete();
        }

        return true;
    }
}