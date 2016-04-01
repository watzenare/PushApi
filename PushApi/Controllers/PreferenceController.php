<?php

namespace PushApi\Controllers;

use \PushApi\PushApi;
use \PushApi\PushApiException;
use \PushApi\Controllers\Controller;
use \PushApi\Models\Preference;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
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

        if (!$preference = Preference::createPreference($idUser, $idTheme, $option)) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }

        $this->send($preference);
    }

    /**
     * Retrieves all preferences of a given user or it also can check
     * if user has set preferences from a theme (if he has set it, the
     * preference is displayed).
     * @param int $idUser User identification
     * @param int $idTheme Theme identification
     * @throws PushApiException
     */
    public function getPreference($idUser, $idTheme)
    {
        if (!$preference = Preference::getPreference($idUser, $idTheme)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($preference);
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

        if (!$preference = Preference::updatePreference($idUser, $idTheme, $update)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($preference);
    }

    /**
     * Deletes a user preference given a user and a theme id
     * @param int $idUser User identification
     * @param int $idTheme Theme identification
     * @throws PushApiException
     */
    public function deletePreference($idUser, $idTheme)
    {
        if (!$preference = Preference::remove($idUser, $idTheme)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($preference);
    }

    /**
     * Retrieves all user preferences registered.
     * @param  int $idUser User identification.
     * @return array
     * @throws PushApiException
     */
    public function getPreferences($idUser)
    {
        if (!$preferences = Preference::getAllPreferences($idUser)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($preferences);
    }

    /**
     * Updates the value of all the user preferences with the option set.
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

        if (!$preferences = Preference::updateAllPreferences($idUser, $update)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($preferences);
    }
}