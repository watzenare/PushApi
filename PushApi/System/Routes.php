<?php 

use \Slim\Route;
use \PushApi\PushApiException;
use \PushApi\Controllers\AppController;
use \PushApi\Controllers\UserController;
use \PushApi\Controllers\ChannelController;
use \PushApi\Controllers\SubscribedController;
use \PushApi\Controllers\TypeController;
use \PushApi\Controllers\SendController;

Route::setDefaultConditions(
    array(
        'id' => '\d+',
    )
);

/**
 * Middleware that gets the headers and checks if application has autorization
 * @param  SlimRoute $route Routing params
 */
function authChecker(Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    (new AppController())->checkAuth($headers);
}

////////////////////////////////////
//          AUTH ROUTES           //
////////////////////////////////////
$slim->group('/app', 'authChecker', function() use ($slim) {
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
$slim->get('/apps', 'authChecker', function() {
    (new AppController())->getApps();
});

///////////////////////////////////
//         USER ROUTES           //
///////////////////////////////////
$slim->group('/user', 'authChecker', function() use ($slim) {
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
        //////////////////////////////////////////
        //         PREFERENCES ROUTES           //
        //////////////////////////////////////////
        // Gets user preferences
        $slim->get('/preferences', function($id) {
            (new UserController())->getPreferences($id);
        });
        $slim->group('/preference', function() use ($slim) {
            // Adds a preference to a user
            $slim->post('/:idtype', function($id, $idtype) {
                (new UserController())->setPreference($id, $idtype);
            });
            // Gets user preference
            $slim->get('/:idtype', function($id, $idtype) {
                (new UserController())->getPreference($id, $idtype);
            });
            // Updates user preference
            $slim->put('/:idtype', function($id, $idtype) {
                (new UserController())->updatePreference($id, $idtype);
            });
            // Deletes user preference
            $slim->delete('/:idtype', function($id, $idtype) {
                (new UserController())->deletePreference($id, $idtype);
            });
        });
    });
});
// Geting all users
$slim->group('/users', 'authChecker', function() use ($slim) {
    // Creates users passed, separated by coma, and adds only the valid users
    // (non repeated and valid email)
    $slim->post('', function() {
        (new UserController())->setUsers();
    });
    // Geting all users
    $slim->get('', function() {
        (new UserController())->getAllUsers();
    });
});

//////////////////////////////////////
//         CHANNEL ROUTES           //
//////////////////////////////////////
$slim->group('/channel', 'authChecker', function() use ($slim) {
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

$slim->group('/channels', 'authChecker', function() use ($slim) {
    // Geting all channels
    $slim->get('', function() {
        (new ChannelController())->getAllChannels();
    });
});

///////////////////////////////////
//         TYPE ROUTES           //
///////////////////////////////////
$slim->group('/type', 'authChecker', function() use ($slim) {
    // Creates type $id or retrives type if it was created before
    $slim->post('', function() {
        (new TypeController())->setType();
    });
    // Gets user $id
    $slim->get('/:id', function($id) {
        (new TypeController())->getType($id);
    });
    // Updates type $id given put params
    $slim->put('/:id', function($id) {
        (new TypeController())->updateType($id);
    });
    // Deletes type $id
    $slim->delete('/:id', function($id) {
        (new TypeController())->deleteType($id);
    });
});

$slim->group('/types', 'authChecker', function() use ($slim) {
    // Geting all types
    $slim->get('', function() {
        (new TypeController())->getAllTypes();
    });
    // Get all types by $range
    $slim->get('/range/:range', function($range) {
        (new TypeController())->getByRange($range);
    });
});

$slim->post('/send', 'authChecker', function() use ($slim) {
    (new SendController())->sendMessage();
});