<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Channel;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

class ChannelController extends Controller
{
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

    public function getChannel($id)
    {
        try {
            $channel = Channel::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($channel->toArray());
    }

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
            $channel = Channel::where('level', $level)->orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($channel->toArray());
    }
}