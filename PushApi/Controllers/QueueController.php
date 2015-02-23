<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Controllers\Controller;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Controlls the various actions that can be done into the queues
 */
class QueueController extends Controller
 {
    const EMAIL = "email";
    const ANDROID = "android";
    const IOS = "ios";

    /**
     * Adds at the end of the specific queue the data recived
     * @param [array] $data   Data we want to add into the queue
     * @param [string] $target The target queue name
     * @return [boolean]       Success of the operation
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
     * Retrieves the data data of the queue from the begining of the target queue
     * @param  [string] $target The target queue name
     * @return [array/boolean] The data in the queue
     */
    public function getFromQueue($target)
    {
        switch ($target) {
            case self::EMAIL:
                $element = $this->redis->blPop(self::EMAIL, 0);
                return json_decode($element[1]);
                break;

            case self::ANDROID:
                $element = $this->redis->lPop(self::ANDROID, 0);
                return json_decode($element[1]);
                break;

            case self::IOS:
                $element = $this->redis->lPop(self::IOS, 0);
                return json_decode($element[1]);
                break;

            default:
                return false;
                break;
        }
    }
 }