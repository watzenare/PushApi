<?php

namespace PushApi\Controllers;

use \PushApi\Controllers\Controller;
use \PushApi\PushApiException;
use \PushApi\Models\User;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use \Illuminate\Database\QueryException;

class UserController extends Controller
{
    public function setUser()
    {
        try {
            $email = $this->slim->request->post('email');

            if (!isset($email)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }
            $user = User::where('email', $email)->first();

            if (isset($user->email)) {
                $this->send($user->toArray());
            } else {
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

    public function getUser($id)
    {
        try {
            $user = User::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($user->toArray());
    }

    public function updateUser($id)
    {
        try {
            $email = $this->slim->request->put('email');

            if (!isset($email)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }
            $user = User::where('id', $id)->update(array('email' => $data['email']));
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($user->toArray());
    }

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

    public function getAllUsers()
    {
        try {
            $user = User::orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($user->toArray());
    }
}