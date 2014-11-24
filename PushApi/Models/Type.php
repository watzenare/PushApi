<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Model of the types table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Type extends Eloquent
{
    // Range values
    const UNICAST = 0;
    const MULTICAST = 1;
    const BROADCAST = 2;

    public $timestamps = false;
	public $fillable = array('name', 'range');
    protected $hidden = array('created');

    /**
     * Relationship n-1 to get an instance of the preferences table
     * @return [Preferences] Instance of preferences model
     */
    public function preferences()
    {
        return $this->hasMany('\PushApi\Models\Preferences');
    }
}