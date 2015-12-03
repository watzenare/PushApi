<?php

namespace PushApi\Models;

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
            "user_id" => "",
            "theme_id" => "",
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
            $result['id'] = $preference->id;
            $result['option'] = $preference->option;
            $result['user_id'] = $preference->user_id;
            $result['theme_id'] = $preference->theme_id;
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return $result;
    }

    /**
     * Creates a new user preference.
     * @param  int $idUser
     * @param  int $idTheme
     * @param  int $option
     * @return array
     * @throws PushApiException
     */
    public static function createPreference($idUser, $idTheme, $option)
    {
        // Checking if preference is already set
        if (self::checkExistsUserPreference($idUser, $idTheme)) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }

        if (User::checkExists($idUser) && Theme::checkExists($idTheme)) {
            $preference = new Preference;
            $preference->user_id = (int) $idUser;
            $preference->theme_id = (int) $idTheme;
            $preference->option = $option;
            $preference->save();
            return $preference;
        } else {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
    }

    /**
     * Gets the target user preference.
     * @param  int $idUser
     * @param  int $idTheme
     * @return array
     * @throws PushApiException
     */
    public static function getPreference($idUser, $idTheme)
    {
        $preference = self::checkExistsUserPreference($idUser, $idTheme);

        if (!$preference) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return self::generateFromModel($preference);
    }

    /**
     * Updates the target user preference if it is set.
     * @param  int $idUser
     * @param  int $idTheme
     * @param  array $update
     * @return boolean
     * @throws PushApiException
     */
    public static function updatePreference($idUser, $idTheme, $update)
    {
        $preference = self::checkExistsUserPreference($idUser, $idTheme);

        if (!$preference) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        try {
            $preference->update($update);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return true;
    }

    /**
     * Remove the target user preference.
     * @param  int $idUser
     * @param  int $idTheme
     * @return boolean
     * @throws PushApiException
     */
    public static function remove($idUser, $idTheme)
    {
        $preference = self::checkExistsUserPreference($idUser, $idTheme);

        if (!$preference) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        try {
            $preference->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return true;
    }

    /**
     * Obtains all user preferences set by the user.
     * @param  int $idUser
     * @return array
     * @throws PushApiException
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
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        return $result;
    }

    /**
     * Updates all target user preferences with the same option value.
     * @param  int $idUser
     * @param  int $update
     * @return boolean
     * @throws PushApiException
     */
    public static function updateAllPreferences($idUser, $update)
    {
        $totalThemes = 0;

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
                    throw new PushApiException(PushApiException::NOT_FOUND);
                }
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        // Checking if all preferences have been set correctly
        if ($totalThemes == $count) {
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