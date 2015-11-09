<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\User;
use \PushApi\Models\Device;
use \PushApi\Controllers\Controller;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
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
     * Request params:
     * @var "email" required
     * @throws PushApiException
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

        try {
            $this->send(User::createUser($email));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Retrives the user information if it is registered.
     * @param int $id  User identification
     * @throws PushApiException
     */
    public function getUser($id)
    {
        try {
            $this->send(User::get($id));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Deletes a user given its identification
     * @param int $id  User identification
     * @throws PushApiException
     */
    public function deleteUser($id)
    {
        try {
            $deleted = User::remove($id);
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }

        $this->send($deleted);
    }

    /**
     * Updates user infomation given its identification and params to update.
     * @param int $id  User identification
     *
     * Request params:
     * @var "email" optional
     * @var "android" optional
     * @var "ios" optional
     * @throws PushApiException
     */
    public function addUserDevice($id)
    {
        $update = array();

        if (isset($this->requestParams['email'])) {
            $update[Device::TYPE_EMAIL] = $this->requestParams['email'];
        }

        if (isset($this->requestParams['android'])) {
            $update[Device::TYPE_ANDROID] = $this->requestParams['android'];
        }

        if (isset($this->requestParams['ios'])) {
            $update[Device::TYPE_IOS] = $this->requestParams['ios'];
        }

        $update = $this->cleanParams($update);

        if (empty($update)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        if (isset($update['email']) && !filter_var($update['email'], FILTER_VALIDATE_EMAIL)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        foreach ($update as $type => $reference) {
            Device::addDevice($id, $type, $reference);
        }

        try {
            $this->send(User::get($id));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Gets device information given its device id.
     * @param  int $id  User identification
     * @param  int $idDevice Device identification
     * @throws PushApiException
     */
    public function getUserDeviceInfo($id, $idDevice)
    {
        try {
            $this->send(Device::get($id, $idDevice));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Gets device information without knowing its id but knowing its reference identification.
     * @param  int $id  User identification
     *
     * Request params:
     * @var "reference" required
     * @throws PushApiException
     */
    public function getUserDeviceInfoByParams($id)
    {
        if (!isset($this->requestParams['reference'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $reference = $this->requestParams['reference'];

        try {
            $this->send(Device::getIdByReference($id, $reference));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Removes a device form user.
     * @param  int $id  User identification
     * @param  int $idDevice Device identification
     * @throws PushApiException
     */
    public function removeUserDevice($id, $idDevice)
    {
        Device::removeDeviceById($id, $idDevice);

        try {
            $this->send(User::get($id));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Retrives all users registred.
     * @var "limit" optional
     * @var "page" optional
     * @throws PushApiException
     */
    public function getUsers()
    {
        $limit = (isset($this->requestParams['limit']) ? $this->requestParams['limit'] : 10);
        $page = (isset($this->requestParams['page']) ? $this->requestParams['page'] : 1);

        if (isset($limit) && $limit < 0) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid limit value");
        }

        if (isset($page) && $page < 1) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid page value");
        }

        try {
            $this->send(User::getUsers($limit, $page));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Creates new users with given params and displays the information
     * of the created user. If the user is tried to registered twice or
     * has an invalid email, it isn't added again.
     *
     * Request params:
     * @var "emails" required
     * @throws PushApiException
     */
    public function setUsers()
    {
        $added = [];

        if (!isset($this->requestParams['emails'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $emails = json_decode($this->requestParams['emails']);

        foreach ($emails as $email) {
            if (!empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = User::createUser($email);
                if ($user) {
                    array_push($added, $user);
                }
            }
        }

        try {
            $this->send($added);
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Retrives the smartphones that user has registered.
     * @param int $id  User identification
     * @throws PushApiException
     */
    public function getSmartphonesRegistered($id)
    {
        $smartphones = [];

        if (!$user = User::checkExists($id)) {
            $this->send($smartphones);
        }

        if ($user->android != 0) {
            $smartphones[] = "Android";
        }

        if ($user->ios != 0) {
            $smartphones[] = "iOs";
        }

        try {
            $this->send($smartphones);
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }
}