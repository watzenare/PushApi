<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Model of the users table, manages all the relationships and dependencies
 * that can be done on these table
 */
class User extends Eloquent
{
    public $timestamps = false;
    protected $fillable = array('email', 'android_id', 'ios_id', 'unicast', 'broadcast');
    protected $guarded = array('id','created');
    protected $hidden = array('created');

    /**
     * Relationship n-1 to get an instance of the subscribed table
     * @return [Subscribed] Instance of User model
     */
    public function subscriptions()
    {
        return $this->hasMany('\PushApi\Models\Subscription');
    }

    /**
     * Relationship n-1 to get an instance of the preferences table
     * @return [Preferences] Instance of User model
     */
    public function preferences()
    {
        return $this->hasMany('\PushApi\Models\Preferences');
    }
}