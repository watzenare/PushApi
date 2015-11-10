<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Model of the trakings table, manages all the relationships and dependencies
 * that can be done on this table
 */
class Tracking extends Eloquent
{
	public $timestamps = false;
    protected $fillable = array('email', 'theme', 'agent');
    protected $hidden = array('created');
}