<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\App;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions for the API validation.
 */
class AppController extends Controller
{
	/**
	 * Creates a new app into with given params and displays the 
     * information of the created app. If it is tried to register app
     * twice (checked by mail), the information of the registrated app
     * is displayed without adding it again into the registration
	 */
	public function setApp()
	{
        $name = $this->slim->request->post('name');

        if (!isset($name)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $apps = App::get();

        // There's a limit of apps creation and it must be checked
        if (sizeof($apps->toArray()) >= App::MAX_APPS_ENABLED) {
            throw new PushApiException(PushApiException::LIMIT_EXCEEDED);
        }

        // Checks if the app already exists
        $app = App::where('name', $name)->first();

        if (!isset($app->name)) {
            $app = new App;
            $app->name = $name;
            $app->save();
            $this->send($app->toArray());
        } else {
            $this->send(false);
        }
    }

	/**
     * Retrives app information if it is registered
     * @param [int] $id  App identification
     */
	public function getApp($id)
	{
        try {
            $app = App::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($app->toArray());
    }

	/**
     * Updates app infomation given its identification and the param to update
     * @param [int] $id  App identification
     */
	public function updateApp($id)
	{
        $update = array();
        $update['name'] = $this->slim->request->put('name');

        $update = $this->cleanParams($update);

        if (empty($update)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        // Checking if the app exists
        try {
            $app = App::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        foreach ($update as $key => $value) {
            $app->$key = $value;
        }

        $app->update();
        $this->send($app->toArray());
    }

	/**
     * Deletes an app given its identification
     * @param [int] $id  App identification
     */
	public function deleteApp($id)
	{
        try {
            $app = App::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $app->delete();
        $this->send($app->toArray());
    }

	/**
     * Retrives all apps registered
     */
	public function getApps()
	{
        try {
            $app = App::orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($app->toArray());
    }

    /**
     * Checks if the call has an authentication token is valid and lets
     * the app use the PushApi methods or dies if it is an invalid key.
     * In order to authenticate the aplication it is required to send via
     * HTTP headers the following tags:
     *
     * @param 'X-App-Id' that must contain the id of the app that wants to use the API
     * @param 'X-App-Auth' that must contain the authentication key
     *
     * The authentication key is a MD5 hash key obtained from merging 3 values
     * that the agent must know in order:
     *
     * @example md5(application_name + current_date(format = yy-mm-dd) + application_secret)
     */
    public function checkAuth()
    {
        $todayData = date('Y-m-d');

        $appId = $this->slim->request->headers->get('X-App-Id');
        $auth = $this->slim->request->headers->get('X-App-Auth');

        if (isset($appId) && isset($auth)) {
            try {
                $app = App::findOrFail($appId);
            } catch (ModelNotFoundException $e) {
                throw new PushApiException(PushApiException::NOT_AUTHORIZED);
            }
            if (md5($app->name . $todayData . $app->secret) != $auth) {
                throw new PushApiException(PushApiException::NOT_AUTHORIZED);
            }
        } else {
            throw new PushApiException(PushApiException::NOT_AUTHORIZED);
        }
    }
}