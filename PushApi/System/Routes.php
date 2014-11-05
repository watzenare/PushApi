<?php 

use \PushApi\Controllers\User;
use \PushApi\PushApiException;

// Use always authApp because only can be called by an enabled app
// Use sometimes authAdmin when you use critical calls (most of all delete)
// Admin == system && App == enabled apps
// $this->slim->post('/app', 'authApp', 'authAdmin', function () {}


////////////////////////////////////
//          AUTH ROUTES           //
////////////////////////////////////
// $this->slim->post('/app', function () {

// }

// $this->slim->get('/app/:id', function ($id) {

// }

// $this->slim->put('/app/:id', function ($id) {

// }

// $this->slim->delete('/app/:id', function ($id) {

// }

// $this->slim->get('/apps', function () {

// }
// function authenticate(\Slim\Route $route) {
//     // Getting request headers
//     $headers = apache_request_headers();
//     $response = array();
//     $app = \Slim\Slim::getInstance();

//     // Verifying Authorization Header
//     if (isset($headers['Authorization'])) {
//         $db = new DbHandler();

//         // get the api key
//         $api_key = $headers['Authorization'];
//         // validating api key
//         if (!$db->isValidApiKey($api_key)) {
//             // api key is not present in users table
//             $response["error"] = true;
//             $response["message"] = "Access Denied. Invalid Api key";
//             echoRespnse(401, $response);
//             $app->stop();
//         } else {
//             global $user_id;
//             // get user primary key id
//             $user_id = $db->getUserId($api_key);
//         }
//     } else {
//         // api key is missing in header
//         $response["error"] = true;
//         $response["message"] = "Api key is misssing";
//         echoRespnse(400, $response);
//         $app->stop();
//     }
// }


///////////////////////////////////
//         USER ROUTES           //
///////////////////////////////////
$this->slim->post('/user', function () {
    $result = false;
    $error = 0;
    try {
        $data = array();
        $data['username'] = $this->slim->request->post('username');
        $data['userId'] = $this->slim->request->post('userId');
        $data['email'] = $this->slim->request->post('email');

        if (!isset($data['username']) || !isset($data['userId']) || !isset($data['email'])) {
            throw new PushApiException(PushApiException::EMPTY_PARAMS);
        }

        $user = new User;
        $created = $user->createUser($data);
        if ($created) {
            $this->slim->response()->status(HTTP_OK);
            $result = array(
                'id' => $created
            );
        } else {
            $this->slim->response()->status(HTTP_NOT_MODIFIED);
        }
    } catch (PushApiException $e) {
        $this->slim->response()->status(HTTP_BAD_REQUEST);
        $this->slim->response()->header('X-Status-Reason', $e->getMessage());
        $result = $e->getMessage();
        $error = $e->getCode();
    }
    $this->sendResponse($result, $error);
});

$this->slim->get('/user/:id', function ($id) {
    $result = false;
    $error = 0;

    try {
        if (!isset($id)) {
            throw new PushApiException(PushApiException::EMPTY_PARAMS);
        }

        $user = new User;
        $result = $user->get($id);
        if ($result) {
            $this->slim->response()->status(HTTP_OK);
        } else {
            $this->slim->response()->status(HTTP_NOT_FOUND);
        }
    } catch (PushApiException $e) {
        $this->slim->response()->status(HTTP_BAD_REQUEST);
        $this->slim->response()->header('X-Status-Reason', $e->getMessage());
        $result = $e->getMessage();
        $error = $e->getCode();
    }
    $this->sendResponse($result, $error);
});

$this->slim->put('/user/:id', function ($id) {
    $result = false;
    $error = 0;

    try {
        $data = array();

        if (!isset($id)) {
            throw new PushApiException(PushApiException::EMPTY_PARAMS);
        }

        $data['username'] = $this->slim->request->put('username');
        $data['userId'] = $this->slim->request->put('userId');
        $data['email'] = $this->slim->request->put('email');

        if (!isset($data['username']) && !isset($data['userId']) && !isset($data['email'])) {
            throw new PushApiException(PushApiException::EMPTY_PARAMS);
        }

        $user = new User;
        $result = $user->update($id, $data);
        if ($result) {
            $this->slim->response()->status(HTTP_OK);
        } else {
            $this->slim->response()->status(HTTP_NOT_FOUND);
        }
    } catch (PushApiException $e) {
        $this->slim->response()->status(HTTP_BAD_REQUEST);
        $this->slim->response()->header('X-Status-Reason', $e->getMessage());
        $result = $e->getMessage();
        $error = $e->getCode();
    }
    $this->sendResponse($result, $error);
});

$this->slim->delete('/user/:id', function ($id) {
    $result = false;
    $error = 0;

    try {
        if (!isset($id)) {
            throw new PushApiException(PushApiException::EMPTY_PARAMS);
        }

        $user = new User;
        $result = $user->delete($id);
        if ($result) {
            $this->slim->response()->status(HTTP_OK);
        } else {
            $this->slim->response()->status(HTTP_NOT_FOUND);
        }
    } catch (PushApiException $e) {
        $this->slim->response()->status(HTTP_BAD_REQUEST);
        $this->slim->response()->header('X-Status-Reason', $e->getMessage());
        $result = $e->getMessage();
        $error = $e->getCode();
    }
    $this->sendResponse($result, $error);
});

$this->slim->get('/users', function () {
    $result = false;
    $error = 0;

    try {
        $user = new User;
        $result = $user->getUsers();
        if ($result) {
            $this->slim->response()->status(HTTP_OK);
        } else {
            $this->slim->response()->status(HTTP_NOT_FOUND);
        }
    } catch (PushApiException $e) {
        $this->slim->response()->status(HTTP_BAD_REQUEST);
        $this->slim->response()->header('X-Status-Reason', $e->getMessage());
        $result = $e->getMessage();
        $error = $e->getCode();
    }
    $this->sendResponse($result, $error);
});