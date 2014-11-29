<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Model of the themes table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Theme extends Eloquent
{
    // Range values
    const UNICAST = 'unicast';
    const MULTICAST = 'multicast';
    const BROADCAST = 'broadcast';

    public $timestamps = false;
	public $fillable = array('name', 'range');
    protected $hidden = array('created');

    private static $validValues = array(
        self::UNICAST,
        self::MULTICAST,
        self::BROADCAST
    );

    /**
     * Relationship n-1 to get an instance of the preferences table
     * @return [Preferences] Istance of preferences model
     */
    public function preferences()
    {
        return $this->hasMany('\PushApi\Models\Preference');
    }

    /**
     * Returns the valid values that accepts Theme model
     * @return [array] Array with the accepted constants 
     */
    public static function getValidValues()
    {
        return self::$validValues;
    }

    /**
     * Relationship n-1 to get an instance of the logs table
     * @return [Log] Instance of Log model
     */
    public function logs()
    {
        return $this->hasMany('\PushApi\Models\Log');
    }
}