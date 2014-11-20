<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Model of the subscribed table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Subscription extends Eloquent
{
    // Preferences values
    CONST EMAIL = 1;
    CONST SMARTPHONE = 2;

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
     * Relationship 1-n to get an instance of the channels table
     * @return [Channel] Instance of Channel model
     */
    public function channel()
    {
        return $this->belongsTo('\PushApi\Models\Channel');
    }

}