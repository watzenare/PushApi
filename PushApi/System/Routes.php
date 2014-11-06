<?php 

use \PushApi\Controllers\UserController;
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
$this->slim->group('/user', function() {
    $this->slim->post('', function () {
        (new UserController($this->slim))->setUser();
    });

    $this->slim->get('/:id', function ($id) {
        (new UserController($this->slim))->getUser($id);
    });

    $this->slim->put('/:id', function ($id) {
        (new UserController($this->slim))->updateUser($id);
    });

    $this->slim->delete('/:id', function ($id) {
        (new UserController($this->slim))->deleteUser($id);
    });
});

$this->slim->get('/users', function () {
    (new UserController($this->slim))->getAllUsers();
});