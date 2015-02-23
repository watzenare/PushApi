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
use \PushApi\Controllers\TrackingController;

// Retreiving the headers and the encoded params for each HTTP request.
$params = array();

$method = $slim->request->getMethod();

switch ($method) {
    case 'GET':
        $params = $slim->request->get();
        break;

    case 'POST':
        $params = $slim->request->post();
        break;

    case 'PUT':
        $params = $slim->request->put();
        break;

    case 'DELETE':
        $params = $slim->request->delete();
        break;

    default:
        $slim->stop();
        break;
}

// Customized HTTP headers
$params['X-App-Id'] = $slim->request->headers->get('X-App-Id');
$params['X-App-Auth'] = $slim->request->headers->get('X-App-Auth');

/**
 * Middleware that gets the headers and checks if application has autorization
 * @param  SlimRoute $route Routing params
 */
$authChecker = function() use ($params) {
    (new AppController($params))->checkAuth();
};

/**
 * Validating the format of received url's
 */
Route::setDefaultConditions(
    array(
        'id' => '\d+',
        'idchannel' => '\d+',
        'idtheme' => '\d+',
    )
);

///////////////////////////////////////
//          TRACKING ROUTE           //
///////////////////////////////////////
// Returns an 1x1 pixel while tracks the user
$slim->get('/tracking/px.gif', function() use ($params) {
    (new TrackingController($params))->getTrackingPixel();
});

////////////////////////////////////
//          AUTH ROUTES           //
////////////////////////////////////
// Creates a new app or retrives the app if it was created before
$slim->post('/app', function() use ($params) {
    (new AppController($params))->setApp();
});
$slim->group('/app/:id', $authChecker, function() use ($slim, $params) {
    // Gets the app $id
    $slim->get('', function($id) {
        (new AppController())->getApp($id);
    });
    // Updates app $id given put params or retrives a new app secret
    $slim->put('', function($id) use ($params) {
        (new AppController($params))->updateApp($id);
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
$slim->group('/user', $authChecker, function() use ($slim, $params) {
    // Creates user $id or retrives user if it was created before
    $slim->post('', function() use ($params) {
        (new UserController($params))->setUser();
    });
    $slim->group('/:id', function() use ($slim, $params) {
        // Gets user $id
        $slim->get('', function($id) {
            (new UserController())->getUser($id);
        });
        // Updates user $id given put params
        $slim->put('', function($id) use ($params) {
            (new UserController($params))->updateUser($id);
        });
        // Deletes user $id
        $slim->delete('', function($id) {
            (new UserController())->deleteUser($id);
        });
        ////////////////////////////////////////
        //         SUBSCRIBE ROUTES           //
        ////////////////////////////////////////
        // Subscribes a user to a channel
        $slim->post('/subscribe/:idchannel', function($id, $idchannel) use ($params) {
            (new SubscriptionController($params))->setSubscribed($id, $idchannel);
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
        $slim->group('/preference', function() use ($slim, $params) {
            // Adds a preference to a user
            $slim->post('/:idtheme', function($id, $idtheme) use ($params) {
                (new PreferenceController($params))->setPreference($id, $idtheme);
            });
            // Gets user preference
            $slim->get('/:idtheme', function($id, $idtheme) {
                (new PreferenceController())->getPreference($id, $idtheme);
            });
            // Updates user preference
            $slim->put('/:idtheme', function($id, $idtheme) use ($params) {
                (new PreferenceController($params))->updatePreference($id, $idtheme);
            });
            // Deletes user preference
            $slim->delete('/:idtheme', function($id, $idtheme) {
                (new PreferenceController())->deletePreference($id, $idtheme);
            });
        });
    });
});
$slim->group('/users', $authChecker, function() use ($slim, $params) {
    // Creates users passed, separated by coma, and adds only the valid users
    // (non repeated and valid email)
    $slim->post('', function() use ($params) {
        (new UserController($params))->setUsers();
    });
    // Geting all users
    $slim->get('', function() {
        (new UserController())->getUser();
    });
});

//////////////////////////////////////
//         CHANNEL ROUTES           //
//////////////////////////////////////
$slim->group('/channel', $authChecker, function() use ($slim, $params) {
    // Creates channel $id or retrives channel if it was created before
    $slim->post('', function() use ($params) {
        (new ChannelController($params))->setChannel();
    });
    // Gets channel $id
    $slim->get('/:id', function($id) {
        (new ChannelController())->getChannel($id);
    });
    // Updates channel $id given put params
    $slim->put('/:id', function($id) use ($params) {
        (new ChannelController($params))->updateChannel($id);
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
// Gets channel information given its name
$slim->get('/channel_name', function() use ($params) {
    (new ChannelController($params))->getChannelByName();
});

////////////////////////////////////
//         THEME ROUTES           //
////////////////////////////////////
$slim->group('/theme', $authChecker, function() use ($slim, $params) {
    // Creates theme $id or retrives theme if it was created before
    $slim->post('', function() use ($params) {
        (new ThemeController($params))->setTheme();
    });
    // Gets theme $id
    $slim->get('/:id', function($id) {
        (new ThemeController())->getTheme($id);
    });
    // Updates theme $id given put params
    $slim->put('/:id', function($id) use ($params) {
        (new ThemeController($params))->updateTheme($id);
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
// Gets theme information given its name
$slim->get('/theme_name', function() use ($params) {
    (new ThemeController($params))->getThemeByName();
});

//////////////////////////////////////
//         SUBJECT ROUTES           //
//////////////////////////////////////
$slim->group('/subject', $authChecker, function() use ($slim, $params) {
    // Creates subject retrives it if it was created before
    $slim->post('', function() use ($params) {
        (new SubjectController($params))->setSubject();
    });
    // Gets subject $idSubject
    $slim->get('/:idsubject', function($idSubject) {
        (new SubjectController())->getSubject($idSubject);
    });
    // Updates subject $idSubject given put params
    $slim->put('/:idsubject', function($idSubject) use ($params) {
        (new SubjectController($params))->updateSubject($idSubject);
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

///////////////////////////////////
//         SEND ROUTES           //
///////////////////////////////////
$slim->post('/send', $authChecker, function() use ($slim, $params) {
    (new LogController($params))->sendMessage();
});