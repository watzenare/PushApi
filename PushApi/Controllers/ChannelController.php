<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Channel;
use \PushApi\Controllers\Controller;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Contains the basic and general actions that can be done with a channel.
 */
class ChannelController extends Controller
{
    /**
     * Creates a new channel into the registration with given params and
     * displays the information of the created channel. If the channel tries
     * to register twice (checked by name), the information of the
     * register channel is displayed without adding him again into the
     * registration.
     * @throws PushApiException
     *
     * Call params:
     * @var "name" required
     */
    public function setChannel()
    {
        if (!isset($this->requestParams['name'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $this->send(Channel::createChannel($this->requestParams['name']));
    }

    /**
     * Retrieves all channels registered or a channel information if it is registered.
     * @param int $id  Channel identification.
     * @throws PushApiException
     */
    public function getChannel($id)
    {
        $this->send(Channel::getChannel($id));
    }

    /**
     * Retrieves the channel information given its name.
     * @param string $name  Channel name.
     * @throws PushApiException
     */
    public function getChannelByName()
    {
        if (!isset($this->requestParams['name'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $this->send(Channel::getInfoByName($this->requestParams['name']));
    }

    /**
     * Updates channel information given its identification and params to update.
     * @param int $id  Channel identification.
     * @throws PushApiException
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

        $this->send(Channel::updateChannel($id, $update));
    }

    /**
     * Deletes a channel given its identification.
     * @param int $id  Channel identification.
     * @throws PushApiException
     */
    public function deleteChannel($id)
    {
        $this->send(Channel::remove($id));
    }

    /**
     * Retrieves all channels register.
     * @throws PushApiException
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

        $this->send(Channel::getChannels($limit, $page));
    }
}