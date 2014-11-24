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
    CONST EMAIL = 1;
    CONST ANDROID = 2;
    CONST IOS = 3;

    public $timestamps = false;
    protected $fillable = array('user_id', 'channel_id', 'preferences');
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
     * Relationship 1-n to get an instance of the type table
     * @return [Type] Instance of Type model
     */
    public function type()
    {
        return $this->belongsTo('\PushApi\Models\Type');
    }
}