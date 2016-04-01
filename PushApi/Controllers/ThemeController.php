<?php

namespace PushApi\Controllers;

use \PushApi\PushApi;
use \PushApi\PushApiException;
use \PushApi\Models\Theme;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Contains the basic and general actions that can be done with a theme.
 */
class ThemeController extends Controller
{
    /**
     * Creates a new theme into the registration with given params and
     * displays the information of the created theme. If the theme tries
     * to register twice (checked by name), the information of the
     * saved theme is displayed without adding him again into the
     * registration.
     *
     * Call params:
     * @var "name" required
     * @var "range" required
     */
    public function setTheme()
    {
        if (!isset($this->requestParams['name']) || !isset($this->requestParams['range'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $name = $this->requestParams['name'];
        $range = $this->requestParams['range'];

        if (empty($name) || empty($range)) {
            throw new PushApiException(PushApiException::EMPTY_PARAMS);
        }

        if (!in_array($range, Theme::getValidRanges(), true)) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::RANGE_UNICAST . ", " . Theme::RANGE_MULTICAST . ", " . Theme::RANGE_BROADCAST);
        }

        if (!$theme = Theme::createTheme($name, $range)) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }

        $this->send($theme);
    }

    /**
     * Retrieves the theme information if it is registered.
     * @param int $id  Theme identification
     */
    public function getTheme($id)
    {
        if (!$theme = Theme::getTheme($id)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($theme);
    }

    /**
     * Updates theme information given its identification and params to update.
     * @param [int] $id  Theme identification.
     *
     * Call params:
     * @var "name" required
     * @var "range" required
     */
    public function updateTheme($id)
    {
        $update = array();

        if (isset($this->requestParams['range']) && !in_array($this->requestParams['range'], Theme::getValidRanges(), true)) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::RANGE_UNICAST . ", " . Theme::RANGE_MULTICAST . ", " . Theme::RANGE_BROADCAST);
        } else {
            $update['range'] = $this->requestParams['range'];
        }

        if (isset($this->requestParams['name'])) {
            $update['name'] = $this->requestParams['name'];
        }

        $update = $this->cleanParams($update);

        if (empty($update)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        if (!$theme = Theme::updateTheme($id, $update)) {
            throw new PushApiException(PushApiException::ACTION_FAILED);
        }

        $this->send($theme);
    }

    /**
     * Deletes a theme given its identification.
     * @param int $id  Theme identification.
     */
    public function deleteTheme($id)
    {
        if (!$theme = Theme::remove($id)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($theme);
    }

    /**
     * Retrieves all themes registered.
     * @throws PushApiException
     *
     * Call params:
     * @var "limit" optional
     * @var "page" optional
     */
    public function getThemes()
    {
        $limit = (isset($this->requestParams['limit']) ? $this->requestParams['limit'] : 10);
        $page = (isset($this->requestParams['page']) ? $this->requestParams['page'] : 1);

        if ($limit <= 0) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid limit value");
        }

        if ($page < 1) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid page value");
        }

        if (!$theme = Theme::getThemes($limit, $page)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($theme);
    }

    /**
     * Retrieves the theme information given its name.
     * @throws PushApiException
     *
     * Call params:
     * @var "name" required
     */
    public function getThemeByName()
    {
        if (!isset($this->requestParams['name'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        if (!$theme = Theme::getInfoByName($this->requestParams['name'])) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($theme);
    }

    /**
     * Retrieves all themes registered given a range.
     * @param  string $range Value referring the range of the theme.
     * @throws PushApiException
     *
     * Call params:
     * @var "limit" optional
     * @var "page" optional
     */
    public function getThemesByRange($range)
    {
        $limit = (isset($this->requestParams['limit']) ? $this->requestParams['limit'] : 10);
        $page = (isset($this->requestParams['page']) ? $this->requestParams['page'] : 1);

        if ($limit <= 0) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid limit value");
        }

        if ($page < 1) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid page value");
        }

        if (!in_array($range, Theme::getValidRanges(), true)) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::RANGE_UNICAST . ", " . Theme::RANGE_MULTICAST . ", " . Theme::RANGE_BROADCAST);
        }

        if (!$themes = Theme::getThemes($limit, $page, $range)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($themes);
    }
}