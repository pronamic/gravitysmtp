SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `wp_gravitycrm_address`;
CREATE TABLE `wp_gravitycrm_address` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `lineOne` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `lineTwo` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `state` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `postalCode` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `isPrimary` tinyint(1) NOT NULL DEFAULT '0',
  `contactId` mediumint DEFAULT NULL,
  `companyId` mediumint DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `lineOne` (`lineOne`),
  FULLTEXT KEY `lineTwo` (`lineTwo`),
  FULLTEXT KEY `city` (`city`),
  FULLTEXT KEY `state` (`state`),
  FULLTEXT KEY `country` (`country`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `wp_gravitycrm_address` (`id`, `type`, `lineOne`, `lineTwo`, `city`, `state`, `country`, `postalCode`, `isPrimary`, `contactId`, `companyId`, `dateCreated`, `dateUpdated`) VALUES
(1,	'',	'123 Street St',	'',	'City',	'NY',	'US',	'55555',	0,	NULL,	1,	'2025-10-02 19:01:17',	'2025-10-02 19:01:17'),
(2,	'',	'123 B Street',	'',	'City',	'NY',	'US',	'55555',	0,	NULL,	2,	'2025-10-02 19:01:36',	'2025-10-02 19:01:36');

DROP TABLE IF EXISTS `wp_gravitycrm_company`;
CREATE TABLE `wp_gravitycrm_company` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `companyName` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `companyLogo` mediumint DEFAULT NULL,
  `companyBanner` mediumint DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `companyName` (`companyName`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `wp_gravitycrm_company` (`id`, `companyName`, `companyLogo`, `companyBanner`, `description`, `source`, `dateCreated`, `dateUpdated`) VALUES
(1,	'Company A',	0,	NULL,	'',	NULL,	'2025-10-02 18:58:25',	'2025-10-02 18:58:25'),
(2,	'Company B',	0,	NULL,	'',	NULL,	'2025-10-02 18:59:12',	'2025-10-02 18:59:12');

DROP TABLE IF EXISTS `wp_gravitycrm_company_contact`;
CREATE TABLE `wp_gravitycrm_company_contact` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `company_id` mediumint NOT NULL,
  `contact_id` mediumint NOT NULL,
  `isMain` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `wp_gravitycrm_company_contact` (`id`, `company_id`, `contact_id`, `isMain`) VALUES
(1,	1,	1,	0),
(2,	1,	2,	0),
(3,	2,	3,	0);

DROP TABLE IF EXISTS `wp_gravitycrm_contact`;
CREATE TABLE `wp_gravitycrm_contact` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `firstName` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `lastName` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `jobTitle` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `contactAvatar` mediumint DEFAULT NULL,
  `contactBanner` mediumint DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `firstName` (`firstName`),
  FULLTEXT KEY `lastName` (`lastName`),
  FULLTEXT KEY `jobTitle` (`jobTitle`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `wp_gravitycrm_contact` (`id`, `firstName`, `lastName`, `jobTitle`, `description`, `contactAvatar`, `contactBanner`, `source`, `dateCreated`, `dateUpdated`) VALUES
(1,	'Jane',	'Smith',	'',	'',	0,	NULL,	NULL,	'2025-10-02 18:59:50',	'2025-10-02 18:59:50'),
(2,	'John',	'Smith',	'',	'',	0,	NULL,	NULL,	'2025-10-02 19:00:12',	'2025-10-02 19:00:12'),
(3,	'Rob',	'Carlin',	'',	'',	0,	NULL,	NULL,	'2025-10-02 19:00:41',	'2025-10-02 19:00:41');

DROP TABLE IF EXISTS `wp_gravitycrm_deal`;
CREATE TABLE `wp_gravitycrm_deal` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `value` bigint NOT NULL,
  `estimatedCloseDate` bigint DEFAULT NULL,
  `notes` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `attachments` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `previousNeighbor` mediumint DEFAULT '0',
  `pipelineId` mediumint DEFAULT NULL,
  `stageId` mediumint DEFAULT NULL,
  `userId` mediumint DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `label` (`label`),
  FULLTEXT KEY `notes` (`notes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `wp_gravitycrm_deal_company`;
CREATE TABLE `wp_gravitycrm_deal_company` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `deal_id` mediumint NOT NULL,
  `company_id` mediumint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `wp_gravitycrm_deal_contact`;
CREATE TABLE `wp_gravitycrm_deal_contact` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `deal_id` mediumint NOT NULL,
  `contact_id` mediumint NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `wp_gravitycrm_email`;
CREATE TABLE `wp_gravitycrm_email` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `address` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `isPrimary` tinyint(1) NOT NULL DEFAULT '0',
  `contactId` mediumint DEFAULT NULL,
  `companyId` mediumint DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `address` (`address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `wp_gravitycrm_email` (`id`, `type`, `address`, `isPrimary`, `contactId`, `companyId`, `dateCreated`, `dateUpdated`) VALUES
(1,	'',	'support@companya.local',	0,	NULL,	1,	'2025-10-02 18:58:25',	'2025-10-02 18:58:25'),
(2,	'',	'feedback@companya.local',	0,	NULL,	1,	'2025-10-02 18:58:25',	'2025-10-02 18:58:25'),
(3,	'',	'foo@companyb.local',	0,	NULL,	2,	'2025-10-02 18:59:12',	'2025-10-02 18:59:12'),
(4,	'',	'bar@companyb.local',	0,	NULL,	2,	'2025-10-02 18:59:12',	'2025-10-02 18:59:12'),
(5,	'',	'jane@companya.local',	0,	1,	NULL,	'2025-10-02 18:59:50',	'2025-10-02 18:59:50'),
(6,	'',	'john@companya.local',	0,	2,	NULL,	'2025-10-02 19:00:12',	'2025-10-02 19:00:12'),
(7,	'',	'rob@companyb.local',	0,	3,	NULL,	'2025-10-02 19:00:41',	'2025-10-02 19:00:41');

DROP TABLE IF EXISTS `wp_gravitycrm_file`;
CREATE TABLE `wp_gravitycrm_file` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `path` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `fileName` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `extension` tinytext COLLATE utf8mb4_unicode_520_ci,
  `size` int DEFAULT NULL,
  `dealId` mediumint DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `wp_gravitycrm_meta`;
CREATE TABLE `wp_gravitycrm_meta` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `object_type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `object_id` mediumint NOT NULL,
  `meta_name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `meta_value` mediumtext COLLATE utf8mb4_unicode_520_ci NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `meta_value` (`meta_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `wp_gravitycrm_note`;
CREATE TABLE `wp_gravitycrm_note` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `contents` mediumtext COLLATE utf8mb4_unicode_520_ci,
  `userId` mediumint DEFAULT NULL,
  `dealId` mediumint DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `contents` (`contents`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `wp_gravitycrm_phone`;
CREATE TABLE `wp_gravitycrm_phone` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `number` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `countryCode` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `isPrimary` tinyint(1) NOT NULL DEFAULT '0',
  `contactId` mediumint DEFAULT NULL,
  `companyId` mediumint DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `number` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `wp_gravitycrm_phone` (`id`, `type`, `number`, `countryCode`, `isPrimary`, `contactId`, `companyId`, `dateCreated`, `dateUpdated`) VALUES
(1,	'',	'14445556666',	'US',	0,	NULL,	1,	'2025-10-02 18:58:25',	'2025-10-02 18:58:25'),
(2,	'',	'12223334444',	'US',	0,	NULL,	1,	'2025-10-02 18:58:25',	'2025-10-02 18:58:25'),
(3,	'',	'18887776666',	'US',	0,	NULL,	2,	'2025-10-02 18:59:12',	'2025-10-02 18:59:12'),
(4,	'',	'19998887777',	'US',	0,	NULL,	2,	'2025-10-02 18:59:12',	'2025-10-02 18:59:12'),
(5,	'',	'19998887777',	'US',	0,	1,	NULL,	'2025-10-02 18:59:50',	'2025-10-02 18:59:50'),
(6,	'',	'18887779797',	'US',	0,	2,	NULL,	'2025-10-02 19:00:12',	'2025-10-02 19:00:12'),
(7,	'',	'17776668686',	'US',	0,	3,	NULL,	'2025-10-02 19:00:41',	'2025-10-02 19:00:41');

DROP TABLE IF EXISTS `wp_gravitycrm_pipeline`;
CREATE TABLE `wp_gravitycrm_pipeline` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `wp_gravitycrm_social`;
CREATE TABLE `wp_gravitycrm_social` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `platform` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `identifier` varchar(300) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `isPrimary` tinyint(1) NOT NULL DEFAULT '0',
  `contactId` mediumint DEFAULT NULL,
  `companyId` mediumint DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `identifier` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `wp_gravitycrm_social` (`id`, `platform`, `identifier`, `isPrimary`, `contactId`, `companyId`, `dateCreated`, `dateUpdated`) VALUES
(1,	'instagram',	'companyb',	0,	NULL,	2,	'2025-10-02 19:01:48',	'2025-10-02 19:01:48'),
(2,	'instagram',	'companya',	0,	NULL,	1,	'2025-10-02 19:02:02',	'2025-10-02 19:02:02');

DROP TABLE IF EXISTS `wp_gravitycrm_stage`;
CREATE TABLE `wp_gravitycrm_stage` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `label` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `labelStyle` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `pipelineId` mediumint DEFAULT NULL,
  `previousNeighbor` mediumint DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;


DROP TABLE IF EXISTS `wp_gravitycrm_website`;
CREATE TABLE `wp_gravitycrm_website` (
  `id` mediumint NOT NULL AUTO_INCREMENT,
  `type` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `url` varchar(300) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `isPrimary` tinyint(1) NOT NULL DEFAULT '0',
  `contactId` mediumint DEFAULT NULL,
  `companyId` mediumint DEFAULT NULL,
  `dateCreated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateUpdated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

INSERT INTO `wp_gravitycrm_website` (`id`, `type`, `url`, `isPrimary`, `contactId`, `companyId`, `dateCreated`, `dateUpdated`) VALUES
(1,	'',	'https://companya.local',	0,	NULL,	1,	'2025-10-02 18:58:25',	'2025-10-02 18:58:25'),
(2,	'',	'https://companyb.local',	0,	NULL,	2,	'2025-10-02 18:59:12',	'2025-10-02 18:59:12');

-- 2025-10-02 19:05:09 UTC
