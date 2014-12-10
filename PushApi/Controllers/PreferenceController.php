<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Controllers\Controller;
use \PushApi\Models\User;
use \PushApi\Models\Theme;
use \PushApi\Models\Preference;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions for managing preference.
 */
class PreferenceController extends Controller
{
    /**
     * Retrives all preferences of a given user
     * @param [int] $idUser User identification
     */
    public function getPreferences($idUser)
    {
        try {
            $preferences = User::findOrFail($idUser)->preferences()->orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($preferences->toArray());
    }

    /**
     * Sets user preference to a given theme, if the preference has
     * been done before, it only displays the information of the preference
     * else, creates the preference and displays the resulting information
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     */
    public function setPreference($idUser, $idTheme)
    {
        try {
            $option = (int) $this->slim->request->post('option');

            if (!isset($option)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if ($update['option'] > Preference::ALL_RANGES) {
                throw new PushApiException(PushApiException::INVALID_OPTION);
            }

            $preference = User::find($idUser)->preferences()->where('theme_id', $idTheme)->first();
            if (!isset($preference) || empty($preference)) {
                if (!empty(User::find($idUser)->toArray()) && !empty(Theme::find($idTheme)->toArray())) {
                    $preference = new Preference;
                    $preference->user_id = $idUser;
                    $preference->theme_id = $idTheme;
                    $preference->option = $option;
                    $preference->save();
                }
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($preference->toArray());
    }

    /**
     * Retrives preference of a given theme
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     */
    public function getPreference($idUser, $idTheme)
    {
        try {
            $preferences = User::findOrFail($idUser)->preferences()->where('theme_id', $idTheme)->orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($preferences->toArray());
    }

    /**
     * Updates user preference given an id theme
     * @param [int] $idUser User identification
     * @param [int] $idTheme Theme identification
     */
    public function updatePreference($idUser, $idTheme)
    {
        try {
            $update = array();
            $update['option'] = (int) $this->slim->request->post('option');

            if (!isset($update['option'])) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if ($update['option'] > Preference::ALL_RANGES) {
                throw new PushApiException(PushApiException::INVALID_OPTION);
            }

            $user = Preference::where('theme_id', $idTheme)->update($update);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
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
            $preference->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        } catch (\Exception $e) {
            throw new PushApiException(PushApiException::INVALID_ACTION);
        }
        $this->send($preference->toArray());
    }
}