<?php 

use \PushApi\PushApiException;
use \PushApi\Controllers\UserController;
use \PushApi\Controllers\ChannelController;
use \PushApi\Controllers\SubscribedController;

// Use always authApp because only can be called by an enabled app
// Use sometimes authAdmin when you use critical calls (most of all delete)
// Admin == system && App == enabled apps
// $this->slim->post('/app', 'authApp', 'authAdmin', function() {}


////////////////////////////////////
//          AUTH ROUTES           //
////////////////////////////////////
// $this->slim->post('/app', function() {

// }

// $this->slim->get('/app/:id', function($id) {

// }

// $this->slim->put('/app/:id', function($id) {

// }

// $this->slim->delete('/app/:id', function($id) {

// }

// $this->slim->get('/apps', function() {

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
$slim->group('/user', function() use ($slim) {
    // Creates user $id or retrives user if it was created before
    $slim->post('', function() {
        (new UserController())->setUser();
    });
    $slim->group('/:id', function() use ($slim) {
        // Gets user $id
        $slim->get('', function($id) {
            (new UserController())->getUser($id);
        });
        // Updates user $id given put params
        $slim->put('', function($id) {
            (new UserController())->updateUser($id);
        });
        // Deletes user $id
        $slim->delete('', function($id) {
            (new UserController())->deleteUser($id);
        });
        ////////////////////////////////////////
        //         SUBSCRIBE ROUTES           //
        ////////////////////////////////////////
        // Subscribes a user to a channel
        $slim->post('/subscribe/:idchannel', function($id, $idchannel) {
            (new UserController())->setSubscribed($id, $idchannel);
        });
        $slim->group('/subscribed', function() use ($slim) {
            // Gets user subscriptions
            $slim->get('', function($id) {
                (new UserController())->getSubscribed($id);
            });
            // Gets user $id subscription $idchannel
            $slim->get('/:idchannel', function($id, $idchannel) {
                (new UserController())->getSubscribed($id, $idchannel);
            });
            // Deletes user $id subscriptions
            $slim->delete('/:idchannel', function($id, $idchannel) {
                (new UserController())->deleteSubscribed($id, $idchannel);
            });
        });
    });
});

$slim->group('/users', function() use ($slim) {
    // Geting all users
    $slim->get('', function() {
        (new UserController())->getAllUsers();
    });
});

//////////////////////////////////////
//         CHANNEL ROUTES           //
//////////////////////////////////////
$slim->group('/channel', function() use ($slim) {
    // Creates channel $id or retrives channel if it was created before
    $slim->post('', function() {
        (new ChannelController())->setChannel();
    });
    // Gets user $id
    $slim->get('/:id', function($id) {
        (new ChannelController())->getChannel($id);
    });
    // Updates channel $id given put params
    $slim->put('/:id', function($id) {
        (new ChannelController())->updateChannel($id);
    });
    // Deletes channel $id
    $slim->delete('/:id', function($id) {
        (new ChannelController())->deleteChannel($id);
    });
});
$slim->group('/channels', function() use ($slim) {
    // Geting all channels
    $slim->get('', function() {
        (new ChannelController())->getAllChannels();
    });
    // Geting all channels with $level
    $slim->get('/level/:level', function($level) {
        (new ChannelController())->getLevel($level);
    });
});
