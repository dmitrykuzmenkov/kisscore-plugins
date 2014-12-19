CREATE TABLE `selection_example` (
  `key` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `pos` int(11) unsigned NOT NULL DEFAULT '0',
  `title` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`key`),
  KEY `sort` (`lang`,`pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
