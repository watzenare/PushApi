<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Preference;
use \PushApi\Controllers\Controller;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Controlls the various actions that can be done into the queues.
 */
class QueueController extends Controller
{
    const EMAIL = "email";
    const ANDROID = "android";
    const IOS = "ios";

    /**
     * All data that should be stored to send the notification.
     * @var array
     */
    private $params = [];
    private $references = [
        self::ANDROID => [],
        self::IOS => [],
    ];

    /**
     * Basic setter of the private $params variable.
     * @param string $key
     * @param int/string/boolean $value
     */
    public function addParams($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * Basic getter from the private $params variable.
     * @param  string $key
     * @return int/string/boolean
     */
    public function getParams($key = false)
    {
        if ($key && isset($this->params[$key])) {
            return $this->params[$key];
        } else if ($key && !isset($this->params[$key])) {
            return false;
        } else {
            return $this->params;
        }
    }

    /**
     * Adds at the end of the specific queue the data received
     * @param array $data   Data we want to add into the queue
     * @param string $target The target queue name
     * @return boolean       Success of the operation
     */
    public function addToQueue($data, $target)
    {
        switch ($target) {
            case self::EMAIL:
                $this->redis->rPush(self::EMAIL, json_encode($data));
                break;

            case self::ANDROID:
                $this->redis->rPush(self::ANDROID, json_encode($data));
                break;

            case self::IOS:
                $this->redis->rPush(self::IOS, json_encode($data));
                break;

            default:
                return false;
                break;
        }

        return true;
    }

    /**
     * Retrieves the data data of the queue from the beginning of the target queue
     * @param  string $target The target queue name
     * @return array/boolean The data in the queue
     */
    public function getFromQueue($target)
    {
        switch ($target) {
            case self::EMAIL:
                $element = $this->redis->blPop(self::EMAIL, 0);
                return json_decode($element[1]);

            case self::ANDROID:
                $element = $this->redis->blPop(self::ANDROID, 0);
                return json_decode($element[1]);

            case self::IOS:
                $element = $this->redis->blPop(self::IOS, 0);
                return json_decode($element[1]);

            default:
                return false;
        }
    }

    /**
     * Checks the preferences that user has set foreach device and adds into the right
     * queue, if @param multiple is set, then it will store the smartphone receivers into
     * queues in order to send only one request to the server with all the receivers.
     * @param  string $preference User preference.
     * @param  array $devicesIds  Array of device keys and its ids as values.
     * @param  boolean $multiple  If there will be more calls with the same class instance (multicast && broadcast types).
     * @throws  PushApiException
     */
    public function preQueuingDecider($preference, $devicesIds, $multiple = false)
    {
        // Checking if user wants to receive via email
        if ((Preference::EMAIL & $preference) == Preference::EMAIL && isset($devicesIds[self::EMAIL])) {
            $this->addEachDeviceToQueue($devicesIds[self::EMAIL], self::EMAIL);
        }

        if (!$multiple) {
            // Checking if user wants to receive via smartphone
            if ((Preference::SMARTPHONE & $preference) == Preference::SMARTPHONE) {
                if (isset($devicesIds[self::ANDROID]) && !empty($devicesIds[self::ANDROID])) {
                    // Android receivers requires to be stored into an array structure
                    $this->addEachDeviceToQueue($devicesIds[self::ANDROID], self::ANDROID);
                }
                if (isset($devicesIds[self::IOS]) && !empty($devicesIds[self::IOS])) {
                    $this->addEachDeviceToQueue($devicesIds[self::IOS], self::IOS);
                }
            }
        } else {
            // Checking if user wants to receive via smartphone
            if ((Preference::SMARTPHONE & $preference) == Preference::SMARTPHONE) {
                if (isset($devicesIds[self::ANDROID]) && !empty($devicesIds[self::ANDROID])) {
                    $this->references[self::ANDROID] = $this->addReferencesToArray($devicesIds[self::ANDROID]);
                }
                if (isset($devicesIds[self::IOS]) && !empty($devicesIds[self::IOS])) {
                    $this->references[self IOS] = $this->addReferencesToArray($devicesIds[self::IOS]);
                }

                // Android GMC lets send notifications to 1000 devices with one JSON message,
                // if there are more >1000 we need to refill the list
                if (sizeof($this->references[self::ANDROID]) == 1000) {
                    $this->params['receiver'] = $this->references[self::ANDROID];
                    $this->addToDeviceQueue(self::ANDROID);
                    $this->references[self::ANDROID] = [];
                }
            }
        }
    }

    /**
     * Obtains all destination references of the target user given the target device.
     * @param  array $references
     * @param  string $device
     */
    private function addEachDeviceToQueue($references, $device)
    {
        // When target has various mail address, it should be send one by one.
        if ($device == self::EMAIL) {
            foreach ($references as $deviceId => $reference) {
                $this->params['receiver'] = $reference;
                $this->addToDeviceQueue($device);
            }
        } else {
            // When target has only one reference, the receiver information should be managed different depending
            // of the target device to send.
            if (sizeof($references) == 1) {
                if ($device == self::IOS) {
                    // Ios does not require to receive one device into an array
                    $this->params['receiver'] = array_values($references)[0];
                } else {
                    // Android requires to be stored into an array structure even if the target is one device.
                    $this->params['receiver'] = array(array_values($references)[0]);
                }
                $this->addToDeviceQueue($device);
                // When target has various device references, it will be send in the same GCM message.
            } else {
                $receivers = [];
                foreach ($references as $deviceId => $reference) {
                    $receivers[] = $reference;
                }
                $this->params['receiver'] = $receivers;
                $this->addToDeviceQueue($device);
            }
        }
    }

    /**
     * Obtains all destination references and adds them to an array of references.
     * @param array $devices
     */
    private function addReferencesToArray($devices)
    {
        $temporalArray = [];
        foreach ($devices as $deviceId => $reference) {
            array_push($temporalArray, $reference);
        }

        return $temporalArray;
    }

    /**
     * Stores into the right queue the smartphones arrays if those has been set.
     */
    public function storeToQueues()
    {
        if (!empty($this->references[self::ANDROID])) {
            $this->params['receiver'] = $this->references[self::ANDROID];
            $this->addToDeviceQueue(self::ANDROID);
        }

        if (!empty($this->references[self::IOS])) {
            $this->params['receiver'] = $this->references[self::IOS];
            $this->addToDeviceQueue(self::IOS);
        }
    }

    /**
     * Generates an array of data prepared to be stored in the $device queue.
     * @param string $device  Destination where the message must be stored.
     * @throws  PushApiException
     */
    private function addToDeviceQueue($device)
    {
        if (!isset($device)) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }

        $validData["to"] = $this->params['receiver'];
        $validData["theme"] = $this->params['theme'];
        $validData["message"] = $this->params['message'];

        // All destinations must take into account the delay time
        if (isset($this->params['delay'])) {
            $validData["delay"] = $this->params['delay'];
        }

        // All destinations must take into account the timeToLive of a message
        if (isset($this->params['timeToLive'])) {
            $validData["timeToLive"] = $this->params['timeToLive'];
        }

        // Depending of the target device, the standard message can be updated
        switch ($device) {
            case self::EMAIL:
                if (isset($this->params['subject'])) {
                    $validData["subject"] = $this->params['subject'];
                }

                // If template is set, it is prefered to use it instead of the plain message
                if (isset($this->params['template'])) {
                    $validData["message"] = $this->params['template'];
                }
                break;

            case self::IOS:
            case self::ANDROID:
                // Restricting to have a redirect param
                if (REDIRECT_REQUIRED) {
                    if (isset($this->params['redirect'])) {
                        $validData["redirect"] = $this->params['redirect'];
                    } else {
                        return false;
                    }
                } else if (isset($this->params['redirect'])) {
                    $validData["redirect"] = $this->params['redirect'];
                }
                break;

            default:
                throw new PushApiException(PushApiException::INVALID_ACTION);
                break;
        }

        $this->addToQueue($validData, $device);
    }
}