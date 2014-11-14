CREATE DATABASE pushdb;

USE pushdb;

CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(80) NOT NULL,
    `android_id` int(11) NOT NULL DEFAULT 0,
    `ios_id` int(11) NOT NULL DEFAULT 0,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
);

CREATE TABLE IF NOT EXISTS `channels` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `level` int(1) NOT NULL DEFAULT 0,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `subscribed` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `channel_id` int(11) NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `apps` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `secret` varchar(100) NOT NULL,
    `auth` varchar(100) NOT NULL,
    `name` varchar(100) NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
);

CREATE TABLE IF NOT EXISTS `queue` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `action` varchar(100) NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
);

ALTER TABLE `subscribed` ADD FOREIGN KEY (`user_id`) REFERENCES `pushdb`.`users` (`id`);
ALTER TABLE `subscribed` ADD FOREIGN KEY (`channel_id`) REFERENCES `pushdb`.`channels` (`id`);