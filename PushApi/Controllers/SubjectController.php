<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Controllers\Controller;
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

        $this->send(Subject::createSubject($themeName, $description));
    }

    /**
     * Retrives all edited subjects or the subject information given its id
     * @param int $idSubject Subject identification
     */
    public function getSubject($idSubject)
    {
        $this->send(Subject::getSubject($idSubject));
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

        if (!isset($this->requestParams['description'])) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        $update['description'] = $this->requestParams['description'];

        $this->send(Subject::updateSubject($idSubject, $update));
    }

    /**
     * Deletes a subject given its id.
     * @param int $idSubject Subject identification.
     */
    public function deleteSubject($idSubject)
    {
        $this->send(Subject::remove($idSubject));
    }

    /**
     * Retrives all subjects registred.
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

        if ($limit < 0) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid limit value");
        }

        if ($page < 1) {
            throw new PushApiException(PushApiException::INVALID_RANGE, "Invalid page value");
        }

        $this->send(Subject::getSubjects($limit, $page));
    }
}