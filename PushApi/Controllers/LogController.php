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
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Contains the general actions in order to send the messages (retriving actor data and
 * transforming it in order to be correctly queued)
 */
class LogController extends Controller
{
    const MAX_DELAY = 3600; // 1 hour in seconds
    const TIME_TO_LIVE = 86400; // 1 day in seconds

    private $androidUsers = array();
    private $iosUsers = array();
    private $message = '';
    private $theme;
    private $subject;
    private $template;
    private $redirect;
    private $delay;
    private $timeToLive;

    /**
     * Given the different parameters, it is ordered to check the range of the message and
     * if the users wants to recive that message (included it's preferences email/smartphone).
     * Once the information is obtained it is stored a log of the call and it is queued in
     * order to be sent when server can do it.
     * If user hasn't set preferences from that theme, default send is to all ranges. There is
     * only one possibility that the user doesn't recive notifications, he has to set the preference
     * of that theme. Otherwise, he can recive emails (if smartphones aren't set).
     *
     * Call params:
     * @var "message" required
     * @var "theme" required
     * @var "user_id"
     * @var "channel"
     */
    public function sendMessage()
    {
        /**
         * The most important first values to check are message and theme because if theme it's
         * multicast we don't need to check the other parameters
         */
        if (!isset($this->requestParams['message']) || !isset($this->requestParams['theme'])) {
            throw new PushApiException(PushApiException::NO_DATA, "Expected case param");
        }

        $this->message = $this->requestParams['message'];
        $this->theme = $this->requestParams['theme'];

        // If user wants, it can be customized the subject of the notification without being default
        if (isset($this->requestParams['subject'])) {
            $this->subject = $this->requestParams['subject'];
        }

        // Default message to send is set in the "message" value but we can send templates via mail and it is set in "template" value
        if (isset($this->requestParams['template'])) {
            $this->template = $this->requestParams['template'];
        }

        /**
         * This option could be used if your app is non-native (you are using PhoneGap or another service)
         * When notification received by the device, we need to redirect the user between the pages. Using
         * this param you can solve this problem.
         */
        if (isset($this->requestParams['redirect'])) {
            $this->redirect = $this->requestParams['redirect'];
        }

        /**
         * If delay is set, notification will be send after the delay time.
         * Delay must be in seconds and a message can't be delayed more than 1 hour.
         */
        if (isset($this->requestParams['delay'])) {
            if ($this->requestParams['delay'] <= self::MAX_DELAY) {
                $this->delay = Date("Y-m-d h:i:s a", time() + $this->requestParams['delay']);
            } else {
                throw new PushApiException(PushApiException::INVALID_OPTION, "Max 'delay' value 3600 (1 hour)");
            }
        }

        /**
         * If the time to live is set, it must be bigger than the default time to live (1 day).
         * If it is not set, the default value is 1 day and it is always set in order to avoid sending messages
         * when workers fail and they are reactivated late.
         * Time to live must be in seconds and a message can't die before 1 day
         */
        if (isset($this->requestParams['time_to_live'])) {
            if ($this->requestParams['time_to_live'] >= self::TIME_TO_LIVE) {
                $this->timeToLive = Date("Y-m-d h:i:s a", time() + $this->requestParams['time_to_live']);
            } else {
                throw new PushApiException(PushApiException::INVALID_OPTION, "Min 'time_to_live' value 86400 (1 day)");
            }
        } else {
            $this->timeToLive = Date("Y-m-d h:i:s a", time() + self::TIME_TO_LIVE);
        }

        // Search if preference exist
        $theme = Theme::where('name', $this->theme)->first();
        if (!$theme) {
            throw new PushApiException(PushApiException::NOT_FOUND, "Theme doesn't exist");
        }

        $log = new Log;

        switch ($theme->range) {
            // If theme has this range, checks if the user has set its preferences and prepares the message.
            case Theme::UNICAST:
                $this->unicastChecker($theme, $log);
                break;

            /**
             * If theme has this range, checks all users subscribed and its preferences. Prepare the
             * log and the messages to be queued
             */
            case Theme::MULTICAST:
                $this->multicastChecker($theme, $log);
                break;

            /**
             * If theme has this range, checks the preferences for the target theme and send to
             * all users who haven't set option none.
             */
            case Theme::BROADCAST:
                $this->broadcastChecker($theme, $log);
                break;

            default:
                throw new PushApiException(PushApiException::INVALID_ACTION);
                break;
        }
        $this->send(true);
    }

