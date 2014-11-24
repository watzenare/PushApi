<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Channel;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\QueryException;
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
        try {
            $name = $this->slim->request->post('name');

            if (!isset($name)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            // Checking if channel already exists
            $channel = Channel::where('name', $name)->first();

            if (isset($channel->name)) {
                $this->send($channel->toArray());
            } else {
                $channel = new Channel;
                $channel->name = $name;
                $channel->save();
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }        $this->send($channel->toArray());
    }

    /**
     * Retrives channel information if it is registered
     * @param [int] $id  Channel identification
     */
    public function getChannel($id)
    {
        try {
            $channel = Channel::findOrFail($id);
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
        try {
            $update = array();
            $update['name'] = $this->slim->request->put('name');

            $update = $this->cleanParams($update);

            if (empty($update)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            $channel = Channel::find($id);
            foreach ($update as $key => $value) {
                $channel->$key = $value;
            }
            $channel->update();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
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
            $channel->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($channel->toArray());
    }

    /**
     * Retrives all channels registered
     */
    public function getAllChannels()
    {
        try {
            $channel = Channel::orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($channel->toArray());
    }
}