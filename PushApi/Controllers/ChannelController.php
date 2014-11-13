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
     * to registrate twice (checked by mail), the information of the 
     * registrated channel is displayed without adding him again into the 
     * registration
     */
    public function setChannel()
    {
        try {
            $name = $this->slim->request->post('name');
            $level = $this->slim->request->post('level');

            if (!isset($name) && !isset($level)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if ($level > 2 || $level < 0) {
                throw new PushApiException(PushApiException::INVALID_RANGE, 'Level range 0 - 2');
            }

            // Checking if user already exists
            $channel = Channel::where('name', $name)->first();

            if (isset($channel->name)) {
                $this->send($channel->toArray());
            } else {
                $channel = new Channel;
                $channel->name = $name;
                $channel->level = $level;
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
            $update['level'] = $this->slim->request->put('level');

            $update = $this->cleanParams($update);

            if (empty($update)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if (isset($update['level']) && ($update['level'] > 2 || $update['level'] < 0)) {
                throw new PushApiException(PushApiException::INVALID_RANGE, 'Level range 0 - 2');
            }

            $channel = Channel::where('id', $id)->update($update);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($this->boolinize($channel));
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

    public function getLevel($level)
    {
        try {
            if ($level < 0 || $level > 2) {
                throw new PushApiException(PushApiException::INVALID_RANGE, 'Level range 0 - 2');
            }
            $channels = Channel::where('level', $level)->orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($channels->toArray());
    }
}