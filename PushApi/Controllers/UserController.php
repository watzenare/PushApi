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
     * without adding him again into the registration
     */
    public function setUser()
    {
        $email = $this->slim->request->post('email');

        if (!isset($email)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        // Checking if user already exists
        $user = User::where('email', $email)->first();

        if (!isset($user->email)) {
            $user = new User;
            $user->email = $email;
            $user->save();
        }
        $this->send($user->toArray());
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
     */
    public function updateUser($id)
    {
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

        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        foreach ($update as $key => $value) {
            $user->$key = $value;
        }

        $user->update();
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
     * of the created user. If the user is tried to registered twice or
     * has an invalid email, it isn't added again.
     */
    public function setUsers()
    {
        $added = array();
        $emails = $this->slim->request->post('emails');

        if (!isset($emails)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $emails = explode(",", trim($emails));

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
        $this->send($added);
    }
}