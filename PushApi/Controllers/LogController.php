<?php

namespace PushApi\Controllers;

use \PushApi\PushApi;
use \PushApi\PushApiException;
use \PushApi\Models\Log;
use \PushApi\Models\User;
use \PushApi\Models\Theme;
use \PushApi\Models\Channel;
use \PushApi\Models\Preference;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Contains the general actions in order to send the messages (retrieving actor data and
 * transforming it in order to be correctly queued)
 */
class LogController extends Controller
{
    const MAX_DELAY = 3600; // 1 hour in seconds
    const TIME_TO_LIVE = 86400; // 1 day in seconds

    private $queueController;

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->queueController = new QueueController();
    }

    /**
     * Given the different parameters, it is ordered to check the range of the message and
     * if the users wants to receive that message (included it's preferences email/smartphone).
     * Once the information is obtained it is stored a log of the call and it is queued in
     * order to be sent when server can do it.
     * If user has not set preferences from that theme, default send is to all ranges. There is
     * only one possibility that the user does not receive notifications, he has to set the preference
     * of that theme. Otherwise, he can receive emails (if smartphones are not set).
     * @throws  PushApiException
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
         * multicast we don't need to check the other parameters.
         */
        if (!isset($this->requestParams['message']) || !isset($this->requestParams['theme'])) {
            throw new PushApiException(PushApiException::NO_DATA, "Expected required params");
        }

        $this->queueController->addParams('message', $this->requestParams['message']);
        $this->queueController->addParams('theme', $this->requestParams['theme']);

        // If user wants, it can be customized the subject of the notification without being default
        if (isset($this->requestParams['subject'])) {
            $this->queueController->addParams('subject', $this->requestParams['subject']);
        }

        // Default message to send is set in the "message" value but we can send templates via mail and it is set in "template" value
        if (isset($this->requestParams['template'])) {
            $this->queueController->addParams('template', $this->requestParams['template']);
        }

        /**
         * This option could be used if your app is non-native (you are using PhoneGap or another service).
         * When notification received by the device, we need to redirect the user between the pages. Using
         * this param you can solve this problem.
         */
        if (isset($this->requestParams['redirect'])) {
            $this->queueController->addParams('redirect', $this->requestParams['redirect']);
        }

        /**
         * If delay is set, notification will be send after the delay time.
         * Delay must be in seconds and a message can't be delayed more than 1 hour.
         */
        if (isset($this->requestParams['delay'])) {
            if ($this->requestParams['delay'] <= self::MAX_DELAY) {
                $delay = Date("Y-m-d h:i:s a", time() + $this->requestParams['delay']);
                $this->queueController->addParams('delay', $delay);
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
                $timeToLive = Date("Y-m-d h:i:s a", time() + $this->requestParams['time_to_live']);
            } else {
                throw new PushApiException(PushApiException::INVALID_OPTION, "Min 'time_to_live' value 86400 (1 day)");
            }
        } else {
            $timeToLive = Date("Y-m-d h:i:s a", time() + self::TIME_TO_LIVE);
        }
        $this->queueController->addParams('timeToLive', $timeToLive);

        // Search if preference exist
        $theme = Theme::getInfoByName($this->queueController->getParams('theme'));

        switch ($theme['range']) {
            // If theme has this range, checks if the user has set its preferences and prepares the message.
            case Theme::RANGE_UNICAST:
                $result = $this->unicastChecker($theme);
                break;

            /**
             * If theme has this range, checks all users subscribed and its preferences. Prepare the
             * log and the messages to be queued
             */
            case Theme::RANGE_MULTICAST:
                $result = $this->multicastChecker($theme);
                break;

            /**
             * If theme has this range, checks the preferences for the target theme and send to
             * all users who haven't set option none.
             */
            case Theme::RANGE_BROADCAST:
                $result = $this->broadcastChecker($theme);
                break;

            default:
                throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($result);
    }

    /**
     * Manages the required unicast information in order to generate the right
     * data that will be stored into the queues.
     * @param  Theme $theme A theme model with the theme information.
     * @throws  PushApiException
     */
    private function unicastChecker($theme)
    {
        if (!isset($this->requestParams['user_id'])) {
            throw new PushApiException(PushApiException::NO_DATA, "Expected user_id param");
        }

        $userId = (int) $this->requestParams['user_id'];

        /*
         * Preventing to send twice a day the same notification if it is set a sending limitation.
         * If theme is added into the blacklist, this functionality is pointless.
         */
        if (SENDING_LIMITATION && $this->messageHasBeenSentBefore($theme, $userId)) {
            PushApi::log(__METHOD__ . " - Unicast not send, message has been already sent today", \Slim\Log::INFO);
            return false;
         }

        // Searching if the user has set preferences for that theme in order to get the option
        $userPreference = Preference::getPreference($userId, $theme['id']);

        // If we don't find the user, the default preference is to send through all devices
        if (!$userPreference) {
            $user = User::getUser($userId);

            // Checking if user exists
            if (!$user) {
                PushApi::log(__METHOD__ . " - Unicast not sent due to not found values (user $userId not found)", \Slim\Log::WARN);
                return false;
            }

            $preference = Preference::ALL_RANGES;
        } else {
            if ($userPreference['option'] == Preference::NOTHING) {
                PushApi::log(__METHOD__ . " - Unicast preference set as do not want to receive anything, message not sent", \Slim\Log::INFO);
                return false;
            } else {
                $preference = $userPreference['option'];
                $user = User::getUser($userId);
            }
        }

        $this->queueController->preQueuingDecider(
            decbin($preference),
            [
                QueueController::EMAIL => $user['email'],
                QueueController::ANDROID => $user['android'],
                QueueController::IOS => $user['ios'],
            ],
            false
        );

        // Registering message
        $attributes = [
            "theme_id" => $theme['id'],
            "user_id" => $userId,
            "message" => $this->queueController->getParams('message'),
        ];
        Log::storeNotificationLog($attributes);

        return true;
    }

    /**
     * Manages the required multicast information in order to generate the right
     * data that will be stored into the queues.
     * @param  Theme $theme A theme model with the theme information.
     * @throws  PushApiException
     */
    private function multicastChecker($theme)
    {
        if (!isset($this->requestParams['channel'])) {
            throw new PushApiException(PushApiException::NO_DATA, "Expected channel param");
        }

        $channelName = $this->requestParams['channel'];

        $channel = Channel::getInfoByName($channelName);

        if (!$channel) {
            PushApi::log(__METHOD__ . " - Multicast not sent due to not found values (channelName $channelName)", \Slim\Log::WARN);
            return false;
        }

        $subscriptions = Channel::getSubscribers($channel['name']);

        if (!$subscriptions) {
            PushApi::log(__METHOD__ . " - Multicast not sent due to not found values (no subscribers to channel)", \Slim\Log::WARN);
            return false;
        }

        // Checking user preferences and add the notification to the right queue
        foreach ($subscriptions as $user) {
            $userPreference = Preference::getPreference($user['id'], $theme['id']);
            if (!$userPreference) {
                // User hasn't set preferences for that theme, by default receive all devices
                $preference = Preference::ALL_RANGES;
            } else {
                if ($userPreference['option'] == Preference::NOTHING) {
                    continue;
                } else {
                    $preference = $userPreference['option'];
                }
            }

            $this->queueController->preQueuingDecider(
                decbin($preference),
                [
                    QueueController::EMAIL => $user['email'],
                    QueueController::ANDROID => $user['android'],
                    QueueController::IOS => $user['ios'],
                ],
                true
            );
        }

        // Storing users to queue
        $this->queueController->storeToQueues();

        // Registering message
        $attributes = [
            "theme_id" => $theme['id'],
            "channel_id" => $channel['id'],
            "message" => $this->queueController->getParams('message'),
        ];
        Log::storeNotificationLog($attributes);

        return true;
    }

    /**
     * Manages the required broadcast information in order to generate the right
     * data that will be stored into the queues.
     * @param  Theme $theme A theme model with the theme information.
     * @throws  PushApiException
     */
    private function broadcastChecker($theme)
    {
        $skip = 0;
        $offset = 25;

        // Checking user preferences and add the notification to the right queue
        while ($users = User::orderBy('id', 'asc')->take($offset)->offset($skip * $offset)->get()) {
            $users = User::orderBy('id', 'asc')->get()->toArray();
            foreach ($users as $key => $user) {
                $user = User::getUser($user['id']);
                // Search if the user has set broadcast preferences
                $preference = Preference::checkExistsUserPreference($user['id'], $theme['id']);
                // If user has set, it is used that option but if not set, default is all devices
                if ($preference) {
                    if ($preference->option == Preference::NOTHING) {
                        PushApi::log(__METHOD__ . " - Broadcast preference set as do not want to receive anything, message not sent", \Slim\Log::INFO);
                        continue;
                    } else {
                        $preference = $preference->option;
                    }
                } else {
                    $preference = Preference::ALL_RANGES;
                }

                $this->queueController->preQueuingDecider(
                    decbin($preference),
                    [
                        QueueController::EMAIL => $user['email'],
                        QueueController::ANDROID => $user['android'],
                        QueueController::IOS => $user['ios'],
                    ],
                    true
                );
            }
            $skip++;
        }

        // Storing pre-queuing results to each queue
        $this->queueController->storeToQueues();

        // Registering message
        $attributes = [
            "theme_id" => $theme['id'],
            "message" => $this->queueController->getParams('message'),
        ];
        Log::storeNotificationLog($attributes);

        return true;
    }

    /**
     * Checks if a notification has been sent before to the target user on the current day.
     * It checks the Log model if there is a previous row of the target theme name for that user.
     * @param  Theme $theme A theme model with the theme information.
     * @param  int $userId   User identification.
     * @return boolean   Final decision if the limitation is applied.
     * @throws  PushApiException
     */
    private function messageHasBeenSentBefore($theme, $userId)
    {
        $log = new Log;
        try {
            $history = $log->where('theme_id', $theme['id'])->where('user_id', $userId)->get()->toArray();
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
}