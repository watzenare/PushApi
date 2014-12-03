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
use \PushApi\Controllers\QueueController;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the general actions in order to send the messages (retriving actor data and
 * transforming it in order to be correctly queued)
 */
class LogController extends Controller
{
    /**
     * Given the different parameters, it is ordered to check the range of the message and
     * if the users wants to recive that message (included it's preferences email/smartphone).
     * Once the information is obtained it is stored a log of the call and it is queued in
     * order to be sent when server can do it.
     * If user hasn't set preferences from that theme, default send is to all ranges. There is
     * only one possibility that the user doesn't recive notifications, he has to set the preference
     * of that theme. Otherwise, he can recive emails (if smartphones aren't set).
     */
	public function sendMessage()
	{
        $message = $this->slim->request->post('message');
        $theme = $this->slim->request->post('theme');
        $userId = (int) $this->slim->request->post('user_id');
        $channel = $this->slim->request->post('channel');

        /**
         * The most important first values to check are message and theme because if theme it's
         * multicast we don't need to check the other parameters
         */
        if (!isset($message) && !isset($theme)) {
            throw new PushApiException(PushApiException::NO_DATA, "Expected case param");
        }

        // Search if preference exist and if true, it gets all the users that have set preferences.
        $theme = Theme::with('preferences.user')->where('name', $theme)->first();
        if (!$theme) {
            throw new PushApiException(PushApiException::NOT_FOUND, "Theme doesn't exist");
        }

        $users = array();
        $log = new Log;

        switch ($theme->range) {
            // If theme has this range, checks if the user has set its preferences and prepares the message.
            case Theme::UNICAST:

                if (!isset($userId)) {
                    throw new PushApiException(PushApiException::NO_DATA, "Expected user_id param");
                }

                try {
                    $user = false;
                    // Searching user into theme preferences (if the user exist we don't need to do sql search)
                    foreach ($theme->preferences->toArray() as $key => $preferenceUser) {
                        if ($preferenceUser['user']['id'] == $userId) {
                            $user = $preferenceUser['user'];
                            $preference = decbin($preferenceUser['option']);
                        }  
                    }

                    if (!$user) {
                        $user = User::findOrFail($userId);
                        $preference = decbin(Preference::ALL_RANGES);
                    }

                    // Checking if user wants to recive via email
                    if ((Preference::EMAIL & $preference) == Preference::EMAIL) {
                        $this->addToEmailQueue($user['email'], $theme, $message);
                    }
                    // Checking if user wants to recive via smartphone
                    if ((Preference::SMARTPHONE & $preference) == Preference::SMARTPHONE) {
                        if ($user['android_id'] != 0) {
                            $this->addToAndroidQueue($user['android_id'], $theme, $message);
                        }
                        if ($user['ios_id'] != 0) {
                            $this->addToIosQueue($user['ios_id'], $theme, $message);
                        }
                    }
                } catch (ModelNotFoundException $e) {
                    throw new PushApiException(PushApiException::NOT_FOUND);
                }

                // Registering message
                $log->theme_id = $theme->id;
                $log->user_id = $userId;
                $log->message = $message;
                $log->save();
                break;

            // If theme has this range, checks all users subscribed and its preferences. Prepare the log and
            // the messages to be queued
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

                $iosUsers = array();
                $androidUsers = array();
                $usersSubscribers = $channel->subscriptions;

                // Checking user preferences and add the notification to the right queue
                foreach ($usersSubscribers->toArray() as $key => $subscription) {
                    // User hasn't set preferences for that theme, by default recive all devices
                    if (empty($subscription['user']['preferences'][0])) {
                        $preference = decbin(Preference::ALL_RANGES);
                    } else {
                        $preference = decbin($subscription['user']['preferences'][0]['option']);
                    }

                    // Checking if user wants to recive via email
                    if ((Preference::EMAIL & $preference) == Preference::EMAIL) {
                        $this->addToEmailQueue($subscription['user']['email'], $theme, $message);
                    }
                    // Checking if user wants to recive via smartphone
                    if ((Preference::SMARTPHONE & $preference) == Preference::SMARTPHONE) {
                        if ($subscription['user']['android_id'] != 0) {
                            array_push($androidUsers, $subscription['user']['android_id']);
                        }
                        if ($subscription['user']['ios_id'] != 0) {
                            array_push($iosUsers, $subscription['user']['ios_id']);
                        }

                        // Android GMC lets send notifications to 1000 devices with one JSON message,
                        // if there are more >1000 we need to refill the list
                        if (sizeof($androidUsers) == 1000) {
                            $this->addToAndroidQueue($androidUsers, $theme, $message);
                            $androidUsers = array();
                        }
                    }
                }

                if (!empty($androidUsers)) {
                    $this->addToAndroidQueue($androidUsers, $theme, $message);
                }
                if (!empty($iosUsers)) {
                    $this->addToIosQueue($iosUsers, $theme, $message);
                }

                // Registering message
                $log->theme_id = $theme->id;
                $log->channel_id = $channel->id;
                $log->message = $message;
                $log->save();

                break;

            // If theme has this range, checks the preferences for the target theme and send to
            // all users who haven't set option none.
            case Theme::BROADCAST:
                $usersPreferences = $theme->preferences;

                if (empty($usersPreferences->toArray())) {
                    $this->send(false);
                } else {
                    $androidUsers = array();
                    $iosUsers = array();
                    // Checking user preferences and add the notification to the right queue
                    foreach ($usersPreferences->toArray() as $key => $userPreference) {
                        $option = decbin($userPreference['option']);
                        // Checking if user wants to recive via email
                        if ((Preference::EMAIL & $option) == Preference::EMAIL) {
                            $this->addToEmailQueue($userPreference['user']['email'], $theme, $message);
                        }
                        // Checking if user wants to recive via smartphone
                        if ((Preference::SMARTPHONE & $option) == Preference::SMARTPHONE) {
                            if ($userPreference['user']['android_id'] != 0) {
                                array_push($androidUsers, $userPreference['user']['android_id']);
                            }
                            if ($userPreference['user']['ios_id'] != 0) {
                                array_push($iosUsers, $userPreference['user']['ios_id']);
                            }

                            // Android GMC lets send notifications to 1000 devices with one JSON message,
                            // if there are more >1000 we need to refill the list
                            if (sizeof($androidUsers) == 1000) {
                                $this->addToAndroidQueue($androidUsers, $theme, $message);
                                $androidUsers = array();
                            }
                        }
                    }

                    if (!empty($androidUsers)) {
                        $this->addToAndroidQueue($androidUsers, $theme, $message);
                    }
                    if (!empty($iosUsers)) {
                        $this->addToIosQueue($iosUsers, $theme, $message);
                    }

                    // Registering message
                    $log->theme_id = $theme->id;
                    $log->message = $message;
                    $log->save();
                }

                break;
            
            default:
                throw new PushApiException(PushApiException::INVALID_ACTION);
                break;
        }
        $this->send(true);
	}

