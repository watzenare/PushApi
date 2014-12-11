<?php 

use \Slim\Route;
use \PushApi\PushApiException;
use \PushApi\Controllers\Controller;
use \PushApi\Controllers\AppController;
use \PushApi\Controllers\LogController;
use \PushApi\Controllers\UserController;
use \PushApi\Controllers\ThemeController;
use \PushApi\Controllers\ChannelController;
use \PushApi\Controllers\SubscriptionController;
use \PushApi\Controllers\PreferenceController;
use \PushApi\Controllers\SubjectController;

Route::setDefaultConditions(
    array(
        'id' => '\d+',
        'idchannel' => '\d+',
        'idtheme' => '\d+',
    )
);

/**
 * Middleware that gets the headers and checks if application has autorization
 * @param  SlimRoute $route Routing params
 */
$authChecker = function () {
    (new AppController())->checkAuth();
};

////////////////////////////////////
//          AUTH ROUTES           //
////////////////////////////////////
// Creates a new app or retrives the app if it was created before
$slim->post('/app', function() {
    (new AppController())->setApp();
});
$slim->group('/app/:id', $authChecker, function() use ($slim) {
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
// Geting all apps
$slim->get('/apps', $authChecker, function() {
    (new AppController())->getApps();
});

///////////////////////////////////
//         USER ROUTES           //
///////////////////////////////////
$slim->group('/user', $authChecker, function() use ($slim) {
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
            (new SubscriptionController())->setSubscribed($id, $idchannel);
        });
        $slim->group('/subscribed', function() use ($slim) {
            // Gets user subscriptions
            $slim->get('', function($id) {
                (new SubscriptionController())->getSubscribed($id);
            });
            // Gets user $id subscription $idchannel
            $slim->get('/:idchannel', function($id, $idchannel) {
                (new SubscriptionController())->getSubscribed($id, $idchannel);
            });
            // Deletes user $id subscriptions
            $slim->delete('/:idchannel', function($id, $idchannel) {
                (new SubscriptionController())->deleteSubscribed($id, $idchannel);
            });
        });
        //////////////////////////////////////////
        //         PREFERENCES ROUTES           //
        //////////////////////////////////////////
        // Gets user preferences
        $slim->get('/preferences', function($id) {
            (new PreferenceController())->getPreference($id);
        });
        $slim->group('/preference', function() use ($slim) {
            // Adds a preference to a user
            $slim->post('/:idtheme', function($id, $idtheme) {
                (new PreferenceController())->setPreference($id, $idtheme);
            });
            // Gets user preference
            $slim->get('/:idtheme', function($id, $idtheme) {
                (new PreferenceController())->getPreference($id, $idtheme);
            });
            // Updates user preference
            $slim->put('/:idtheme', function($id, $idtheme) {
                (new PreferenceController())->updatePreference($id, $idtheme);
            });
            // Deletes user preference
            $slim->delete('/:idtheme', function($id, $idtheme) {
                (new PreferenceController())->deletePreference($id, $idtheme);
            });
        });
    });
});
$slim->group('/users', $authChecker, function() use ($slim) {
    // Creates users passed, separated by coma, and adds only the valid users
    // (non repeated and valid email)
    $slim->post('', function() {
        (new UserController())->setUsers();
    });
    // Geting all users
    $slim->get('', function() {
        (new UserController())->getUser();
    });
});

//////////////////////////////////////
//         CHANNEL ROUTES           //
//////////////////////////////////////
$slim->group('/channel', $authChecker, function() use ($slim) {
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
$slim->group('/channels', $authChecker, function() use ($slim) {
    // Geting all channels
    $slim->get('', function() {
        (new ChannelController())->getChannel();
    });
});

////////////////////////////////////
//         THEME ROUTES           //
////////////////////////////////////
$slim->group('/theme', $authChecker, function() use ($slim) {
    // Creates theme $id or retrives theme if it was created before
    $slim->post('', function() {
        (new ThemeController())->setTheme();
    });
    // Gets theme $id
    $slim->get('/:id', function($id) {
        (new ThemeController())->getTheme($id);
    });
    // Updates theme $id given put params
    $slim->put('/:id', function($id) {
        (new ThemeController())->updateTheme($id);
    });
    // Deletes theme $id
    $slim->delete('/:id', function($id) {
        (new ThemeController())->deleteTheme($id);
    });
});
$slim->group('/themes', $authChecker, function() use ($slim) {
    // Geting all themes
    $slim->get('', function() {
        (new ThemeController())->getTheme();
    });
    // Get all themes by $range
    $slim->get('/range/:range', function($range) {
        (new ThemeController())->getByRange($range);
    });
});

///////////////////////////////////
//         SEND ROUTES           //
///////////////////////////////////
$slim->post('/send', $authChecker, function() use ($slim) {
    (new LogController())->sendMessage();
});

//////////////////////////////////////
//         SUBJECT ROUTES           //
//////////////////////////////////////
$slim->group('/subject', $authChecker, function() use ($slim) {
    // Creates subject retrives it if it was created before
    $slim->post('', function() {
        (new SubjectController())->setSubject();
    });
    // Gets subject $idSubject
    $slim->get('/:idsubject', function($idSubject) {
        (new SubjectController())->getSubject($idSubject);
    });
    // Updates subject $idSubject given put params
    $slim->put('/:idsubject', function($idSubject) {
        (new SubjectController())->updateSubject($idSubject);
    });
    // Deletes subject $idSubject
    $slim->delete('/:idsubject', function($idSubject) {
        (new SubjectController())->deleteSubject($idSubject);
    });
});
$slim->group('/subjects', $authChecker, function() use ($slim) {
    // Geting all subjects
    $slim->get('', function() {
        (new SubjectController())->getSubject();
    });
});