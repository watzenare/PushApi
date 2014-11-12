<?php 

use \PushApi\PushApiException;
use \PushApi\Controllers\UserController;
use \PushApi\Controllers\ChannelController;
use \PushApi\Controllers\SubscribedController;

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
    // Creates user $id or retrives user if it was created before
    $this->slim->post('', function() {
        // (new UserController($this->slim))->setUser();
    });
    $this->slim->group('/:id', function() {
        // Gets user $id
        $this->slim->get('', function($id) {
            (new UserController($this->slim))->getUser($id);
        });
        // Updates user $id given put params
        $this->slim->put('', function($id) {
            (new UserController($this->slim))->updateUser($id);
        });
        // Deletes user $id
        $this->slim->delete('', function($id) {
            (new UserController($this->slim))->deleteUser($id);
        });
        ////////////////////////////////////////
        //         SUBSCRIBE ROUTES           //
        ////////////////////////////////////////
        // Subscribes a user to a channel
        $this->slim->post('/subscribe/:idchannel', function($id, $idchannel) {
            (new SubscribedController($this->slim))->setSubscribed($id, $idchannel);
        });
        $this->slim->group('/subscribed', function() {
            // Gets user $id subscriptions
            $this->slim->get('/:idchannel', function($id, $idchannel) {
                (new SubscribedController($this->slim))->getSubscribed($id, $idchannel);
            });
            // Deletes user $id subscriptions
            $this->slim->delete('/:idchannel', function($id, $idchannel) {
                (new SubscribedController($this->slim))->deleteSubscribed($id, $idchannel);
            });
        });
    });
});

$this->slim->group('/users', function() {
    // Geting all users
    $this->slim->get('', function () {
        (new UserController($this->slim))->getAllUsers();
    });
});

//////////////////////////////////////
//         CHANNEL ROUTES           //
//////////////////////////////////////
$this->slim->group('/channel', function() {
    // Creates channel $id or retrives channel if it was created before
    $this->slim->post('', function () {
        (new ChannelController($this->slim))->setChannel();
    });
    // Gets user $id
    $this->slim->get('/:id', function ($id) {
        (new ChannelController($this->slim))->getChannel($id);
    });
    // Updates channel $id given put params
    $this->slim->put('/:id', function ($id) {
        (new ChannelController($this->slim))->updateChannel($id);
    });
    // Deletes channel $id
    $this->slim->delete('/:id', function ($id) {
        (new ChannelController($this->slim))->deleteChannel($id);
    });
});
$this->slim->group('/channels', function() {
    // Geting all channels
    $this->slim->get('', function () {
        (new ChannelController($this->slim))->getAllChannels();
    });
    // Geting all channels with $level
    $this->slim->get('/level/:level', function ($level) {
        (new ChannelController($this->slim))->getLevel($level);
    });
});
