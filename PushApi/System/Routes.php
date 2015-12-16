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

// Retreieving the headers and the encoded params for each HTTP request.
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
 * Middleware that gets the headers and checks if application has authorization
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
// Creates a new app or retrieves the app if it was created before
$slim->post('/app', function() use ($params) {
    (new AppController($params))->setApp();
});
$slim->group('/app/:id', $authChecker, function() use ($slim, $params) {
    // Gets the app $id
    $slim->get('', function($id) {
        (new AppController())->getApp($id);
    });
    // Updates app $id given put params or retrieves a new app secret
    $slim->put('', function($id) use ($params) {
        (new AppController($params))->updateApp($id);
    });
    // Deletes app $id
    $slim->delete('', function($id) {
        (new AppController())->deleteApp($id);
    });
});
// Getting all apps
$slim->get('/apps', $authChecker, function() {
    (new AppController())->getApps();
});

///////////////////////////////////
//         USER ROUTES           //
///////////////////////////////////
$slim->group('/user', $authChecker, function() use ($slim, $params) {
    // Creates user $id or retrieves user if it was created before
    $slim->post('', function() use ($params) {
        (new UserController($params))->setUser();
    });
    $slim->group('/:id', function() use ($slim, $params) {
        // Gets user $id
        $slim->get('', function($id) {
            (new UserController())->getUser($id);
        });
        // Deletes user $id
        $slim->delete('', function($id) {
            (new UserController())->deleteUser($id);
        });
        // Gets user $id the devices that has registered
        $slim->get('/smartphones', function($id) {
            (new UserController())->getSmartphonesRegistered($id);
        });
        // Adds new new devices ids to user
        $slim->post('/device', function($id) use ($params) {
            (new UserController($params))->addUserDevice($id);
        });
        // Gets device information given some params
        $slim->get('/device', function($id) use ($params) {
            (new UserController($params))->getUserDeviceInfoByParams($id);
        });
        // Gets device information given the id
        $slim->get('/device/:iddevice', function($id, $iddevice) {
            (new UserController())->getUserDeviceInfo($id, $iddevice);
        });
        // Removes a device form user
        $slim->delete('/device/:iddevice', function($id, $iddevice) {
            (new UserController())->removeUserDevice($id, $iddevice);
        });
        // Removes all user devices of the given type
        $slim->delete('/device/type/:type', function($id, $type) {
            (new UserController())->removeUserDeviceByType($id, $type);
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
                (new SubscriptionController())->getSubscriptions($id);
            });
            // Gets user $id subscription $idchannel
            $slim->get('/:idchannel', function($id, $idchannel) {
                (new SubscriptionController())->getSubscription($id, $idchannel);
            });
            // Deletes user $id subscriptions
            $slim->delete('/:idchannel', function($id, $idchannel) {
                (new SubscriptionController())->deleteSubscription($id, $idchannel);
            });
        });
        //////////////////////////////////////////
        //         PREFERENCES ROUTES           //
        //////////////////////////////////////////
        $slim->group('/preferences', function() use ($slim, $params) {
            // Gets user preferences
            $slim->get('', function($id) {
                (new PreferenceController())->getPreferences($id);
            });
            // Updates all user preferences
            $slim->put('', function($id) use ($params) {
                (new PreferenceController($params))->updateAllPreferences($id);
            });
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
    // (non repeated and valid email).
    $slim->post('', function() use ($params) {
        (new UserController($params))->setUsers();
    });
    // Getting all users
    $slim->get('', function() use ($params) {
        (new UserController($params))->getUsers();
    });
});

//////////////////////////////////////
//         CHANNEL ROUTES           //
//////////////////////////////////////
$slim->group('/channel', $authChecker, function() use ($slim, $params) {
    // Creates channel $id or retrieves channel if it was created before
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
$slim->group('/channels', $authChecker, function() use ($slim, $params) {
    // Getting all channels
    $slim->get('', function() use ($params) {
        (new ChannelController($params))->getChannels();
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
    // Creates theme $id or retrieves theme if it was created before
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
$slim->group('/themes', $authChecker, function() use ($slim, $params) {
    // Getting all themes
    $slim->get('', function() use ($params) {
        (new ThemeController($params))->getThemes();
    });
    // Get all themes by $range
    $slim->get('/range/:range', function($range) use ($params) {
        (new ThemeController($params))->getThemesByRange($range);
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
    // Creates subject retrieves it if it was created before
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
$slim->group('/subjects', $authChecker, function() use ($slim, $params) {
    // Getting all subjects
    $slim->get('', function() use ($params) {
        (new SubjectController($params))->getSubjects();
    });
});

///////////////////////////////////
//         SEND ROUTES           //
///////////////////////////////////
$slim->post('/send', $authChecker, function() use ($slim, $params) {
    (new LogController($params))->sendMessage();
});

//////////////////////////////////
//         LOG ROUTES           //
//////////////////////////////////
// $slim->group('/log', $authChecker, function() use ($slim, $params) {
//     // // Creates log retrieves it if it was created before
//     // $slim->post('', function() use ($params) {
//     //     (new LogController($params))->setSubject();
//     // });
//     // Gets log $idSubject
//     $slim->get('/:idlog', function($idSubject) {
//         (new LogController())->getSubject($idSubject);
//     });
//     // // Updates log $idSubject given put params
//     // $slim->put('/:idlog', function($idSubject) use ($params) {
//     //     (new LogController($params))->updateSubject($idSubject);
//     // });
//     // Deletes log $idSubject
//     $slim->delete('/:idlog', function($idSubject) {
//         (new LogController())->deleteSubject($idSubject);
//     });
// });
// $slim->group('/logs', $authChecker, function() use ($slim, $params) {
//     // Getting all logs
//     $slim->get('', function() use ($params) {
//         (new LogController($params))->getSubjects();
//     });
// });