<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Theme;
use \PushApi\Controllers\Controller;
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

        if (!isset($theme->name)) {
            $theme = new Theme;
            $theme->name = $name;
            $theme->range = $range;
            $theme->save();
        }

        $this->send($theme->toArray());
    }

    /**
     * Retrives all themes or the theme information if it is registered
     * @param [int] $id  Theme identification
     */
    public function getTheme($id = false)
    {
        try {
            if (!$id) {
                $theme = Theme::orderBy('id', 'asc')->get();
            } else {
                $theme = Theme::findOrFail($id);
            }
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
            
        try {
            $theme = Theme::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        foreach ($update as $key => $value) {
            $theme->$key = $value;
        }

        $theme->update();
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
     * Retrives all themes registered given a range
     * @param  [string] $range Value refering the range of the theme
     */
    public function getByRange($range)
    {
        if (!in_array($range, Theme::getValidValues(), true)) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range themes: " . Theme::UNICAST . ", " . Theme::MULTICAST . ", " . Theme::BROADCAST);
        }

        try {
            $theme = Theme::where('range', $range)->orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($theme->toArray());
    }
}