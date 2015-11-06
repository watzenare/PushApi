<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Model of the logs table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Log extends Eloquent
{
    public $timestamps = false;
	public $fillable = array('theme_id', 'channel_id', 'user_id', 'message');
}