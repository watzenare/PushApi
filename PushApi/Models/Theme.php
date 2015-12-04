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
 * Documentation @link https://push-api.readme.io/
 *
 * Model of the themes table, manages all the relationships and dependencies
 * that can be done on these table.
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
     * Relationship n-1 to get an instance of the preferences table.
     * @return Preferences Instance of preferences model.
     */
    public function preferences()
    {
        return $this->hasMany('\PushApi\Models\Preference');
    }

    /**
     * Relationship n-1 to get an instance of the logs table.
     * @return Log Instance of Log model.
     */
    public function logs()
    {
        return $this->hasMany('\PushApi\Models\Log');
    }

    /**
     * Relationship 1-1 to get an instance of the subjects table.
     * @return Subject Instance of Subject model.
     */
    public function subject()
    {
        return $this->hasOne('\PushApi\Models\Subject');
    }

    /**
     * Returns the valid values that accepts Theme model.
     * @return array Array with the accepted constants.
     */
    public static function getValidValues()
    {
        return self::$validValues;
    }

    /**
     * Checks if theme exists and returns it if true.
     * @param  int $id
     * @return Theme/false
     * @throws PushApiException
     */
    public static function checkExists($id)
    {
        try {
            $theme = Theme::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return false;
        }

        return $theme;
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
     * Retrieves the theme information given its name.
     * @param  string $name
     * @return int/boolean
     * @throws PushApiException
     */
    public static function getInfoByName($name)
    {
        $theme = Theme::where('name', $name)->first();

        if ($theme == null) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return self::generateFromModel($theme);
    }

    /**
     * Retrieves the Theme id given its name if exists.
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
     * @param  int $id Theme identification.
     * @return array
     * @throws PushApiException
     */
    public static function getTheme($id)
    {
        try {
            $theme = Theme::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return self::generateFromModel($theme);
    }

    /**
     * Creates a new theme if it does not exist yet.
     * @param  string $name  Name of the new theme.
     * @param  string $range Range of the notifications.
     * @return array
     * @throws PushApiException
     */
    public static function createTheme($name, $range)
    {
        $themeExists = self::getIdByName($name);

        if ($themeExists) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }

        $theme = new Theme;
        $theme->name = $name;
        $theme->range = $range;
        $theme->save();

        return $theme;
    }

    /**
     * Updates the target theme with the available updating values.
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

        return true;
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

        // It must be deleted all subjects first in order to destroy the DB relationship
        if (!Subject::deleteAllThemeSubjects($id)) {
            return false;
        }

        try {
            $theme = Theme::findOrFail($id);
            $theme->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return true;
    }

    /**
     * Obtains all themes registered. It can be searched giving limit and page values.
     * Also can retrieve all themes registered given a range value.
     * @param  int $limit Max results per page.
     * @param  int $page  Page to display.
     * @param  string $range Theme range value.
     * @return array
     * @throws PushApiException
     */
    public static function getThemes($limit = 10, $page = 1, $range = false)
    {
        $result = [
            'themes' => []
        ];
        $skip = 0;
        // Updating the page offset
        if ($page != 1) {
            $skip = ($page - 1) * $limit;
        }

        $result['limit'] = (int) $limit;
        $result['page'] = (int) $page;

        try {
            if ($range) {
                $themes = Theme::where('range', $range)->orderBy('id', 'asc')->take($limit)->offset($skip)->get();
            } else {
                $themes = Theme::orderBy('id', 'asc')->take($limit)->offset($skip)->get();
            }

            $result['totalInPage'] = sizeof($themes);

            foreach ($themes as $theme) {
                $result['themes'][] = self::generateFromModel($theme);
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return $result;
    }
}