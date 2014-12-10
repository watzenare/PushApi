<?php

namespace PushApi\Models;

use \Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * A subject is the description of the theme->name and it is used for example as
 * a mail subject. An example is that you have a theme name like:
 *     user_comment
 * and you want to send it via mail with a better description like:
 *     A user has commented on your profile wall
 * This model of the subjects table, manages all the relationships and dependencies
 * that can be done on these table in order to improve theme names descriptions.
 */
class Subject extends Eloquent
{
    public $timestamps = false;
	public $fillable = array('name', 'description');
    protected $hidden = array('created');


    /**
     * Relationship 1-1 to get an instance of the themes table
     * @return [Themes] Instance of themes model
     */
    public function theme()
    {
        return $this->belongsTo('\PushApi\Models\Theme');
    }
}