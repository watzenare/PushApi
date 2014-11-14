<?php 

use \PushApi\PushApiException;
use \PushApi\Controllers\AppController;
use \PushApi\Controllers\UserController;
use \PushApi\Controllers\ChannelController;
use \PushApi\Controllers\SubscribedController;

// Use always authApp because only can be called by an enabled app
// Use sometimes authAdmin when you use critical calls (most of all delete)
// Admin == system && App == enabled apps
// $this->slim->post('/app', 'authApp', 'authAdmin', function() {});


////////////////////////////////////
//          AUTH ROUTES           //
////////////////////////////////////
$slim->group('/app', function() use ($slim) {
    // Creates a new app or retrives the app if it was created before
    $slim->post('', function() {
        (new AppController())->setApp();
    });

    $slim->group('/:id', function() use ($slim) {
        // Gets the app $id
        $slim->get('', function($id) {
            (new AppController())->getApp($id);
        });
        // Updates app $id given put params or retrives a new app secret
        $slim->put('', function($id) {
            (new AppController())->updateApp($id);
        });
        // Deletes app $id
        $slim->delete('', function($id) {
            (new AppController())->deleteApp($id);
        });
    });
});
// Geting all apps
$slim->get('/apps', function() {
    (new AppController())->getApps();
});

// function authenticate(\Slim\Route $route) {
//     // Getting request headers
//     $headers = apache_request_headers();

//     // Verifying Authorization Header
//     if (isset($headers['Authorization'])) {
//     } else {
//          $slim->stop();
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