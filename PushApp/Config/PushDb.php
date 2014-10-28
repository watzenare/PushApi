<?php

namespace PushApp\Config;

$db = array();
$db['name'] = 'CREATE DATABASE push_db;';
$db['use'] = 'USE push_db;';
$db['t_users'] = 'CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `user_token` varchar(32) NOT NULL,
    `password` varchar(32) NOT NULL,
    `email` varchar(80) NOT NULL,
    `status` int(1) NOT NULL DEFAULT 1,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
);';

$db['t_services'] = 'CREATE TABLE IF NOT EXISTS `services` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_user` int(11) NOT NULL,
    `gcm_id` int(11) NOT NULL DEFAULT 0,
    `apns_id` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `id_user` (`id_user`)
);';

$db['t_channels'] = 'CREATE TABLE IF NOT EXISTS `channels` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `level` int(1) NOT NULL DEFAULT 0,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);';

$db['t_subscribed'] = 'CREATE TABLE IF NOT EXISTS `subscribed` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_user` int(11) NOT NULL,
    `id_channel` int(11) NOT NULL,
    `level` int(1) NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);';

$db['fk_services'] = 'ALTER TABLE `services` ADD FOREIGN KEY (`id_user`) REFERENCES `push_db`.`users` (`id`);';
$db['fk_subscribed_idu'] = 'ALTER TABLE `subscribed` ADD FOREIGN KEY (`id_user`) REFERENCES `push_db`.`users` (`id`);';
$db['fk_subscribed_idc'] = 'ALTER TABLE `subscribed` ADD FOREIGN KEY (`id_channel`) REFERENCES `push_db`.`channels` (`id`);';