<?php

namespace PushApi\Controllers;

use \PushApi\PushApi;
use \PushApi\PushApiException;
use \PushApi\Models\Subscription;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Contains the basic and general actions for managing subscriptions.
 */
class SubscriptionController extends Controller
{
    /**
     * Subscribes a user to a given channel, if the subscription has
     * been done before, it only displays the information of the subscription
     * else, creates the subscription and displays the resulting information.
     * @param int $idUser    User identification
     * @param int $idChannel Channel identification
     * @throws PushApiException
     */
    public function setSubscribed($idUser, $idChannel)
    {
        if (!$subscription = Subscription::createSubscription($idUser, $idChannel)) {
            throw new PushApiException(PushApiException::ACTION_FAILED);
        }

        $this->send($subscription);
    }

    /**
     * Retrieves all subscriptions of a given user or it also can check
     * if user is subscribed into a channel (if he is subscribed, the
     * subscription is displayed).
     * @param int $idUser    User identification
     * @param int $idChannel Channel identification
     * @throws PushApiException
     */
    public function getSubscription($idUser, $idChannel)
    {
        if (!$subscription = Subscription::getSubscription($idUser, $idChannel)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($subscription);
    }

    /**
     * Deletes a user subscription given a user and a subscription id.
     * @param int $idUser    User identification.
     * @param int $idChannel Channel identification.
     * @throws PushApiException
     */
    public function deleteSubscription($idUser, $idChannel)
    {
        if (!$subscription = Subscription::remove($idUser, $idChannel)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($subscription);
    }

    /**
     * Retrieves all user subscriptions registered.
     * @param  int $idUser User identification.
     * @return array
     * @throws PushApiException
     */
    public function getSubscriptions($idUser)
    {
        if (!$subscriptions = Subscription::getSubscriptions($idUser)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($subscriptions);
    }
}