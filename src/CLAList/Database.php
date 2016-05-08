<?php

namespace CLAList;

require(BASE_DIR . "/config/db.php");

class Database extends \PDO {

	protected $tablePrefix;

	public function __construct($dbname) {
		//Pass params in here
		parent::__construct(\MBDB::getDSN($dbname), \MBDB::getDatabaseUser($dbname), \MBDB::getDatabasePass($dbname));
		$this->tablePrefix = \MBDB::getDatabasePrefix($dbname);

		if ($this->errorCode()) {
			print_r($this->errorInfo());
		}
	}

	public function convertPathToAbsolute($file) {
		if (strpos($file, BASE_DIR) !== false)
			return $file;
		
		$full = str_replace("~/", "cla-git/", $file);
		$full = BASE_DIR . "/" . $full;
		return $full;
	}

	public function convertPathToRelative($path) {
		if (substr($path, 0, 1) == "~")
			return $path;

		$path = "~/" . str_replace(array(BASE_DIR . "/", "cla-git/", "~/"), "", $path);
		return $path;
	}

	public function prepare($statement, array $driver_options = array()) {
		$statement = str_replace("@_", $this->tablePrefix, $statement);
		return parent::prepare($statement, $driver_options);
	}

	public function getSetting($key) {
		$query = $this->prepare("SELECT `value` FROM `@_settings` WHERE `key` = :key");
		$query->bindParam(":key", $key);
		$query->execute();
		return $query->fetchColumn(0);
	}

	public function setSetting($key, $value) {
		$query = $this->prepare("UPDATE `@_settings` SET `value` = :value WHERE `key` = :key");
		$query->bindParam(":value", $value);
		$query->bindParam(":key", $key);
		$query->execute();
	}

}
