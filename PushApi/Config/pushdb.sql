CREATE DATABASE pushdb;

USE pushdb;

CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `userId` int(11) NOT NULL,
    `email` varchar(80) NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `userId` (`userId`),
  UNIQUE KEY `email` (`email`)
);

CREATE TABLE IF NOT EXISTS `services` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `idUser` int(11) NOT NULL,
    `gcmId` int(11) NOT NULL DEFAULT 0,
    `apnsId` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idUser` (`idUser`)
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
    `idUser` int(11) NOT NULL,
    `idChannel` int(11) NOT NULL,
    `level` int(1) NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `auth` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `secret` varchar(100) NOT NULL,
    `auth` varchar(100) NOT NULL,
    `name` varchar(100) NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
);

ALTER TABLE `services` ADD FOREIGN KEY (`idUser`) REFERENCES `pushdb`.`users` (`id`);
ALTER TABLE `subscribed` ADD FOREIGN KEY (`idUser`) REFERENCES `pushdb`.`users` (`id`);
ALTER TABLE `subscribed` ADD FOREIGN KEY (`idChannel`) REFERENCES `pushdb`.`channels` (`id`);