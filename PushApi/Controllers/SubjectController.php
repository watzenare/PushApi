<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Controllers\Controller;
use \PushApi\Models\Theme;
use \PushApi\Models\Subject;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions for managing subjects
 */
class SubjectController extends Controller
{
    /**
     * Sets a description for a given subject or returns the information if it has
     * been edited before.
     */
    public function setSubject()
    {
            $themeName = $this->slim->request->post('theme_name');
            $description = $this->slim->request->post('description');

            if (!isset($themeName) || !isset($description)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            // Checking if theme exists
            $theme = Theme::where('name', $themeName)->first();

            if (!isset($theme) && empty($theme)) {
                throw new PushApiException(PushApiException::NOT_FOUND, 'theme_name not found');
            }

            $subject = Subject::where('theme_id', $theme->id)->first();
            if (!isset($subject) && empty($subject)) {
                $subject = new Subject;
                $subject->theme_id = $theme->id;
                $subject->description = $description;
                $subject->save();
            }
            $this->send($subject->toArray());
    }

    /**
     * Retrives all edited subjects or the subject information given its id
     * @param [int] $idSubject Subject identification
     */
    public function getSubject($idSubject = false)
    {
        try {
            if (!$idSubject) {
                $subjects = Subject::orderBy('id', 'asc')->get();
            } else {
                $subject = Subject::findOrFail($idSubject);
            }
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($subject->toArray());
    }

    /**
     * Updates subject description given a subject id
     * @param [int] $idSubject User identification
     */
    public function updateSubject($idSubject)
    {
        $update = array();
        $update['description'] = $this->slim->request->put('description');

        $update = $this->cleanParams($update);

        if (empty($update)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        try {
            $subject = Subject::findOrFail($idSubject);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $subject->description = $update['description'];
        $subject->update();
        $this->send($subject->toArray());
    }

    /**
     * Deletes a subject given its id
     * @param [int] $idSubject Subject identification
     */
    public function deleteSubject($idSubject)
    {
        try {
            $subject = Subject::findOrFail($idSubject);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $subject->delete();
        $this->send($subject->toArray());
    }
}