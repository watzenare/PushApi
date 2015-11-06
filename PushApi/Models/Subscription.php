<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Model of the subscriptions table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Subscription extends Eloquent
{
    public $timestamps = false;
    protected $fillable = array('user_id', 'channel_id');
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