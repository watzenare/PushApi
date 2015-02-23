<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
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