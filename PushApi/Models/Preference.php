<?php

namespace PushApi\Models;

use \Slim\Log;
use \PushApi\PushApi;
use \PushApi\System\IModel;
use \PushApi\PushApiException;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Model of the preferences table, manages all the relationships and dependencies
 * that can be done on these table.
 */
class Preference extends Eloquent implements IModel
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
     * Returns the basic displayable Preference model.
     * @return array
     */
    public static function getEmptyDataModel()
    {
        return [
            "id" => 0,
            "option" => "",
            "user_id" => 0,
            "theme_id" => 0,
        ];
    }

    /**
     * Relationship 1-n to get an instance of the users table.
     * @return User Instance of User model.
     */
    public function user()
    {
        return $this->belongsTo('\PushApi\Models\User');
    }

    /**
     * Relationship 1-n to get an instance of the themes table.
     * @return Theme Instance of Theme model.
     */
    public function theme()
    {
        return $this->belongsTo('\PushApi\Models\Theme');
    }

    /**
     * Checks if user exists and returns it if true.
     * @param  int $id
     * @return Theme/false
     */
    public static function checkExists($id)
    {
        try {
            $preference = Preference::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $preference;
    }

    /**
     * Checks if it is set the preference between user and theme.
     * @param  int $idUser
     * @param  int $idTheme
     * @return Preference/false
     */
    public static function checkExistsUserPreference($idUser, $idTheme)
    {
        try {
            $preference = User::findOrFail($idUser)->preferences()->where('theme_id', $idTheme)->first();
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        if ($preference) {
            return $preference;
        }

        return false;
    }

    public static function generateFromModel($preference)
    {
        $result = self::getEmptyDataModel();
        try {
            $result['id'] = (int) $preference->id;
            $result['option'] = $preference->option;
            $result['user_id'] = (int) $preference->user_id;
            $result['theme_id'] = (int) $preference->theme_id;
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }

    /**
     * Creates a new user preference.
     * @param  int $idUser
     * @param  int $idTheme
     * @param  int $option
     * @return array
     */
    public static function createPreference($idUser, $idTheme, $option)
    {
        // Checking if preference is already set
        if (self::checkExistsUserPreference($idUser, $idTheme)) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::DUPLICATED_VALUE, Log::DEBUG);
            return false;
        }

        if (User::checkExists($idUser) && Theme::checkExists($idTheme)) {
            $preference = new Preference;
            $preference->user_id = (int) $idUser;
            $preference->theme_id = (int) $idTheme;
            $preference->option = $option;
            $preference->save();
            return $preference;
        } else {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }
    }

    /**
     * Gets the target user preference.
     * @param  int $idUser
     * @param  int $idTheme
     * @return array
     */
    public static function getPreference($idUser, $idTheme)
    {
        $preference = self::checkExistsUserPreference($idUser, $idTheme);

        if (!$preference) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return self::generateFromModel($preference);
    }

    /**
     * Updates the target user preference if it is set.
     * @param  int $idUser
     * @param  int $idTheme
     * @param  array $update
     * @return boolean
     */
    public static function updatePreference($idUser, $idTheme, $update)
    {
        $preference = self::checkExistsUserPreference($idUser, $idTheme);

        if (!$preference) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        try {
            $preference->update($update);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return true;
    }

    /**
     * Remove the target user preference.
     * @param  int $idUser
     * @param  int $idTheme
     * @return boolean
     */
    public static function remove($idUser, $idTheme)
    {
        $preference = self::checkExistsUserPreference($idUser, $idTheme);

        if (!$preference) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        try {
            $preference->delete();
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return true;
    }

    /**
     * Obtains all user preferences set by the user.
     * @param  int $idUser
     * @return array
     */
    public static function getAllPreferences($idUser)
    {
        $result = [];

        try {
            $preferences = User::findOrFail($idUser)->preferences()->orderBy('id', 'asc')->get();
            foreach ($preferences as $preference) {
                $result[] = self::generateFromModel($preference);
            }
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }

    /**
     * Updates all target user preferences with the same option value.
     * @param  int $idUser
     * @param  int $update
     * @return boolean
     */
    public static function updateAllPreferences($idUser, $update)
    {
        try {
            // Obtaining all themes
            $themes = Theme::get()->toArray();
            $totalThemes = sizeof($themes);
            $count = 0;
            foreach ($themes as $theme) {
                try {
                    // Checking if exists the preference in order to create or update the value
                    $preference = self::checkExistsUserPreference($idUser, $theme['id']);
                    if ($preference && $preference->option != $update['option']) {
                        // Updating user preference option
                        $preference = self::updatePreference($idUser, $theme['id'], $update);
                    } else if (!$preference) {
                        // Creating user preference option
                        $preference = self::createPreference($idUser, $theme['id'], $update['option']);
                    }

                    if ($preference) {
                        $count++;
                    }
                } catch (ModelNotFoundException $e) {
                    PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
                    return false;
                }
            }
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        // Checking if all preferences have been set correctly
        if (isset($totalThemes) && $totalThemes == $count) {
            return true;
        }

        return false;
    }

    /**
     * Deletes all preferences related with the target theme.
     * @param  int $idTheme
     * @return boolean
     */
    public static function deleteAllThemePreferences($idTheme)
    {
        $preferences = Preference::where('theme_id', $idTheme)->get();

        foreach ($preferences as $preference) {
            $preference->delete();
        }

        return true;
    }

    /**
     * Deletes all preferences related with the target user.
     * @param  int $idUser
     * @return boolean
     */
    public static function deleteAllUserPreferences($idUser)
    {
        $preferences = Preference::where('user_id', $idUser)->get();

        foreach ($preferences as $preference) {
            $preference->delete();
        }

        return true;
    }
}