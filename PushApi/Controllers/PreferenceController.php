<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Controllers\Controller;
use \PushApi\Models\User;
use \PushApi\Models\Theme;
use \PushApi\Models\Preference;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions for managing preference.
 */
class PreferenceController extends Controller
{
    /**
     * Sets user preference to a given theme, if the preference has
     * been done before, it only displays the information of the preference
     * else, creates the preference and displays the resulting information
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     *
     * Call params:
     * @var "option" required
     */
    public function setPreference($idUser, $idTheme)
    {

        if (!isset($this->requestParams['option'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $option = (int) $this->requestParams['option'];

        if ($option > Preference::ALL_RANGES || $option < Preference::NOTHING) {
            throw new PushApiException(PushApiException::INVALID_RANGE);
        }

        // Checking if preference already exists
        try {
            $preference = User::findOrFail($idUser)->preferences()->where('theme_id', $idTheme)->first();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        if (!isset($preference) || empty($preference)) {
            if (!empty(Theme::find($idTheme))) {
                $preference = new Preference;
                $preference->user_id = $idUser;
                $preference->theme_id = $idTheme;
                $preference->option = $option;
                $preference->save();
            }
        }

        if (!empty($preference)) {
            $this->send($preference->toArray());
        } else {
            $this->send(array());
        }
    }

    /**
     * Retrives all preferences of a given user or it also can check
     * if user has set preferences from a theme (if he has set it, the
     * preference is displayed)
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     */
    public function getPreference($idUser, $idTheme = false)
    {
        try {
            if ($idTheme) {
                $preferences = User::findOrFail($idUser)->preferences()->where('theme_id', $idTheme)->orderBy('id', 'asc')->first();
            } else {
                $preferences = User::findOrFail($idUser)->preferences()->orderBy('id', 'asc')->get();
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        if (!empty($preferences)) {
            $this->send($preferences->toArray());
        } else {
            $this->send(array());
        }
    }

    /**
     * Updates user preference given an id theme
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     *
     * Call params:
     * @var "option" required
     */
    public function updatePreference($idUser, $idTheme)
    {
        $update = array();

        if (!isset($this->requestParams['option'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $update['option'] = (int) $this->requestParams['option'];

        if ($update['option'] > Preference::ALL_RANGES || $update['option'] < Preference::NOTHING) {
            throw new PushApiException(PushApiException::INVALID_RANGE);
        }

        try {
            $user = Preference::where('theme_id', $idTheme)->where('user_id', $idUser)->update($update);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($this->boolinize($user));
    }

    /**
     * Deletes a user preference given a user and a theme id
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     */
    public function deletePreference($idUser, $idTheme)
    {
        try {
            $preference = User::findOrFail($idUser)->preferences()->where('theme_id', $idTheme)->first();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        if (!empty($preference)) {
            $preference->delete();
            $this->send($preference->toArray());
        } else {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
    }

    /**
     * Updates the value of all the user preferences with the option set
     * @param [int] $idUser User identification
     *
     * Call params:
     * @var "option" required
     */
    public function updateAllPreferences($idUser)
    {
        $update = array();
        $totalThemes = 0;

        if (!isset($this->requestParams['option'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $update['option'] = $this->requestParams['option'];

        if ($update['option'] > Preference::ALL_RANGES || $update['option'] < Preference::NOTHING) {
            throw new PushApiException(PushApiException::INVALID_RANGE);
        }

        try {
            // Obtaining all themes
            $themes = Theme::get()->toArray();
            $totalThemes = sizeof($themes);
            $count = 0;
            foreach ($themes as $theme) {
                try {
                    // Checking if exists the preference in order to create or update the value
                    $preference = User::findOrFail($idUser)->preferences()->where('theme_id', $theme['id'])->first();
                    if ($preference && $preference->option != $update['option']) {
                        // Updating user preference option
                        $preference = Preference::where('theme_id', $theme['id'])->where('user_id', $idUser)->update($update);
                    } else if (!$preference) {
                        // Creating user preference option
                        $preference = new Preference;
                        $preference->user_id = $idUser;
                        $preference->theme_id = $theme['id'];
                        $preference->option = $update['option'];
                        $preference->save();
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
            $this->send(true);
        }

        $this->send(false);
    }
}