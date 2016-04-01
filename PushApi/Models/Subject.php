<?php

namespace PushApi\Models;

use \Slim\Log;
use \PushApi\PushApi;
use \PushApi\System\IModel;
use \PushApi\PushApiException;
use \Illuminate\Database\Eloquent\Model as Eloquent;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * A subject is the description of the theme->name and it is used for example as
 * a mail subject. An example is that you have a theme name like:
 *     user_comment
 * and you want to send it via mail with a better description like:
 *     A user has commented on your profile wall
 * This model of the subjects table, manages all the relationships and dependencies
 * that can be done on these table in order to improve theme names descriptions.
 */
class Subject extends Eloquent implements IModel
{
    public $timestamps = false;
	public $fillable = array('name', 'description');
    protected $hidden = array('created');

    /**
     * Returns the basic displayable Subject model.
     * @return array
     */
    public static function getEmptyDataModel()
    {
        return [
            "id" => 0,
            "theme_id" => "",
            "description" => "",
        ];
    }

    /**
     * Relationship 1-1 to get an instance of the themes table.
     * @return Themes Instance of themes model.
     */
    public function theme()
    {
        return $this->belongsTo('\PushApi\Models\Theme');
    }

    /**
     * Checks if subject exists and returns it if true.
     * @param  int $id
     * @return Subject/false
     */
    public static function checkExists($id)
    {
        try {
            $subject = Subject::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $subject;
    }

    public static function generateFromModel($subject)
    {
        $result = self::getEmptyDataModel();
        try {
            $result['id'] = $subject->id;
            $result['theme_id'] = $subject->theme_id;
            $result['description'] = $subject->description;
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }

    /**
     * Retrieves the subject given its theme id reference.
     * @param  int $idTheme
     * @return array/boolean
     */
    public static function getSubjectByThemeId($idTheme)
    {
        $subject = Subject::where('theme_id', $idTheme)->first();

        if ($subject) {
            return self::generateFromModel($subject);
        }

        return false;
    }

    /**
     * Retrieves the subject given its theme name reference.
     * @param  string $themeName
     * @return array/boolean
     */
    public static function getSubjectByThemeName($themeName)
    {
        $theme = Theme::getInfoByName($themeName);

        if (!$theme) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND . " - Theme not found", Log::DEBUG);
            return false;
        }

        return self::getSubjectByThemeId($theme['id']);
    }

    /**
     * Obtains all information about target subject given its id.
     * @param  int $id
     * @return array
     */
    public static function getSubject($id)
    {
        try {
            $subject = Subject::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return self::generateFromModel($subject);
    }
    /**
     * Creates a new subject if it does not exist yet.
     * @param  string $themeId
     * @param  string $description
     * @return array
     */
    public static function createSubject($themeId, $description)
    {
        if ($subjectExists = self::getSubjectByThemeId($themeId)) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::DUPLICATED_VALUE, Log::DEBUG);
            return false;
        }

        $subject = new Subject;
        $subject->theme_id = $themeId;
        $subject->description = $description;
        $subject->save();

        return $subject;
    }

    /**
     * Updates the target subject with the available updating values.
     * @param  int $id
     * @param  array $update
     * @return boolean
     */
    public static function updateSubject($id, $update)
    {
        if (!$subject = self::checkExists($id)) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        foreach ($update as $key => $value) {
            $subject->$key = $value;
        }

        try {
            $subject->update();
        } catch (QueryException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::DUPLICATED_VALUE, Log::DEBUG);
            return false;
        }

        return true;
    }

    /**
     * Deletes the target subject given its id.
     * @param  int $id
     * @return boolean
     */
    public static function remove($id)
    {
        try {
            $subject = Subject::findOrFail($id);
            $subject->delete();
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return true;
    }

    /**
     * Deletes the target subject given its theme id.
     * @param  int $id
     * @return boolean
     */
    public static function removeByThemeId($themeId)
    {
        if (!$subject = self::getSubjectByThemeId($themeId)) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return self::remove($subject['id']);
    }

    /**
     * Obtains all subjects registered. It can be searched giving limit and page values.
     * @param  int $limit
     * @param  int $page
     * @return array
     */
    public static function getSubjects($limit = 10, $page = 1)
    {
        $result = [
            'subjects' => []
        ];
        $skip = 0;
        // Updating the page offset
        if ($page != 1) {
            $skip = ($page - 1) * $limit;
        }

        $result['limit'] = (int) $limit;
        $result['page'] = (int) $page;
        try {
            $subjects = Subject::orderBy('id', 'asc')->take($limit)->offset($skip)->get();

            $result['totalInPage'] = sizeof($subjects);

            foreach ($subjects as $subjects) {
                $result['subjects'][] = self::generateFromModel($subjects);
            }
        } catch (ModelNotFoundException $e) {
            PushApi::log(__METHOD__ . " - Error: " . PushApiException::NOT_FOUND, Log::DEBUG);
            return false;
        }

        return $result;
    }
}