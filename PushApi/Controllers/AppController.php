<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\App;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions for the API validation.
 */
class AppController extends Controller
{
	/**
	 * [setApp description]
	 */
	public function setApp()
	{
        try {
            $name = $this->slim->request->post('name');

            if (!isset($name)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }
            $app = App::where('name', $name)->first();

            if (!isset($app->name)) {
                $app = new App;
                $app->name = $name;
                $app->save();
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($app->toArray());
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
        try {
            $update = array();
            $update['name'] = $this->slim->request->put('name');

            $update = $this->cleanParams($update);

            if (empty($update)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            $app = App::where('id', $id)->update($update);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($this->boolinize($app));
    }

	/**
     * Deletes an app given its identification
     * @param [int] $id  App identification
     */
	public function deleteApp($id)
	{
        try {
            $app = App::findOrFail($id);
            $app->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
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
}