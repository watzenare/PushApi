<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Log;
use \PushApi\Models\User;
use \PushApi\Models\Theme;
use \PushApi\Models\Channel;
use \PushApi\Models\Preference;
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
        $theme = $this->slim->request->post('theme');
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
        $log = new Log;

        switch ($theme->range) {
            case Theme::UNICAST:
                if (!isset($userId)) {
                    throw new PushApiException(PushApiException::NO_DATA, "Expected user_id param");
                }
                try {
                    $user = User::findOrFail($userId);
                    $preference = $user->preferences()->where('theme_id', $theme->id)->get();

                    $preference = $preference->toArray();

                    // User hasn't set preferences for that theme, by default recive all devices
                    if (empty($preference)) {
                        $preference = decbin(Preference::ALL_DEVICES);
                    } else {
                        $preference = decbin($preference->option);
                    }

                    // Registering message
                    $log->theme_id = $theme->id;
                    $log->user_id = $userId;
                    $log->message = $message;
                    $log->save();

                    if ((Preference::EMAIL & $preference) == Preference::EMAIL) {
                        $this->addToEmailQueue($user, $theme, $message);
                    }

                    if ((Preference::SMARTPHONE & $preference) == Preference::SMARTPHONE) {
                        $this->addToSmartphoneQueue($user, $theme, $message);
                    }
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
                $androidUsers = array();
                $iosUsers = array();

                // Checking user preferences and add the notification to the right queue
                foreach ($usersSubscribers->toArray() as $key => $subscription) {
                    // Email messages are stored individually
                    if ((Preference::EMAIL & $subscription['user']['preferences'][0]['option']) == Preference::EMAIL) {
                        $this->addToEmailQueue($subscription['user'], $theme, $message);
                    }

                    // Smartphone notifications can be stored with multiple users
                    if ((Preference::SMARTPHONE & $subscription['user']['preferences'][0]['option']) == Preference::SMARTPHONE) {
                        if ($subscription['user']['android_id'] != 0) {
                            array_push($androidUsers, $subscription['user']['android_id']);
                        } else if ($subscription['user']['ios_id'] != 0) {
                            array_push($iosUsers, $subscription['user']['ios_id;']);
                        }

                        // Android GMC lets send notifications to 1000 devices with one JSON message
                        if (sizeof($androidUsers) == 1000) {
                            $this->addToAndroidQueue($androidUsers, $theme, $message);
                            $androidUsers = array();
                        }
                    }
                }

                $this->addToAndroidQueue($androidUsers, $theme, $message);
                $this->addToIosQueue($iosUsers, $theme, $message);

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

    /**
     * [addToEmailQueue description]
     * @param [type] $user    [description]
     * @param [type] $theme   [description]
     * @param [type] $message [description]
     */
	private function addToEmailQueue($user, $theme, $message)
	{
        $this->redis;

        $data = array(
            "to" => $user['email'],
            "subject" => $theme->name,
            "message" => $message
        );
        var_dump($data);
        // $data = str_replace(" ", "\s", addslashes(json_encode($data)));

        // extract data
        // stripslashes(str_replace("\s", " ", $data));


		// $data = array(
  //           'email' => array(
  //           ),
  //           'smartphone' => array(
  //           ),
  //       );
	
  //       $sent = addToQueue($data);

  //       return $sent;
	}

    /**
     * [addToAndroidQueue description]
     * @param [type] $androidUsers [description]
     * @param [type] $theme        [description]
     * @param [type] $message      [description]
     */
    private function addToAndroidQueue($androidUsers, $theme, $message)
    {
        $data = array(
            "registration_ids" => $androidUsers,
            "collapse_key" => $theme->name,
            "data" => array(
                'message' => $message
            )
        );

        var_dump($data);
    }

    /**
     * [addToIosQueue description]
     * @param [type] $iosUsers [description]
     * @param [type] $theme    [description]
     * @param [type] $message  [description]
     */
    private function addToIosQueue($iosUsers, $theme, $message)
    {
        $data = array(
            "to" => $iosUsers,
            "collapse_key" => $theme->name,
            "data" => array(
                'message' => $message
            )
        );

        var_dump($data);
    }
}