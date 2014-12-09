<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\User;
use \PushApi\Models\Theme;
use \PushApi\Models\Channel;
use \PushApi\Models\Preference;
use \PushApi\Models\Subscription;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions that user can do.
 */
class UserController extends Controller
{
    /**
     * Creates a new user with given params and displays the information
     * of the created user. If the user tries to registrate twice (checked
     * by mail), the information of the registrated user is displayed
     * without adding him again into the registration
     */
    public function setUser()
    {
        try {
            $email = $this->slim->request->post('email');

            if (!isset($email)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            $user = User::where('email', $email)->first();

            if (!isset($user->email)) {
                $user = new User;
                $user->email = $email;
                $user->save();
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($user->toArray());
    }

    /**
     * Retrives user information if it is registered
     * @param [int] $id  User identification
     */
    public function getUser($id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($user->toArray());
    }

    /**
     * Updates user infomation given its identification and params to update
     * @param [int] $id  User identification
     */
    public function updateUser($id)
    {
        try {
            $update = array();
            $update['email'] = $this->slim->request->put('email');
            $update['android_id'] = $this->slim->request->put('android_id');
            $update['ios_id'] = $this->slim->request->put('ios_id');

            $update = $this->cleanParams($update);

            if (empty($update)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if (isset($update['email']) && !filter_var($update['email'], FILTER_VALIDATE_EMAIL)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            $user = User::find($id);
            foreach ($update as $key => $value) {
                $user->$key = $value;
            }
            $user->update();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($user->toArray());
    }

    /**
     * Deletes a user given its identification
     * @param [int] $id  User identification
     */
    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($user->toArray());
    }

    /**
     * Creates new users with given params and displays the information
     * of the created user. If the user is tried to registrate twice or
     * has an invalid email, it isn't added again.
     */
    public function setUsers()
    {
        try {
            $added = array();
            $emails = $this->slim->request->post('emails');

            if (!isset($emails)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            $emails = explode(",", $emails);

            foreach ($emails as $key => $email) {
                if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $user = User::where('email', $email)->first();

                    if (!isset($user->email)) {
                        $user = new User;
                        $user->email = $email;
                        $user->save();
                        array_push($added, $user);
                    }
                }
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($added);
    }

    /**
     * Retrives all users registered
     */
    public function getAllUsers()
    {
        try {
            $user = User::orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($user->toArray());
    }

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
            $this->send($subscriptions->toArray());
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
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
            $this->send($subscription->toArray());
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
    }

    /**
     * Retrives all preferences of a given user
     * @param [int] $idUser User identification
     */
    public function getPreferences($idUser)
    {
        try {
            $preferences = User::findOrFail($idUser)->preferences()->orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($preferences->toArray());
    }

    /**
     * Sets user preference to a given theme, if the preference has
     * been done before, it only displays the information of the preference
     * else, creates the preference and displays the resulting information
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     */
    public function setPreference($idUser, $idTheme)
    {
        try {
            $option = (int) $this->slim->request->post('option');

            if (!isset($option)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if ($update['option'] > Preference::ALL_RANGES) {
                throw new PushApiException(PushApiException::INVALID_OPTION);
            }

            $preference = User::find($idUser)->preferences()->where('theme_id', $idTheme)->first();
            if (!isset($preference) || empty($preference)) {
                if (!empty(User::find($idUser)->toArray()) && !empty(Theme::find($idTheme)->toArray())) {
                    $preference = new Preference;
                    $preference->user_id = $idUser;
                    $preference->theme_id = $idTheme;
                    $preference->option = $option;
                    $preference->save();
                }
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($preference->toArray());
    }

    /**
     * Retrives preference of a given theme
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     */
    public function getPreference($idUser, $idTheme)
    {
        try {
            $preferences = User::findOrFail($idUser)->preferences()->where('theme_id', $idTheme)->orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($preferences->toArray());
    }

    /**
     * Updates user preference given an id theme
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     */
    public function updatePreference($idUser, $idTheme)
    {
        try {
            $update = array();
            $update['option'] = (int) $this->slim->request->post('option');

            if (!isset($update['option'])) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if ($update['option'] > Preference::ALL_RANGES) {
                throw new PushApiException(PushApiException::INVALID_OPTION);
            }

            $user = Preference::where('theme_id', $idTheme)->update($update);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($this->boolinize($user));
    }

    /**
     * Deletes a user preference given a user and a theme id
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     */
    public function deletePreference($idUser, $idTheme)
    {
        try {
            $preference = User::findOrFail($idUser)->preferences()->where('theme_id', $idTheme)->first();
            $preference->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($preference->toArray());
    }
}