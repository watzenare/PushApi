<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\User;
use \PushApi\Models\Channel;
use \PushApi\Models\Subscription;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions in order to send the messages
 */
class SendController extends Controller
{
	public function sendMessage()
	{
		try {
            $channelName = $this->slim->request->post('channelName');
            $id = $this->slim->request->post('userId');

            if (!isset($channelName)) {
                throw new PushApiException(PushApiException::NO_DATA, "Expected channelName param");
            }

            $channel = Channel::where(array('name' => $channelName))->first();

            if (isset($id)) {
                $subscription = Subscription::where(array('user_id' => $id, 'channel_id' => $channel->id))->first();
                $subscription->preferences;
                $this->prepareUserSendData($id, $channel, $preferences);
            } else {
                $subscription = Subscription::where(array('channel_id' => $channel->id))->get();
                var_dump($subscription);die;
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
	}

	private function prepareUserSendData($id, $channel, $preferences)
	{
		$data = array();
		$sendEmail = false;
		$sendSmartphone = false;

		$user = User::findOrFail($id);

		// Check if user wants to recive the notification via mail
        if (Subscription::EMAIL == (Subscription::EMAIL & $channel->level)) {
        	$sendEmail = true;
        }
        // Check if user wants to recive the notification via smartphone
        if (Subscription::SMARTPHONE == (Subscription::SMARTPHONE & $channel->level)) {
        	$sendSmartphone = true;
        }

  //       $sent = addToQueue($data);

  //       return $sent;
	}


	private function addToQueue($data = array())
	{
		return true;
	}
}