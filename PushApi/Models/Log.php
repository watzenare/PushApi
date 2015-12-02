<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Model of the logs table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Log extends Eloquent
{
    public $timestamps = false;
    public $fillable = array('theme_id', 'channel_id', 'user_id', 'message');
    /**
     * Array of theme ids that will be prevented to be stored into the Log database.
     * @var array
     */
    public static $blackList = [];

    /**
     * Stores the main data used to send a notification.
     * It can be disabled some notifications from being logged if it is expected
     * to fill the database with a lot of them (adding theme ids to the blacklist array).
     * @param  array $attributes Array of params used to log the notification.
     * @return  boolean
     */
    public static function storeNotificationLog($attributes)
    {
        // Preventing to store notification if theme is into the blacklist
        if (in_array($attributes['theme_id'], Log::blacklist)) {
            return false;
        }

        $log = new Log;
        $log->theme_id = $attributes['theme_id'];
        $log->message = $attributes['message'];

        if (isset($attributes['user_id'])) {
            $log->user_id = $attributes['user_id'];
        }

        if (isset($attributes['channel_id'])) {
            $log->channel_id = $attributes['channel_id'];
        }

        $log->save();

        return true;
    }
}
