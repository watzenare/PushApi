<?php

/**
 * Migration functionalities that should be done before run all the new user functionalities.
 * Foreach user it is stored its destination identifications and they are stored into the new table 'Devices'.
 * TODO: update the Users table with new column types.
 * Foreach user update its device value counter.
 */

function dbConnect()
{
    // Create connection
    $conn = new mysqli(
        'localhost',
        'pushapi',
        'f69fda8c6dbbe18b',
        'pushdb'
    );

    // Check connection
    if ($conn->connect_error) {
        Utils::p("Connection failed: " . $conn->connect_error);
        return null;
    }

    return $conn;
}

$db = dbConnect();

$users = $db->query("SELECT * FROM users");

foreach ($users as $user) {
    if (!empty($user['email'])) {
        var_dump($db->query("INSERT INTO `devices` (`type`, `user_id`, `device_id`, `created`) VALUES (" . 1 . ", " . $user['id'] . ", '" . $user['email'] . "', now())") === true);
    }

    if (!empty($user['android_id'])) {
        var_dump($db->query("INSERT INTO `devices` (`type`, `user_id`, `device_id`, `created`) VALUES (" . 2 . ", " . $user['id'] . ", '" . $user['android_id'] . "', now())") === true);
    }

    if (!empty($user['ios_id'])) {
        var_dump($db->query("INSERT INTO `devices` (`type`, `user_id`, `device_id`, `created`) VALUES (" . 2 . ", " . $user['id'] . ", '" . $user['ios_id'] . "', now())") === true);
    }
}

var_dump("All done, DROP and CREATE the Users table again.");

$db->close();