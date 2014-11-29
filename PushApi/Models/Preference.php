<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Model of the preferences table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Preference extends Eloquent
{
    // Option values
    CONST NOTHING = 0;
    CONST EMAIL = 1;
    CONST SMARTPHONE = 2;
    CONST ALL_DEVICES = 3;

    public $timestamps = false;
    protected $fillable = array('user_id', 'theme_id', 'option');
    protected $hidden = array('created');

    /**
     * Relationship 1-n to get an instance of the users table
     * @return [User] Instance of User model
     */
    public function user()
    {
        return $this->belongsTo('\PushApi\Models\User');
    }

    /**
     * Relationship 1-n to get an instance of the themes table
     * @return [Theme] Instance of Theme model
     */
    public function theme()
    {
        return $this->belongsTo('\PushApi\Models\Theme');
    }
}