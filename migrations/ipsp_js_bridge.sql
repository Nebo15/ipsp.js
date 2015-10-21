/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- База данных: `ipsp_js_bridge`
--

CREATE DATABASE `ipsp_js_bridge`
  DEFAULT CHARACTER SET utf8
  COLLATE utf8_general_ci;
USE `ipsp_js_bridge`;

-- --------------------------------------------------------

--
-- Структура таблицы `logs`
--

CREATE TABLE IF NOT EXISTS `logs` (
  `id`        INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `level`     TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `class`     VARCHAR(255)        NOT NULL,
  `function`  VARCHAR(255)        NOT NULL,
  `file`      VARCHAR(255)        NOT NULL,
  `line`      INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `message`   VARCHAR(255)        NOT NULL,
  `data`      TEXT                NOT NULL,
  `microtime` DOUBLE UNSIGNED     NOT NULL DEFAULT '0',
  `dt_create` DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dt_add`    TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

-- --------------------------------------------------------

--
-- Структура таблицы `public_keys`
--

CREATE TABLE IF NOT EXISTS `public_keys` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `public_key` TEXT,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

-- --------------------------------------------------------

--
-- Структура таблицы `tokens`
--

CREATE TABLE IF NOT EXISTS `tokens` (
  `id`            INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `token`         CHAR(32)                     DEFAULT NULL,
  `public_key_id` INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `jsonPFunction` VARCHAR(255)                 DEFAULT NULL
  COMMENT 'Имя callback-функции для возврата токена клиентскому JS-скрипту',
  `response_url`  VARCHAR(255)                 DEFAULT NULL,
  `payment_id`    INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `payment_state` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  `dt_add`        DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dt_update`     DATETIME            NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  AUTO_INCREMENT = 1;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
