<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\User;
use \PushApi\Models\Subscribed;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

class SubscribedController extends Controller
{
    public function setSubscribed($iduser, $idchannel)
    {
        try {
            $user = User::find($iduser)->subscriptions;
            if (isset($user->idchannel)) {
                $this->send($user->toArray());
            } else {
                $user = new User;
                $user->iduser = $iduser;
                $user->idchannel = $idchannel;
                $user->save();
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            var_dump($e);
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($user->toArray());
    }

    // public function getSubscribed($id)
    // {
    //     try {
    //         $subscription = Subscribed::findOrFail($id);
    //     } catch (ModelNotFoundException $e) {
    //         throw new PushApiException(PushApiException::NOT_FOUND);
    //     }
    //     $this->send($subscription->toArray());
    // }

    // public function deleteSubscribed($id)
    // {
    //     try {
    //         $subscription = Subscribed::findOrFail($id);
    //         $subscription->delete();
    //     } catch (ModelNotFoundException $e) {
    //         throw new PushApiException(PushApiException::NOT_FOUND);
    //     }
    //     $this->send($subscription->toArray());
    // }
}