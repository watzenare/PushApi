<?php

namespace PushApi\Controllers;

use \PushApi\Models\Tracking;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Contains the basic and general actions for the API validation.
 */
class TrackingController extends Controller
{
    const PIXEL_IMAGE = "\x47\x49\x46\x38\x37\x61\x1\x0\x1\x0\x80\x0\x0\xfc\x6a\x6c\x0\x0\x0\x2c\x0\x0\x0\x0\x1\x0\x1\x0\x0\x2\x2\x44\x1\x0\x3b";

    /**
     * Tracks users when they open the mail and returns an 1x1 pixel.
     *
     * Call params:
     * @var "receiver" required
     * @var "theme" required
     */
    public function getTrackingPixel()
    {
        $response = $this->slim->response();
        $response->header('Content-Type', 'image/gif');

        if (!isset($this->requestParams['receiver']) && !isset($this->requestParams['theme'])) {
            echo self::PIXEL_IMAGE;
            return;
        }

        $tracking = new Tracking;
        $tracking->email = $this->requestParams['receiver'];
        $tracking->theme = $this->requestParams['theme'];
        $tracking->agent = $this->slim->request->getUserAgent();

        if (isset($this->requestParams['date_sent']) && !empty($this->requestParams['date_sent'])) {
            $tracking->date_sent = $this->requestParams['date_sent'];
        }

        $tracking->save();

        echo self::PIXEL_IMAGE;
    }
}