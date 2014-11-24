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
    const UNICAST = 'unicast';
    const MULTICAST = 'multicast';
    const BROADCAST = 'broadcast';

    private static $validValues = array(
        self::UNICAST,
        self::MULTICAST,
        self::BROADCAST
    );

    private static $intToString = array(
        0 => self::UNICAST,
        1 => self::MULTICAST,
        2 => self::BROADCAST
    );

    private static $stringToInt = array(
        self::UNICAST => 0,
        self::MULTICAST => 1,
        self::BROADCAST => 2
    );

    public $timestamps = false;
	public $fillable = array('name', 'range');
    protected $hidden = array('created');

    /**
     * Relationship n-1 to get an instance of the preferences table
     * @return [Preferences] Instance of preferences model
     */
    public function preferences()
    {
        return $this->hasMany('\PushApi\Models\Preference');
    }

    /**
     * [getRangeAttribute description]
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public function getRangeAttribute($value)
    {
        return self::$intToString[$value];
    }

    /**
     * [setRangeAttribute description]
     * @param  [type] $value [description]
     */
    public function setRangeAttribute($value)
    {
        $this->attributes['range'] = self::$stringToInt[$value];
    }

    /**
     * [getValidValues description]
     * @return [type] [description]
     */
    public static function getValidValues()
    {
        return self::$validValues;
    }
}