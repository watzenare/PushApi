<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Theme;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
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
     * registration
     */
    public function setTheme()
    {
        try {
            $name = $this->slim->request->post('name');
            $range = $this->slim->request->post('range');

            if (!isset($name) && !isset($range)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if (!in_array($range, Theme::getValidValues(), true)) {
                throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::UNICAST . ", " . Theme::MULTICAST . ", " . Theme::BROADCAST);
            }

            // Checking if theme already exists
            $theme = Theme::where('name', $name)->first();

            if (isset($theme->name)) {
                $this->send($theme->toArray());
            } else {
                $theme = new Theme;
                $theme->name = $name;
                $theme->range = $range;
                $theme->save();
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }        $this->send($theme->toArray());
    }

    /**
     * Retrives theme information if it is registered
     * @param [int] $id  Theme identification
     */
    public function getTheme($id)
    {
        try {
            $theme = Theme::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($theme->toArray());
    }

    /**
     * Updates theme infomation given its identification and params to update
     * @param [int] $id  Theme identification
     */
    public function updateTheme($id)
    {
        try {
            $update = array();
            $update['name'] = $this->slim->request->put('name');
            $update['range'] = $this->slim->request->put('range');

            if (isset($update['range']) && !in_array($update['range'], Theme::getValidValues(), true)) {
                throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::UNICAST . ", " . Theme::MULTICAST . ", " . Theme::BROADCAST);
            }
            
            $update = $this->cleanParams($update);

            if (empty($update)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }
            
            $theme = Theme::find($id);
            foreach ($update as $key => $value) {
                $theme->$key = $value;
            }
            $theme->update();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($theme->toArray());
    }

    /**
     * Deletes a theme given its identification
     * @param [int] $id  Theme identification
     */
    public function deleteTheme($id)
    {
        try {
            $theme = Theme::findOrFail($id);
            $theme->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($theme->toArray());
    }

    /**
     * Retrives all themes registered
     */
    public function getAllThemes()
    {
        try {
            $theme = Theme::orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($theme->toArray());
    }

    /**
     * Retrives all themes registered given a range
     * @param  [int] $range Value refering the range of the theme
     */
    public function getByRange($range)
    {
        try {
            if (!in_array($range, Theme::getValidValues(), true)) {
                throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::UNICAST . ", " . Theme::MULTICAST . ", " . Theme::BROADCAST);
            }

            $theme = Theme::where('range', $range)->orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($theme->toArray());
    }
}