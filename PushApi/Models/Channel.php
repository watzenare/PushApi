<?php

namespace PushApi\Models;

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
     * @throws PushApiExceptions
     */
    public static function checkExists($id)
    {
        try {
            $channel = Channel::findOrFail($id);
        } catch (ModelNotFoundException $e) {
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
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return $result;
    }

    /**
     * Retrives the channel information given its name.
     * @param  string $name
     * @return array
     * @throws PushApiException
     */
    public static function getInfoByName($name)
    {
        $channel = Channel::where('name', $name)->first();

        if ($channel == null) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return self::generateFromModel($channel);
    }

    /**
     * Retrives the Channel id given its name if exists.
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
     * @throws PushApiException
     */
    public static function getChannel($id)
    {
        try {
            $channel = Channel::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return self::generateFromModel($channel);
    }

    /**
     * Creates a new channel if it does not exist yet.
     * @param  string $name
     * @return array
     * @throws PushApiException
     */
    public static function createChannel($name)
    {
        $channelExists = self::getIdByName($name);

        if ($channelExists) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
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
     * @throws PushApiException
     */
    public static function updateChannel($id, $update)
    {
        if (!$channel = self::checkExists($id)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        foreach ($update as $key => $value) {
            $channel->$key = $value;
        }

        try {
            $channel->update();
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }

        return true;
    }

    /**
     * Deletes the target channel given its id.
     * @param  int $id
     * @return boolean
     * @throws PushApiException
     */
    public static function remove($id)
    {
        // It must be deleted all subscriptions first in order to destroy the DB relationship
        if (!Subscription::deleteAllChannelSubscriptions($id)) {
            return false;
        }

        try {
            $channel = Channel::findOrFail($id);
            $channel->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return true;
    }

    /**
     * Obtains all channels registered. It can be searched giving limit and page values.
     * @param  int $limit
     * @param  int $page
     * @return array
     * @throws PushApiException
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

        $result['limit'] = $limit;
        $result['page'] = $page;

        try {
            $channels = Channel::orderBy('id', 'asc')->take($limit)->offset($skip)->get();
            foreach ($channels as $theme) {
                $result['channels'][] = self::generateFromModel($theme);
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $result['totalInPage'] = sizeof($channels);

        return $result;
    }
}