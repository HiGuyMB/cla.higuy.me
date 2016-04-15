<?php

//Catch any errors
if (defined("MBDBRUN")) {
	die("Already ran\n");
}
define("MBDBRUN", 1);

/**
 * Master database control class
 */
class MBDB {
	static $databases = null;

	/**
	 * Get the connection type for the specified database
	 * @param string $db The database's identifier
	 * @return string The database's type
	 */
	static function getDatabaseType($db) {
		return self::$databases[$db]["type"];
	}

	/**
	 * Get the hostname for the specified database
	 * @param string $db The database's identifier
	 * @return string The database's hostname
	 */
	static function getDatabaseHost($db) {
		return self::$databases[$db]["host"];
	}

	/**
	 * Get the database name for the specified database
	 * @param string $db The database's identifier
	 * @return string The database's name
	 */
	static function getDatabaseName($db) {
		return self::$databases[$db]["data"];
	}

	/**
	 * Get the username to access the specified database
	 * @param string $db The database's identifier
	 * @return string The username to access the database
	 */
	static function getDatabaseUser($db) {
		return self::$databases[$db]["user"];
	}

	/**
	 * Get the password to access the specified database
	 * @param string $db The database's identifier
	 * @return string The password to access the database
	 */
	static function getDatabasePass($db) {
		return self::$databases[$db]["pass"];
	}

	/**
	 * Get the table prefix for the specified database
	 * @param string $db The database's identifier
	 * @return string The table prefix used by the database
	 */
	static function getDatabasePrefix($db) {
		return self::$databases[$db]["prefix"];
	}

	static function getDSN($db) {
		return self::getDatabaseType($db) . ":dbname=" . self::getDatabaseName($db) . ";host=" . self::getDatabaseHost($db);
	}

	/**
	 * Add a database to the class's list of databases
	 * @param string $ident The database's identifier
	 * @param string $type  The type of database
	 * @param string $host  The host for the database
	 * @param string $data  The database name for the database
	 * @param string $user  The username to access the database
	 * @param string $pass  The password to access the database
	 * @param string $prefix The table prefix used by the database
	 */
	static function addDatabase($ident = null, $type = null, $host = null, $data = null, $user = null, $pass = null, $prefix = null) {
		if ($ident === null ||
		    $type === null ||
		    $host === null ||
		    $data === null ||
		    $user === null ||
		    $pass === null ||
		    $prefix === null)
			return;
		
		if (self::$databases == null) {
			self::$databases = array();
		}

		self::$databases[$ident] = array("host" => $host,
		                                 "type" => $type,
		                                 "data" => $data,
		                                 "user" => $user,
		                                 "pass" => $pass,
		                                 "prefix" => $prefix);
	}
}

//Database config
include("config.php");
