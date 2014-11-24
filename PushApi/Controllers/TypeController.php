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
            $range = $this->slim->request->post('range');

            if (!isset($name) && !isset($range)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }

            if (!in_array($range, Type::getValidValues(), true)) {
                throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range types: " . Type::UNICAST . ", " . Type::MULTICAST . ", " . Type::BROADCAST);
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
            $update['range'] = $this->slim->request->put('range');

            if (isset($update['range']) && !in_array($update['range'], Type::getValidValues(), true)) {
                throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range types: " . Type::UNICAST . ", " . Type::MULTICAST . ", " . Type::BROADCAST);
            }
            
            $update = $this->cleanParams($update);

            if (empty($update)) {
                throw new PushApiException(PushApiException::NO_DATA);
            }
            
            $type = Type::find($id);
            foreach ($update as $key => $value) {
                $type->$key = $value;
            }
            $type->update();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }
        $this->send($type->toArray());
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

    /**
     * Retrives all types registered given a range
     * @param  [int] $range Value refering the range of the type
     */
    public function getByRange($range)
    {
        try {
            if (!in_array($update['range'], Type::getValidValues(), true)) {
                throw new PushApiException(PushApiException::INVALID_RANGE, "Valid range types: " . Type::UNICAST . ", " . Type::MULTICAST . ", " . Type::BROADCAST);
            }
            ////////////////////////////////
            // We need to search by name  //
            ////////////////////////////////
            $type = Type::where('range', $range)->orderBy('id', 'asc')->get();
        } catch (ModelNotFoundException $e) {
            throw new PushApiException(PushApiException::NOT_FOUND);
        }

        $this->send($type->toArray());
    }
}