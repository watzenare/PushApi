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
        'db_host',
        'db_username',
        'db_password',
        'db_name'
    );

    // Check connection
    if ($conn->connect_error) {
        Utils::p("Connection failed: " . $conn->connect_error);
        return null;
    }

    return $conn;
}

$db = dbConnect();
$count = 0;
$users = $db->query("SELECT * FROM users");

foreach ($users as $user) {
    if (!empty($user['email'])) {
        print_r($db->query("INSERT INTO `devices` (`type`, `user_id`, `device_id`, `created`) VALUES (" . 1 . ", " . $user['id'] . ", '" . $user['email'] . "', now())") === true);
    }

    if (!empty($user['android_id'])) {
        print_r($db->query("INSERT INTO `devices` (`type`, `user_id`, `device_id`, `created`) VALUES (" . 2 . ", " . $user['id'] . ", '" . $user['android_id'] . "', now())") === true);
    }

    if (!empty($user['ios_id'])) {
        print_r($db->query("INSERT INTO `devices` (`type`, `user_id`, `device_id`, `created`) VALUES (" . 2 . ", " . $user['id'] . ", '" . $user['ios_id'] . "', now())") === true);
    }
    $count++;
    if (($count % 10) == 0) {
        print_r("Users done:" . $count);
    }
}

print_r("All user devices added to the new 'Devices table', DROPING and CREATING again the 'Users table'.");

// print_r($db->query("UPDATE `users` SET `email/android/ios` = $count WHERE `id` = $id") === true);

$db->close();
