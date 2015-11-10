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
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Contains the basic and general actions for managing preference.
 */
class PreferenceController extends Controller
{
    /**
     * Sets user preference to a given theme, if the preference has
     * been done before, it only displays the information of the preference
     * else, creates the preference and displays the resulting information
     * @param int $idUser User identification
     * @param int $idTheme Theme identification
     * @throws PushApiException
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

        try {
            $this->send(Preference::createPreference($idUser, $idTheme, $option));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Retrives all preferences of a given user or it also can check
     * if user has set preferences from a theme (if he has set it, the
     * preference is displayed)
     * @param int $idUser User identification
     * @param int $idTheme Theme identification
     * @throws PushApiException
     */
    public function getPreference($idUser, $idTheme)
    {
        try {
            $this->send(Preference::getPreference($idUser, $idTheme));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Updates user preference given an id theme
     * @param int $idUser User identification
     * @param int $idTheme Theme identification
     * @throws PushApiException
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
            $this->send(Preference::updatePreference($idUser, $idTheme, $update));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Deletes a user preference given a user and a theme id
     * @param int $idUser User identification
     * @param int $idTheme Theme identification
     * @throws PushApiException
     */
    public function deletePreference($idUser, $idTheme)
    {
        try {
            $this->send(Preference::remove($idUser, $idTheme));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Retrives all user preferences registred.
     * @param  int $idUser User identification.
     * @return array
     * @throws PushApiException
     */
    public function getPreferences($idUser)
    {
        try {
            $this->send(Preference::getAllPreferences($idUser));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Updates the value of all the user preferences with the option set
     * @param int $idUser User identification
     * @throws PushApiException
     *
     * Call params:
     * @var "option" required
     */
    public function updateAllPreferences($idUser)
    {
        $update = array();

        if (!isset($this->requestParams['option'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $update['option'] = $this->requestParams['option'];

        if ($update['option'] > Preference::ALL_RANGES || $update['option'] < Preference::NOTHING) {
            throw new PushApiException(PushApiException::INVALID_RANGE);
        }

        try {
            $this->send(Preference::updateAllPreferences($idUser, $update));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }
}