    /**
     * Manages the required unicast information in order to generate the right
     * data that will be stored into the queues.
     * @param  [Theme] $theme A theme model with the theme information
     * @param  [Log] $log   An instance of the log model
     */
    private function unicastChecker($theme, $log)
    {
        if (!isset($this->requestParams['user_id'])) {
            throw new PushApiException(PushApiException::NO_DATA, "Expected user_id param");
        }

        $userId = (int) $this->requestParams['user_id'];

        // Preventing to send twice a day the same notification if it is set a sending limitation
        if (SENDING_LIMITATION && $this->messageHasBeenSentBefore($theme, $log, $userId)) {
            return true;
         }

        // Searching if the user has set preferences for that theme in order to get the option
        $user = Preference::with('User')->where('theme_id', $theme->id)->where('user_id', $userId)->first();

        // If we don't find the user, the default preference is to send through all devices
        if (!$user) {
            try {
                $user = User::findOrFail($userId);
            } catch (ModelNotFoundException $e) {
                throw new PushApiException(PushApiException::NOT_FOUND);
            }
            $preference = decbin(Preference::ALL_RANGES);
        } else {
            $user = $user->toArray();
            $preference = $user['option'];
            $user = $user['user'];
        }

        $this->preQueuingDecider(
                $preference,
                array(
                    QueueController::EMAIL => $user['email'],
                    QueueController::ANDROID => $user['android_id'],
                    QueueController::IOS => $user['ios_id'],
                ),
                false
            );

        $log->theme_id = $theme->id;
        $log->user_id = $userId;
        $log->message = $this->message;
        $log->save();
    }

