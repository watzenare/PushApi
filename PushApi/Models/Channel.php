<?php

namespace PushApi\Models;

use \Slim\Log;
use \PushApi\PushApi;
use \PushApi\System\IModel;
use \PushApi\PushApiException;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Model of the channels table, manages all the relationships and dependencies
 * that can be done on these table.
 */
class Channel extends Eloquent implements IModel
{
    public $timestamps = false;
	public $fillable = array('name');
    protected $hidden = array('created');

    /**
     * Returns the basic displayable Channel model.
     * @return array
     */
    public static function getEmptyDataModel()
    {
        return [
            "id" => 0,
            "name" => "",
        ];
    }

    /**
     * Relationship n-1 to get an instance of the subscribed table.
     * @return Subscribed Instance of Subscription model
     */
    public function subscriptions()
    {
        return $this->hasMany('\PushApi\Models\Subscription');
    }

    /**
     * Checks if channel exists and returns it if true.
     * @param  int $id
     * @return Channel/false
     */
    public static function checkExists($id)
    {
        try {
            $channel = Channel::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $channel;
    }

    public static function generateFromModel($channel)
    {
        $result = self::getEmptyDataModel();
        try {
            $result['id'] = $channel->id;
            $result['name'] = $channel->name;
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }

    /**
     * Retrieves the channel information given its name.
     * @param  string $name
     * @return array
     */
    public static function getInfoByName($name)
    {
        $channel = Channel::where('name', $name)->first();

        if ($channel == null) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return self::generateFromModel($channel);
    }

    /**
     * Retrieves the Channel id given its name if exists.
     * @param  string $name Channel name.
     * @return int/boolean
     */
    public static function getIdByName($name)
    {
        $channel = Channel::where('name', $name)->first();

        if ($channel) {
            return $channel->id;
        }

        return false;
    }

    /**
     * Obtains all information about target theme given its id.
     * @param  int $id
     * @return array
     */
    public static function getChannel($id)
    {
        try {
            $channel = Channel::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return self::generateFromModel($channel);
    }

    /**
     * Creates a new channel if it does not exist yet.
     * @param  string $name
     * @return array
     */
    public static function createChannel($name)
    {
        $channelExists = self::getIdByName($name);

        if ($channelExists) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::DUPLICATED_VALUE, Log::DEBUG);
            return false;
        }

        $channel = new Channel;
        $channel->name = $name;
        $channel->save();

        return $channel;
    }

    /**
     * Updates the target channel with the available updating values.
     * @param  int $id
     * @param  string $name
     * @return boolean
     */
    public static function updateChannel($id, $update)
    {
        if (!$channel = self::checkExists($id)) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        foreach ($update as $key => $value) {
            $channel->$key = $value;
        }

        try {
            $channel->update();
        } catch (QueryException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::DUPLICATED_VALUE, Log::DEBUG);
            return false;
        }

        return true;
    }

    /**
     * Deletes the target channel given its id.
     * @param  int $id
     * @return boolean
     */
    public static function remove($id)
    {
        // It must be deleted all subscriptions first in order to destroy the DB relationship
        if (!Subscription::deleteAllChannelSubscriptions($id)) {
            PushApi::log(__METHOD__ . " - Channel subscriptions deleted unsuccessfully, channel $id has not been deleted", Log::WARN);
            return false;
        }

        try {
            $channel = Channel::findOrFail($id);
            $channel->delete();
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return true;
    }

    /**
     * Obtains all channels registered. It can be searched giving limit and page values.
     * @param  int $limit
     * @param  int $page
     * @return array
     */
    public static function getChannels($limit = 10, $page = 1)
    {
        $result = [
            'channels' => []
        ];
        $skip = 0;

        // Updating the page offset
        if ($page != 1) {
            $skip = ($page - 1) * $limit;
        }

        $result['limit'] = (int) $limit;
        $result['page'] = (int) $page;

        try {
            $channels = Channel::orderBy('id', 'asc')->take($limit)->offset($skip)->get();

            $result['totalInPage'] = sizeof($channels);

            foreach ($channels as $channel) {
                $result['channels'][] = self::generateFromModel($channel);
            }
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }

    public static function getSubscribers($name)
    {
        $users = [];

        $id = self::getIdByName($name);

        if ($id == null) {
            return false;
        }

        try {
            $subscribers = Channel::findOrFail($id)->subscriptions()->get();
            foreach ($subscribers as $user) {
                $users[] = User::getUser($user->user_id);
            }
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $users;
    }
}