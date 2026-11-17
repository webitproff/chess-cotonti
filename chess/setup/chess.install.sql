-- File: chess.install.sql
-- Path: plugins/chess/setup/chess.install.sql

CREATE TABLE IF NOT EXISTS `cot_chess_games` (
  `game_id` INT(11) NOT NULL AUTO_INCREMENT,
  `white_id` INT(11) DEFAULT NULL,
  `black_id` INT(11) DEFAULT NULL,
  `is_ai` TINYINT(1) NOT NULL DEFAULT 0,
  `ai_level` VARCHAR(8) NOT NULL DEFAULT '',
  `fen` VARCHAR(120) NOT NULL,
  `hist` MEDIUMTEXT NOT NULL,
  `moves` MEDIUMTEXT NOT NULL,
  `status` ENUM('waiting','active','finished') NOT NULL DEFAULT 'waiting',
  `result` VARCHAR(8) NOT NULL DEFAULT '',
  `finish_reason` VARCHAR(16) NOT NULL DEFAULT '',
  `winner_id` INT(11) DEFAULT NULL,
  `tc_base` INT(11) NOT NULL DEFAULT 0,
  `tc_inc` INT(11) NOT NULL DEFAULT 0,
  `clock_w` BIGINT NOT NULL DEFAULT 0,
  `clock_b` BIGINT NOT NULL DEFAULT 0,
  `clock_ts` BIGINT NOT NULL DEFAULT 0,
  `draw_offer` TINYINT(4) NOT NULL DEFAULT 0,
  `rev` INT(11) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`game_id`),
  KEY `status` (`status`),
  KEY `white` (`white_id`),
  KEY `black` (`black_id`),
  KEY `updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cot_chess_stats` (
  `user_id` INT(11) NOT NULL,
  `wins` INT(11) NOT NULL DEFAULT 0,
  `losses` INT(11) NOT NULL DEFAULT 0,
  `draws` INT(11) NOT NULL DEFAULT 0,
  `ai_wins` INT(11) NOT NULL DEFAULT 0,
  `ai_losses` INT(11) NOT NULL DEFAULT 0,
  `ai_draws` INT(11) NOT NULL DEFAULT 0,
  `rating` INT(11) NOT NULL DEFAULT 1000,
  PRIMARY KEY (`user_id`),
  KEY `rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cot_chess_chat` (
  `msg_id` INT(11) NOT NULL AUTO_INCREMENT,
  `game_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `message` VARCHAR(255) NOT NULL,
  `created_ts` INT(11) NOT NULL,
  PRIMARY KEY (`msg_id`),
  KEY `game_msg` (`game_id`, `msg_id`),
  KEY `user_ts` (`user_id`, `created_ts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
