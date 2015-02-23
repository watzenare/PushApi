<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Tracking;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions for the API validation.
 */
class TrackingController extends Controller
{

    public function getTrackingPixel()
    {
        $agent = $this->slim->request->getUserAgent();
        $tracking = new Tracking;
        $tracking->email = $this->requestParams['receiver'];
        $tracking->theme = $this->requestParams['theme'];
        $tracking->agent = $agent;
        $tracking->save();

        $response = $this->slim->response();
        $response->header('Content-Type', 'image/gif');
        echo "\x47\x49\x46\x38\x37\x61\x1\x0\x1\x0\x80\x0\x0\xfc\x6a\x6c\x0\x0\x0\x2c\x0\x0\x0\x0\x1\x0\x1\x0\x0\x2\x2\x44\x1\x0\x3b";
    }
}