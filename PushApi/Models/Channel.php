<?php

namespace PushApi\Models;

use \PushApi\System\IModel;
use \PushApi\PushApiException;
use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Model of the channels table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Channel extends Eloquent
{
    public $timestamps = false;
	public $fillable = array('name');
    protected $hidden = array('created');

    /**
     * Relationship n-1 to get an instance of the subscribed table
     * @return [Subscribed] Instance of Subscription model
     */
    public function subscriptions()
    {
        return $this->hasMany('\PushApi\Models\Subscription');
    }
}