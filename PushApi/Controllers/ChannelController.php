<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Channel;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Contains the basic and general actions that can be done with a channel.
 */
class ChannelController extends Controller
{
    /**
     * Creates a new channel into the registration with given params and
     * displays the information of the created channel. If the channel tries
     * to registrate twice (checked by name), the information of the
     * registrated channel is displayed without adding him again into the
     * registration.
     *
     * Call params:
     * @var "name" required
     */
    public function setChannel()
    {
        if (!isset($this->requestParams['name'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $name = $this->requestParams['name'];

        // Checking if channel already exists
        $channel = Channel::where('name', $name)->first();

        if (!isset($channel->name)) {
            $channel = new Channel;
            $channel->name = $name;
            $channel->save();
        }
        $this->send($channel->toArray());
    }

    /**
     * Retrives all channels registered or a channel information if it is registered
     * @param int $id  Channel identification
     */
    public function getChannel($id)
    {
        try {
            $this->send(Channel::getChannel($id));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Retrives the channel information given its name
     * @param [string] $name  Channel name
     */
    public function getChannelByName()
    {
        if (!isset($this->requestParams['name'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        try {
            $this->send(Channel::getInfoByName($this->requestParams['name']));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Updates channel infomation given its identification and params to update
     * @param int $id  Channel identification
     *
     * Call params:
     * @var "name" required
     */
    public function updateChannel($id)
    {
        $update = array();

        if (!isset($this->requestParams['name'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $update['name'] = $this->requestParams['name'];

        try {
            $this->send(Channel::updateChannel($id, $update));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Deletes a channel given its identification
     * @param int $id  Channel identification
     */
    public function deleteChannel($id)
    {
        try {
            $this->send(Channel::remove($id));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Retrives all channels registred.
     *
     * Call params:
     * @var "limit" optional
     * @var "page" optional
     */
    public function getChannels()
    {
        $limit = (isset($this->requestParams['limit']) ? $this->requestParams['limit'] : 10);
        $page = (isset($this->requestParams['page']) ? $this->requestParams['page'] : 1);

        if ($limit < 0) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid limit value");
        }

        if ($page < 1) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid page value");
        }

        try {
            $this->send(Channel::getChannels($limit, $page));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }
}