    /**
     * Generates an array for email data
     * @param [string] $email   The email of the target user
     * @param [string] $theme   The theme of the notification
     * @param [string] $message The message that actor wants to send
     */
	private function addToEmailQueue($email, $theme, $message)
	{
        $data = array(
            "to" => $email,
            "subject" => $theme->name,
            "message" => $message
        );
        (new QueueController())->addToQueue($data, QueueController::EMAIL);
    }

    /**
     * Generates an array for android data
     * @param [array] $androidUsers  One or more ids of android devices
     * @param [string] $theme        The theme of the notification
     * @param [string] $message      The message that actor wants to send
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
        (new QueueController())->addToQueue($data, QueueController::ANDROID);
    }

    /**
     * Generates an array for ios data
     * @param [array] $iosUsers  One or more ids of ios devices
     * @param [string] $theme    The theme of the notification
     * @param [string] $message  The message that actor wants to send
     */
    private function addToIosQueue($iosUsers, $theme, $message)
    {
        $data = array(
            "apple_ids" => $iosUsers,
            "collapse_key" => $theme->name,
            "data" => array(
                'message' => $message
            )
        );
        (new QueueController())->addToQueue($data, QueueController::IOS);
    }
}

/////////////////////////////////////////////////////////////////
// TODO: Add log to addToQueue functions because it won't fail //
/////////////////////////////////////////////////////////////////