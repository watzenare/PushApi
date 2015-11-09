<?php

namespace PushApi\Models;

use \PushApi\System\IModel;
use \PushApi\PushApiException;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Model of the themes table, manages all the relationships and dependencies
 * that can be done on these table
 */
class Theme extends Eloquent implements IModel
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
     * Returns the basic displayable Theme model.
     * @return array
     */
    public static function getEmptyDataModel()
    {
        return [
            "id" => 0,
            "name" => "",
            "range" => "",
        ];
    }

    /**
     * Relationship n-1 to get an instance of the preferences table
     * @return Preferences Instance of preferences model
     */
    public function preferences()
    {
        return $this->hasMany('\PushApi\Models\Preference');
    }

    /**
     * Relationship n-1 to get an instance of the logs table
     * @return Log Instance of Log model
     */
    public function logs()
    {
        return $this->hasMany('\PushApi\Models\Log');
    }

    /**
     * Relationship 1-1 to get an instance of the subjects table
     * @return Subject Instance of Subject model
     */
    public function subject()
    {
        return $this->hasOne('\PushApi\Models\Subject');
    }

    /**
     * Returns the valid values that accepts Theme model
     * @return array Array with the accepted constants
     */
    public static function getValidValues()
    {
        return self::$validValues;
    }

    /**
     * Checks if user exists and returns it if true.
     * @param  int $id User id
     * @return User/false
     */
    public static function checkExists($id)
    {
        try {
            $user = Theme::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return false;
        }

        return $user;
    }

    public static function generateFromModel($theme)
    {
        $result = self::getEmptyDataModel();
        try {
            $result['id'] = $theme->id;
            $result['name'] = $theme->name;
            $result['range'] = $theme->range;

        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return $result;
    }

    /**
     * Retrives the theme information given its name.
     * @param  string $name
     * @return int/boolean  If user is found returns id, if not, returns false
     */
    public static function getInfoByName($name)
    {
        $theme = Theme::where('name', $name)->first();

        if ($theme == null) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $theme = self::generateFromModel($theme);

        return $theme;
    }

    /**
     * Retrives all themes registered given a range.
     * @param  string $range
     * @return array
     */
    public static function getInfoByRange($range, $limit = 10, $page = 1)
    {
        $result = [
            'themes' => []
        ];
        $skip = 0;
        // Updating the page offset
        if ($page != 1) {
            $skip = $page * $limit;
        }

        $result['limit'] = (int) $limit;
        $result['page'] = (int) $page;

        try {
            $themes = Theme::where('range', $range)->orderBy('id', 'asc')->take($limit)->offset($skip)->get();
            foreach ($themes as $theme) {
                $result['themes'][] = self::generateFromModel($theme);
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $result['totalInPage'] = sizeof($themes);

        return $result;
    }

    /**
     * Retrives the Theme id given its name if exists.
     * @param  string $name Theme name.
     * @return int/boolean
     */
    public static function getIdByName($name)
    {
        $theme = Theme::where('name', $name)->first();

        if ($theme) {
            return $theme->id;
        }

        return false;
    }

    /**
     * Obtains all information about target theme given its id.
     * @param  int $id Theme identification
     * @return array
     */
    public static function get($id)
    {
        try {
            $theme = Theme::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $result = self::generateFromModel($theme);

        return $result;
    }

    /**
     * Creates a new theme if it does not exist yet
     * @param  string $name  Name of the new theme.
     * @param  string $range Range of the notifications.
     * @return array
     */
    public static function createTheme($name, $range)
    {
        $themeExists = self::getIdByName($name);

        if ($themeExists) {
            return self::get($id);
        }

        if (!isset($theme->name)) {
            $theme = new Theme;
            $theme->name = $name;
            $theme->range = $range;
            $theme->save();
        }

        return $theme;
    }

    /**
     * [update description]
     * @param  string $id
     * @param  array $update
     * @return array
     * @throws PushApiException
     */
    public static function updateTheme($id, $update)
    {
        if (!$theme = self::checkExists($id)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        foreach ($update as $key => $value) {
            $theme->$key = $value;
        }

        try {
            $theme->update();
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }

        return self::generateFromModel($theme);
    }

    /**
     * Deletes the target theme given its id.
     * @param  int $id
     * @return boolean
     * @throws PushApiException
     */
    public static function remove($id)
    {
        // It must be deleted all preferences first in order to destroy the DB relationship
        if (!Preference::deleteAllThemePreferences($id)) {
            return false;
        }

        try {
            $user = Theme::findOrFail($id);
            $user->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return true;
    }

    /**
     * Obtains all themes registered in Push API with all its devices registered. It can be searched
     * giving limit and page values.
     * @param  int $limit Max results per page
     * @param  int $page  Page to display
     * @return array
     */
    public static function getThemes($limit = 10, $page = 1)
    {
        $result = [
            'themes' => []
        ];
        $skip = 0;
        // Updating the page offset
        if ($page != 1) {
            $skip = $page * $limit;
        }

        $result['limit'] = (int) $limit;
        $result['page'] = (int) $page;

        try {
            $themes = Theme::orderBy('id', 'asc')->take($limit)->offset($skip)->get();
            foreach ($themes as $theme) {
                $result['themes'][] = self::generateFromModel($theme);
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $result['totalInPage'] = sizeof($themes);

        return $result;
    }
}