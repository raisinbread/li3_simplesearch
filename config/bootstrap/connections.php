<?php

use lithium\data\Connections;

/**
 * Uncomment this configuration to use Sqlite3 as your default database.
 */
Connections::add('default', array(
  'type'     => 'database',
  'adapter'  => 'Sqlite3',
  'database' => LITHIUM_APP_PATH . '/libraries/li3_simplesearch/resources/data/simplesearch.db',
));

/**
 * Uncomment this configuration to use MongoDB as your default database.
 */
// Connections::add('default', array(
//   'type' => 'MongoDb',
//   'host' => 'localhost',
//   'database' => 'simplesearch'
// ));

/**
 * Uncomment this configuration to use MySQL as your default database.
 */
// Connections::add('default', array(
// 	'type' => 'database',
// 	'adapter' => 'MySql',
// 	'host' => 'localhost',
// 	'login' => 'root',
// 	'password' => '',
// 	'database' => 'simplesearch'
// ));
?>