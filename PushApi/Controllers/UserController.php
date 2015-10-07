<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
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
 * Contains the basic and general actions that user can do.
 */
class UserController extends Controller
{
    /**
     * Creates a new user with given params and displays the information
     * of the created user. If the user tries to registrate twice (checked
     * by mail), the information of the registrated user is displayed
     * without adding him again into the registration.
     *
     * Call params:
     * @var "email" required
     */
    public function setUser()
    {
        if (!isset($this->requestParams['email'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $email = $this->requestParams['email'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new PushApiException(PushApiException::INVALID_DATA);
        }

        // Checking if user already exists
        $user = User::where('email', $email)->first();

        if (!isset($user->email)) {
            $user = new User;
            $user->email = $email;
            $user->save();
        }

        if ($user == null) {
            $user = [];
        } else {
            $user = $user->toArray();
        }

        $this->send($user);
    }

    /**
     * Retrives all users registred or the user information if it is registered
     * @param [int] $id  User identification
     */
    public function getUser($id = false)
    {
        try {
            if (!$id) {
                $user = User::orderBy('id', 'asc')->get();
            } else {
                $user = User::findOrFail($id);
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($user->toArray());
    }

    /**
     * Updates user infomation given its identification and params to update
     * @param [int] $id  User identification
     *
     * Call params:
     * @var "email" optional
     * @var "android_id" optional
     * @var "ios_id" optional
     */
    public function updateUser($id)
    {
        $update = array();

        if (isset($this->requestParams['email'])) {
            $update['email'] = $this->requestParams['email'];
        }

        if (isset($this->requestParams['android_id'])) {
            $update['android_id'] = $this->requestParams['android_id'];
        }

        if (isset($this->requestParams['ios_id'])) {
            $update['ios_id'] = $this->requestParams['ios_id'];
        }

        $update = $this->cleanParams($update);

        if (empty($update)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        if (isset($update['email']) && !filter_var($update['email'], FILTER_VALIDATE_EMAIL)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        try {
            $userToUpdate = User::findOrFail($id);
            // Prevent that two users have the same device id
            foreach ($update as $key => $value) {
                if ($key == 'email') {
                    $user = User::where($key, $update[$key])->first();
                    // If user wants to set the same email of another user, this can't be changed
                    if ($user) {
                        $this->send($userToUpdate->toArray());
                    }
                } else {
                    $user = User::where($key, $update[$key])->first();
                    if ($user && ($user != $userToUpdate)) {
                        $user->$key = 0;
                        $user->update();
                    }
                }
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        foreach ($update as $key => $value) {
            $userToUpdate->$key = $value;
        }

        $userToUpdate->update();
        $this->send($userToUpdate->toArray());
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
     * of the created user. If the user is tried to registered twice or
     * has an invalid email, it isn't added again.
     *
     * Call params:
     * @var "emails" required
     */
    public function setUsers()
    {
        $added = array();

        if (!isset($this->requestParams['emails'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $emails = preg_replace('/\s+/', '', $this->requestParams['emails']);
        $emails = explode(",", $emails);

        foreach ($emails as $key => $email) {
            if (!empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = User::where('email', $email)->first();

                if (!isset($user->email)) {
                    $user = new User;
                    $user->email = $email;
                    $user->save();
                    array_push($added, $user);
                }
            }
        }
        $this->send($added);
    }

    /**
     * Retrives the smartphones that user has registered.
     * @param [int] $id  User identification
     */
    public function getSmartphonesRegistered($id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        if (!empty($user->android_id) && !empty($user->ios_id)) {
            $smartphone = ["Android", "iOs"];
        } else if (!empty($user->android_id) && empty($user->ios_id)) {
            $smartphone = ["Android"];
        } else if (empty($user->android_id) && !empty($user->ios_id)) {
            $smartphone = ["iOs"];
        } else if (empty($user->android_id) && empty($user->ios_id)) {
            $smartphone = [];
        }

        $this->send($smartphone);
    }
}