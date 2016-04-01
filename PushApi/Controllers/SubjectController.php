<?php

namespace PushApi\Controllers;

use \PushApi\PushApi;
use \PushApi\PushApiException;
use \PushApi\Models\Theme;
use \PushApi\Models\Subject;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Contains the basic and general actions for managing subjects
 */
class SubjectController extends Controller
{
    /**
     * Sets a description for a given subject or returns the information if it has
     * been edited before.
     * @throws PushApiException
     *
     * Call params:
     * @var "theme_name" required
     * @var "description" required
     */
    public function setSubject()
    {
        if (!isset($this->requestParams['theme_name']) || !isset($this->requestParams['description'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $themeName = $this->requestParams['theme_name'];
        $description = $this->requestParams['description'];

        if (empty($themeName) || empty($description)) {
            throw new PushApiException(PushApiException::EMPTY_PARAMS);
        }

        if (!$theme = Theme::getInfoByName($themeName)) {
            throw new PushApiException(PushApiException::NOT_FOUND, "Theme not found");
        }

        if (!$subject = Subject::createSubject($theme['id'], $description)) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }

        $this->send($subject);
    }

    /**
     * Retrieves all edited subjects or the subject information given its id.
     * @param int $idSubject Subject identification
     */
    public function getSubject($idSubject)
    {
        if (!$subject = Subject::getSubject($idSubject)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($subject);
    }

    /**
     * Updates subject description given a subject id.
     * @param int $idSubject User identification.
     * @throws PushApiException
     *
     * Call params:
     * @var "description" required
     */
    public function updateSubject($idSubject)
    {
        $update = array();

        if (!isset($this->requestParams['description']) || empty($this->requestParams['description'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $update['description'] = $this->requestParams['description'];

        $update = $this->cleanParams($update);

        if (empty($update)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        if (!$subject = Subject::updateSubject($idSubject, $update)) {
            throw new PushApiException(PushApiException::ACTION_FAILED);
        }

        $this->send($subject);
    }

    /**
     * Deletes a subject given its id.
     * @param int $idSubject Subject identification.
     */
    public function deleteSubject($idSubject)
    {
        if (!$subject = Subject::remove($idSubject)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($subject);
    }

    /**
     * Retrieves all subjects registered.
     * @throws PushApiException
     *
     * Call params:
     * @var "limit" optional
     * @var "page" optional
     */
    public function getSubjects()
    {
        $limit = (isset($this->requestParams['limit']) ? $this->requestParams['limit'] : 10);
        $page = (isset($this->requestParams['page']) ? $this->requestParams['page'] : 1);

        if ($limit <= 0) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid limit value");
        }

        if ($page < 1) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid page value");
        }

        if (!$subjects = Subject::getSubjects($limit, $page)) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($subjects);
    }
}