    /**
     * Manages the required multicast information in order to generate the right
     * data that will be stored into the queues.
     * @param  [Theme] $theme A theme model with the theme information
     * @param  [Log] $log   An instance of the log model
     */
    private function multicastChecker($theme, $log)
    {
        if (!isset($this->requestParams['channel'])) {
            throw new PushApiException(PushApiException::NO_DATA, "Expected channel param");
        }

        $channelName = $this->requestParams['channel'];

        try {
            $channel = Channel::with(array('subscriptions.user.preferences' => function($query) use ($theme) {
                return $query->where('theme_id', $theme->id);
            }))->where('name', $channelName)->first();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        if (!isset($channel)) {
            throw new PushApiException(PushApiException::NOT_FOUND, "Channel doesn't exist");
        }

        // Checking user preferences and add the notification to the right queue
        foreach ($channel->subscriptions->toArray() as $key => $subscription) {
            // User hasn't set preferences for that theme, by default receive all devices
            if (empty($subscription['user']['preferences'][0])) {
                $preference = decbin(Preference::ALL_RANGES);
            } else {
                $preference = decbin($subscription['user']['preferences'][0]['option']);
            }

            $this->preQueuingDecider(
                    $preference,
                    array(
                        QueueController::EMAIL =>  $subscription['user']['email'],
                        QueueController::ANDROID =>  $subscription['user']['android_id'],
                        QueueController::IOS =>  $subscription['user']['ios_id'],
                    ),
                    true
                );
        }

        $this->storeToQueues();

        // Registering message
        $log->theme_id = $theme->id;
        $log->channel_id = $channel->id;
        $log->message = $this->message;
        $log->save();
    }

    /**
     * Manages the required broadcast information in order to generate the right
     * data that will be stored into the queues.
     * @param  [Theme] $theme A theme model with the theme information
     * @param  [Log] $log   An instance of the log model
     */
    private function broadcastChecker($theme, $log)
    {
        // Checking user preferences and add the notification to the right queue
        $users = User::orderBy('id', 'asc')->get()->toArray();
        foreach ($users as $key => $user) {
            // Search if the user has set broadcast preferences
            $preference = User::findOrFail($user['id'])
                                ->preferences()
                                ->where('theme_id', $theme->id)
                                ->first();
            // If user has set, it is used that option but if not set, default is all devices
            if (isset($preference)) {
                $preference = $preference->option;
            } else {
                $preference = Preference::ALL_RANGES;
            }

            $this->preQueuingDecider(
                decbin($preference),
                array(
                    QueueController::EMAIL => $user['email'],
                    QueueController::ANDROID => $user['android_id'],
                    QueueController::IOS => $user['ios_id'],
                ),
                true
            );
        }

        $this->storeToQueues();

        // Registering message
        $log->theme_id = $theme->id;
        $log->message = $this->message;
        $log->save();
    }

    /**
     * Checks if a notification has been sent before to the target user on the current day.
     * It checks the Log model if there is a previous row of the target theme name for that user.
     * @param  [Theme] $theme A theme model with the theme information
     * @param  [Log] $log   An instance of the log model
     * @param  [int] $userId   User identification
     * @return [boolean]   Final decision if the limitation is applied
     */
    private function messageHasBeenSentBefore($theme, $log, $userId)
    {
        try {
            $history = $log->where('theme_id', $theme->id)->where('user_id', $userId)->get()->toArray();
            $todayDate = date("Y-m-d");
            foreach ($history as $key => $row) {
                $date = new \DateTime($row['created']);
                if ($todayDate == $date->format("Y-m-d")) {
                    return true;
                }
            }
        } catch (ModelNotFoundException $e) {
            // If no results found there's no problem to send the notification
        }

        return false;
    }

    /**
     * Checks the preferences that user has set foreach device and adds into the right
     * queue, if @param multiple is set, then it will store the smartphone receivers into
     * queues in order to send only one request to the server with all the receivers.
     * @param  [string] $preference User preference
     * @param  [array] $devicesIds     Array of device keys and its ids as values
     * @param  [boolean] $multiple  If there will be more calls with the same class instance (multicast && broadcast types)
     */
    private function preQueuingDecider($preference, $devicesIds, $multiple = false)
    {
        // Checking if user wants to recive via email
        if ((Preference::EMAIL & $preference) == Preference::EMAIL && isset($devicesIds[QueueController::EMAIL])) {
            $this->addToDeviceQueue($devicesIds[QueueController::EMAIL], QueueController::EMAIL);
        }

        if (!$multiple) {
            // Checking if user wants to recive via smartphone
            if ((Preference::SMARTPHONE & $preference) == Preference::SMARTPHONE) {
                if (isset($devicesIds[QueueController::ANDROID]) && !empty($devicesIds[QueueController::ANDROID])) {
                    // Android receivers requires to be stored into an array structure
                    $this->addToDeviceQueue(array($devicesIds[QueueController::ANDROID]), QueueController::ANDROID);
                }
                if (isset($devicesIds[QueueController::IOS]) && !empty($devicesIds[QueueController::IOS])) {
                    $this->addToDeviceQueue($devicesIds[QueueController::IOS], QueueController::IOS);
                }
            }
        } else {
            // Checking if user wants to recive via smartphone
            if ((Preference::SMARTPHONE & $preference) == Preference::SMARTPHONE) {
                if (isset($devicesIds[QueueController::ANDROID]) && !empty($devicesIds[QueueController::ANDROID])) {
                    array_push($this->androidUsers, $devicesIds[QueueController::ANDROID]);
                }
                if (isset($devicesIds[QueueController::IOS]) && !empty($devicesIds[QueueController::IOS])) {
                    array_push($this->iosUsers, $devicesIds[QueueController::IOS]);
                }

                // Android GMC lets send notifications to 1000 devices with one JSON message,
                // if there are more >1000 we need to refill the list
                if (sizeof($this->androidUsers) == 1000) {
                    $this->addToDeviceQueue($this->androidUsers, QueueController::ANDROID);
                    $this->androidUsers = array();
                }
            }
        }
    }

    /**
     * Stores into the right queue the smartphones arrays if those has been set
     */
    private function storeToQueues()
    {
        if (!empty($this->androidUsers)) {
            $this->addToDeviceQueue($this->androidUsers, QueueController::ANDROID);
        }
        if (!empty($this->iosUsers)) {
            $this->addToDeviceQueue($this->iosUsers, QueueController::IOS);
        }
    }

    /**
     * Generates an array of data prepared to be stored in the $device queue
     * @param [string] $receiver   The receiver of the target user
     * @param [string] $device  Destination where the message must be stored
     */
    private function addToDeviceQueue($receiver, $device)
    {
        if (!isset($device)) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }

        $data["to"] = $receiver;
        $data["theme"] = $this->theme;
        $data["message"] = $this->message;

        // All destinations must take into account the delay time
        if (isset($this->delay)) {
            $data["delay"] = $this->delay;
        }

        // All destinations must take into account the timeToLive of a message
        if (isset($this->timeToLive)) {
            $data["timeToLive"] = $this->timeToLive;
        }

        // Depending of the target device, the standard message can be updated
        switch ($device) {
            case QueueController::EMAIL:
                if (isset($this->subject)) {
                    $data["subject"] = $this->subject;
                }

                // If template is set, it is prefered to use it instead of the plain message
                if (isset($this->template)) {
                    $data["message"] = $this->template;
                }
                break;

            case QueueController::IOS:
            case QueueController::ANDROID:
                // Restricting to have a redirect param
                if (REDIRECT_REQUIRED) {
                    if (isset($this->redirect)) {
                        $data["redirect"] = $this->redirect;
                    } else {
                        return false;
                    }
                } else if (isset($this->redirect)) {
                    $data["redirect"] = $this->redirect;
                }
                break;

            default:
                throw new PushApiException(PushApiException::INVALID_ACTION);
                break;
        }

        (new QueueController())->addToQueue($data, $device);
    }
}