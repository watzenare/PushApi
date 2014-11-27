<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\User;
use \PushApi\Models\Theme;
use \PushApi\Models\Channel;
use \PushApi\Models\Subscription;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions in order to send the messages
 */
class LogController extends Controller
{
	public function sendMessage()
	{
        $message = $this->slim->request->post('message');
        $theme = $this->slim->request->post('case');
        $userId = (int) $this->slim->request->post('user_id');
        $channel = $this->slim->request->post('channel');

        if (!isset($message) && !isset($theme)) {
            throw new PushApiException(PushApiException::NO_DATA, "Expected case param");
        }

        try {
            $theme = Theme::with('preferences.user')->where('name', $theme)->first();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $users = array();

        switch ($theme->range) {
            case Theme::UNICAST:
                if (!isset($userId)) {
                    throw new PushApiException(PushApiException::NO_DATA, "Expected user_id param");
                }
                try {
                    $user = User::findOrFail($userId);
                    $preference = $user->preferences()->where('theme_id', $theme->id)->get();

                    // $message;
                    // $user->id;
                    var_dump($preference->toArray());

                } catch (ModelNotFoundException $e) {
                    throw new PushApiException(PushApiException::NOT_FOUND);
                }
                break;

            case Theme::MULTICAST:
                if (!isset($channel)) {
                    throw new PushApiException(PushApiException::NO_DATA, "Expected channel param");
                }
                try {
                    $channel = Channel::with(array('subscriptions.user.preferences' => function($query) use ($theme) {
                        return $query->where('theme_id', $theme->id);
                    }))->where('name', $channel)->first();
                } catch (ModelNotFoundException $e) {
                    throw new PushApiException(PushApiException::NOT_FOUND);
                }
                $usersSubscribers = $channel->subscriptions;
                var_dump($usersSubscribers->toArray());die();
                break;

            case Theme::BROADCAST:
                $usersPreferences = $theme->preferences;

                if (empty($usersPreferences->toArray())) {
                    $this->send(false);
                } else {
                    foreach ($usersPreferences->toArray() as $key => $user) {
                        $a = "10";
                        $b = "01";
                        $c = "11";
                        var_dump(($a & $b) == $a || ($a & $c) == $a);
                    }
                }

                break;
            
            default:
                throw new PushApiException(PushApiException::INVALID_ACTION);
                break;
        }

	}

	private function prepareUserSendData()
	{
		$data = array(
            'email' => array(
            ),
            'smartphone' => array(
            ),
        );
	
  //       $sent = addToQueue($data);

  //       return $sent;
	}


    /**
     * [addToQueue description]
     * @param array $data [description]
     */
	private function addToQueue($data = array())
	{
		return true;
	}
}