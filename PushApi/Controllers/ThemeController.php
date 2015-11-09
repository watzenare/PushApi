<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Theme;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 *
 * Contains the basic and general actions that can be done with a theme.
 */
class ThemeController extends Controller
{
    /**
     * Creates a new theme into the registration with given params and
     * displays the information of the created theme. If the theme tries
     * to registrate twice (checked by name), the information of the
     * saved theme is displayed without adding him again into the
     * registration.
     *
     * Call params:
     * @var "name" required
     * @var "range" required
     */
    public function setTheme()
    {
        if (!isset($this->requestParams['name']) && !isset($this->requestParams['range'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $name = $this->requestParams['name'];
        $range = $this->requestParams['range'];

        if (!in_array($range, Theme::getValidValues(), true)) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::UNICAST . ", " . Theme::MULTICAST . ", " . Theme::BROADCAST);
        }

        $this->send(Theme::createTheme($name, $range));
    }

    /**
     * Retrives the theme information if it is registered
     * @param int $id  Theme identification
     */
    public function getTheme($id)
    {
        try {
            $this->send(Theme::get($id));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Updates theme infomation given its identification and params to update.
     * @param [int] $id  Theme identification.
     *
     * Call params:
     * @var "name" required
     * @var "range" required
     */
    public function updateTheme($id)
    {
        $update = array();

        if (isset($this->requestParams['range']) && !in_array($this->requestParams['range'], Theme::getValidValues(), true)) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::UNICAST . ", " . Theme::MULTICAST . ", " . Theme::BROADCAST);
        }

        if (isset($this->requestParams['name'])) {
            $update['name'] = $this->requestParams['name'];
        }

        if (isset($this->requestParams['range'])) {
            $update['range'] = $this->requestParams['range'];
        }

        $update = $this->cleanParams($update);

        if (empty($update)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        try {
            $this->send(Theme::updateTheme($id, $update));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Deletes a theme given its identification.
     * @param int $id  Theme identification.
     */
    public function deleteTheme($id)
    {
        try {
            $this->send(Theme::remove($id));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Retrives all themes registred.
     * @var "limit" optional
     * @var "page" optional
     * @throws PushApiException
     */
    public function getThemes()
    {
        $limit = (isset($this->requestParams['limit']) ? $this->requestParams['limit'] : 10);
        $page = (isset($this->requestParams['page']) ? $this->requestParams['page'] : 1);

        if (isset($limit) && $limit < 0) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid limit value");
        }

        if (isset($page) && $page < 1) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid page value");
        }

        try {
            $this->send(Theme::getThemes($limit, $page));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }

    /**
     * Retrives the theme information given its name.
     * @var "name" required
     */
    public function getThemeByName()
    {
        if (!isset($this->requestParams['name'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        try {
            $this->send(Theme::getInfoByName($this->requestParams['name']));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }

    }

    /**
     * Retrives all themes registered given a range.
     * @param  string $range Value refering the range of the theme.
     */
    public function getThemesByRange($range)
    {
        $limit = (isset($this->requestParams['limit']) ? $this->requestParams['limit'] : 10);
        $page = (isset($this->requestParams['page']) ? $this->requestParams['page'] : 1);

        if (isset($limit) && $limit < 0) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid limit value");
        }

        if (isset($page) && $page < 1) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid page value");
        }

        if (!in_array($range, Theme::getValidValues(), true)) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::UNICAST . ", " . Theme::MULTICAST . ", " . Theme::BROADCAST);
        }

        try {
            $this->send(Theme::getInfoByRange($range, $limit, $page));
        } catch (PushApiException $e) {
            throw new PushApiException($e->getCode());
        }
    }
}