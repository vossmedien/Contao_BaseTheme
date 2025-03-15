-- 
-- Tabelle für Stripe-Bestellungen
--
CREATE TABLE IF NOT EXISTS `tl_stripe_orders` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `order_id` varchar(64) NOT NULL default '',
  `session_id` varchar(255) NOT NULL default '',
  `stripe_session_id` varchar(255) NOT NULL default '',
  `element_id` int(10) unsigned NOT NULL default '0',
  `product_data` text NULL,
  `personal_data` text NULL,
  `create_user` tinyint(1) unsigned NOT NULL default '0',
  `member_group` int(10) unsigned NOT NULL default '0',
  `member_id` int(10) unsigned NOT NULL default '0',
  `success_page` int(10) unsigned NOT NULL default '0',
  `admin_email` varchar(255) NOT NULL default '',
  `status` varchar(32) NOT NULL default 'pending',
  `created_at` datetime NOT NULL,
  `paid_at` datetime NULL,
  PRIMARY KEY  (`id`),
  KEY `order_id` (`order_id`),
  KEY `session_id` (`session_id`),
  KEY `stripe_session_id` (`stripe_session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Tabelle für Download-Links
--
CREATE TABLE IF NOT EXISTS `tl_stripe_downloads` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `tstamp` int(10) unsigned NOT NULL default '0',
  `order_id` varchar(64) NOT NULL default '',
  `token` varchar(255) NOT NULL default '',
  `file_path` varchar(255) NOT NULL default '',
  `file_name` varchar(255) NOT NULL default '',
  `expires_in_days` int(10) unsigned NOT NULL default '7',
  `download_limit` int(10) unsigned NOT NULL default '3',
  `download_count` int(10) unsigned NOT NULL default '0',
  `created_at` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Tabelle für temporäre Session-Daten
--
CREATE TABLE IF NOT EXISTS `tl_stripe_session_data` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `session_id` varchar(255) NOT NULL default '',
  `data` longtext NULL,
  `created` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 