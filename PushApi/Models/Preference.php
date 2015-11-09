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
 * Model of the preferences table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Preference extends Eloquent
{
    // Option values
    CONST NOTHING = 0;
    CONST EMAIL = 1;
    CONST SMARTPHONE = 2;
    CONST ALL_RANGES = 3;

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

    public static function deleteAllThemePreferences($id)
    {
        $preferences = Preference::where('theme_id', $id)->get();

        foreach ($preferences as $preference) {
            $preference->delete();
        }

        return true;
    }

    public static function deleteAllUserPreferences($id)
    {
        $preferences = Preference::where('user_id', $id)->get();

        foreach ($preferences as $preference) {
            $preference->delete();
        }

        return true;
    }
}