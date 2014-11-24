CREATE DATABASE pushdb;

USE pushdb;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(80) NOT NULL,
  `android_id` int(11) NOT NULL DEFAULT '0',
  `ios_id` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

CREATE TABLE `preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `option` int(1) NOT NULL DEFAULT '1',
  `user_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `type_id` (`type_id`),
  CONSTRAINT `preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `preferences_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `types` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

CREATE TABLE `types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `range` int(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `channel_id` int(11) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `channel_id` (`channel_id`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `subscriptions_ibfk_2` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

CREATE TABLE `channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

CREATE TABLE `apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `secret` varchar(16) NOT NULL,
  `name` varchar(100) NOT NULL,
  `auth` varchar(32) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8

#Show create table subscriptions