<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Controllers\Controller;
use \PushApi\Models\User;
use \PushApi\Models\Channel;
use \PushApi\Models\Subscription;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions for managing subscriptions.
 */
class SubscriptionController extends Controller
{
    /**
     * Subscribes a user to a given channel, if the subscription has
     * been done before, it only displays the information of the subscription
     * else, creates the subscription and displays the resulting information
     * @param [int] $idUser    User identification
     * @param [int] $idChannel Channel identification
     */
    public function setSubscribed($idUser, $idChannel)
    {
        try {
            $subscription = User::find($idUser)->subscriptions()->where('channel_id', $idChannel)->first();
            if (!isset($subscription) || empty($subscription)) {
                if (!empty(User::find($idUser)->toArray()) && !empty(Channel::find($idChannel)->toArray())) {
                    $subscription = new Subscription;
                    $subscription->user_id = $idUser;
                    $subscription->channel_id = $idChannel;
                    $subscription->save();
                }
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($subscription->toArray());
    }

    /**
     * Retrives all subscriptions of a given user or it also can check
     * if user is subscribed into a channel (if he is subscribed, the
     * subscription is displayed)
     * @param [int] $idUser    User identification
     * @param [int] $idChannel Channel identification
     */
    public function getSubscribed($idUser, $idChannel = false)
    {
        try {
            if ($idChannel) {
                $subscriptions = User::findOrFail($idUser)->subscriptions()->where('channel_id', $idChannel)->first();
            } else {
                $subscriptions = User::findOrFail($idUser)->subscriptions;
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($subscriptions->toArray());
    }

    /**
     * Deletes a user subscription given a user and a subscription id
     * @param [int] $idUser    User identification
     * @param [int] $idChannel Channel identification
     */
    public function deleteSubscribed($idUser, $idChannel)
    {
        try {
            $subscription = User::findOrFail($idUser)->subscriptions()->where('channel_id', $idChannel)->first();
            $subscription->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($subscription->toArray());
    }
}