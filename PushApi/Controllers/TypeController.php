<?php

namespace PushApi\Controllers;

use \PushApi\PushApiException;
use \PushApi\Models\Type;
use \PushApi\Controllers\Controller;
use \Illuminate\Database\QueryException;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic and general actions that can be done with a type.
 */
class TypeController extends Controller
{
    /**
     * Creates a new type into the registration with given params and
     * displays the information of the created type. If the type tries
     * to registrate twice (checked by name), the information of the 
     * saved type is displayed without adding him again into the 
     * registration
     */
    public function setType()
    {
        try {
            $name = $this->slim->request->post('name');
            $range = (int) $this->slim->request->post('range');

            if (!isset($name) && !isset($range)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if ($range > Type::BROADCAST || $range < Type::UNICAST) {
                throw new PushApiException(PushApiException::INVALID_RANGE);
            }

            // Checking if type already exists
            $type = Type::where('name', $name)->first();

            if (isset($type->name)) {
                $this->send($type->toArray());
            } else {
                $type = new Type;
                $type->name = $name;
                $type->range = $range;
                $type->save();
            }
        } catch (QueryException $e) {
            throw new PushApiException(PushApiException::DUPLICATED_VALUE);
        }        $this->send($type->toArray());
    }

    /**
     * Retrives type information if it is registered
     * @param [int] $id  Type identification
     */
    public function getType($id)
    {
        try {
            $type = Type::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($type->toArray());
    }

    /**
     * Updates type infomation given its identification and params to update
     * @param [int] $id  Type identification
     */
    public function updateType($id)
    {
        try {
            $update = array();
            $update['name'] = $this->slim->request->put('name');
            $update['range'] = (int) $this->slim->request->put('range');

            if ($update['range'] > Type::BROADCAST || $update['range'] < Type::UNICAST) {
                throw new PushApiException(PushApiException::INVALID_RANGE);
            }
            
            $update = $this->cleanParams($update);

            if (empty($update)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            $type = Type::where('id', $id)->update($update);
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($this->boolinize($type));
    }

    /**
     * Deletes a type given its identification
     * @param [int] $id  Type identification
     */
    public function deleteType($id)
    {
        try {
            $type = Type::findOrFail($id);
            $type->delete();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($type->toArray());
    }

    /**
     * Retrives all types registered
     */
    public function getAllTypes()
    {
        try {
            $type = Type::orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($type->toArray());
    }
}