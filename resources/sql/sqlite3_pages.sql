CREATE TABLE  `pages` (
`id` INTEGER NOT NULL PRIMARY KEY ,
`created` DATETIME NOT NULL ,
`modified` DATETIME NOT NULL ,
`url` VARCHAR( 255 ) NOT NULL ,
`content` TEXT NOT NULL
);

CREATE TABLE `terms` (
`id` INTEGER NOT NULL PRIMARY KEY ,
`created` DATETIME NOT NULL ,
`modified` DATETIME NOT NULL ,
`term` VARCHAR( 255 ) NOT NULL ,
`page_id` INTEGER NOT NULL ,
`base` REAL NOT NULL ,
`normalization` REAL NOT NULL
);