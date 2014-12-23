<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Channel;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
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
     * registration
     */
    public function setChannel()
    {
        $name = $this->slim->request->post('name');

        if (!isset($name)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

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
     * @param [int] $id  Channel identification
     */
    public function getChannel($id = false)
    {
        try {
            if (!$id) {
                $channel = Channel::orderBy('id', 'asc')->get();
            } else {
                $channel = Channel::findOrFail($id);
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($channel->toArray());
    }

    /**
     * Updates channel infomation given its identification and params to update
     * @param [int] $id  Channel identification
     */
    public function updateChannel($id)
    {
        $update = array();
        $update['name'] = $this->slim->request->put('name');

        $update = $this->cleanParams($update);

        if (empty($update)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        try {
            $channel = Channel::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        foreach ($update as $key => $value) {
            $channel->$key = $value;
        }

        $channel->update();
        $this->send($channel->toArray());
    }

    /**
     * Deletes a channel given its identification
     * @param [int] $id  Channel identification
     */
    public function deleteChannel($id)
    {
        try {
            $channel = Channel::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $channel->delete();
        $this->send($channel->toArray());
    }
}