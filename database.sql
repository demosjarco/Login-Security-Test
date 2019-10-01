-- DROP SCHEMA IF EXISTS webTest;
create SCHEMA IF NOT EXISTS webTest DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;

DROP USER IF EXISTS 'weblogin'@'localhost';
CREATE USER IF NOT EXISTS 'weblogin'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
GRANT select,insert,update,delete ON webTest.accounts TO 'weblogin'@'localhost';
GRANT select,insert,update,delete ON webTest.login_attempts TO 'weblogin'@'localhost';
GRANT select,insert,update,delete ON webTest.login_resets TO 'weblogin'@'localhost';
GRANT select,insert,update,delete ON webTest.pending_accounts TO 'weblogin'@'localhost';
GRANT select,insert,update ON webTest.sessions TO 'weblogin'@'localhost';

DROP USER IF EXISTS 'webchat'@'localhost';
CREATE USER IF NOT EXISTS 'webchat'@'localhost' IDENTIFIED WITH mysql_native_password BY '';
GRANT select ON webTest.accounts TO 'webchat'@'localhost';
GRANT select,insert,update,delete ON webTest.chat_messages TO 'webchat'@'localhost';
GRANT select,insert,update ON webTest.sessions TO 'webchat'@'localhost';

FLUSH PRIVILEGES;

-- ---
-- Globals
-- ---

-- SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
-- SET FOREIGN_KEY_CHECKS=0;

-- ---
-- Table 'accounts'
-- 
-- ---

DROP TABLE IF EXISTS `accounts`;
		
CREATE TABLE `accounts` (
  `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `locked` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'login_attempts'
-- 
-- ---

DROP TABLE IF EXISTS `login_attempts`;
		
CREATE TABLE `login_attempts` (
  `user_id` TINYINT UNSIGNED NOT NULL,
  `time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(255) NOT NULL,
  UNIQUE KEY (`user_id`, `time`, `ip_address`)
);

-- ---
-- Table 'login_resets'
-- 
-- ---

DROP TABLE IF EXISTS `login_resets`;
		
CREATE TABLE `login_resets` (
  `user_id` TINYINT UNSIGNED NOT NULL,
  `reset_TOken` VARCHAR(255) NOT NULL,
  `date_requested` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
);

-- ---
-- Table 'pending_accounts'
-- 
-- ---

DROP TABLE IF EXISTS `pending_accounts`;
		
CREATE TABLE `pending_accounts` (
  `username` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `verify_TOken` VARCHAR(255) UNIQUE NOT NULL,
  `date_requested` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---
-- Table 'chat_messages'
-- 
-- ---

DROP TABLE IF EXISTS `chat_messages`;
		
CREATE TABLE `chat_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` TINYINT UNSIGNED NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- ---
-- Table 'sessions'
-- 
-- ---

DROP TABLE IF EXISTS `sessions`;
		
CREATE TABLE `sessions` (
  `sessionId` VARCHAR(242) UNIQUE NOT NULL,
  `user_id` TINYINT UNSIGNED UNIQUE NOT NULL,
  `lastTouch` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` VARCHAR(255) NOT NULL
);

-- ---
-- Foreign Keys 
-- ---

ALTER TABLE `login_attempts` ADD FOREIGN KEY (user_id) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `login_resets` ADD FOREIGN KEY (user_id) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `chat_messages` ADD FOREIGN KEY (user_id) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `sessions` ADD FOREIGN KEY (user_id) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

DROP EVENT IF EXISTS clearPendingAccounts;
CREATE EVENT IF NOT EXISTS clearPendingAccounts ON SCHEDULE EVERY 5 MINUTE STARTS '2019-01-01 00:00:00' DO
	DELETE FROM pending_accounts WHERE date_requested < DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY);

DROP EVENT IF EXISTS clearPasswordReset;
CREATE EVENT IF NOT EXISTS clearPasswordReset ON SCHEDULE EVERY 5 MINUTE STARTS '2019-01-01 00:00:00' DO
	DELETE FROM login_resets WHERE date_requested < DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 DAY);

DROP EVENT IF EXISTS clearBruteforce;
CREATE EVENT IF NOT EXISTS clearBruteforce ON SCHEDULE EVERY 5 MINUTE STARTS '2019-01-01 00:00:00' DO
	DELETE FROM login_attempts WHERE `time` < DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 HOUR);
	
DROP EVENT IF EXISTS closeSessions;
CREATE EVENT IF NOT EXISTS closeSessions ON SCHEDULE EVERY 5 MINUTE STARTS '2019-01-01 00:00:00' DO
	DELETE FROM closeSessions WHERE lastTouch < DATE_SUB(CURRENT_TIMESTAMP(), INTERVAL 1 HOUR);