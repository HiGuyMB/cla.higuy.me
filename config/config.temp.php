<?php

defined("MBDBRUN") or die();

$dbConfig = [
	'driver'   => 'pdo_mysql',
	'user'     => 'user',
	'password' => 'pass',
	'dbname'   => 'data',
	'unix_socket' => '/opt/local/var/run/mysql57/mysqld.sock'
];

$pathConfig = [
	'content' => BASE_DIR . '/cla-data',
	'utils' => BASE_DIR . '/util',
	'official' => '/path/to/pq/platinum'
];

