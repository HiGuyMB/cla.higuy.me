<?php

define("BASE_DIR", dirname(__DIR__));

require BASE_DIR . '/vendor/autoload.php';

use CLAList\Mission\MissionInfo;
use CLAList\Database;
use CLAList\Mission\Skybox;

function iterate(Database $database, $dirName) {
	$dir = opendir($dirName);
	if (!$dir) {
		return false;
	}
//	echo("Iterate dir " . $dirName . "\n");

	while (($entry = readdir($dir)) !== FALSE) {
		if ($entry === "." || $entry === "..")
			continue;

		$fullPath = $dirName . $entry;
		if (is_dir($fullPath)) {
			iterate($database, $fullPath . "/");
		} else if (is_file($fullPath)) {
			$extension = pathinfo($entry, PATHINFO_EXTENSION);

			if ($extension === "dml") {
				$info = new Skybox($database, $database->convertPathToAbsolute($fullPath));
				$info->addToDatabase();
			}
		}
	}

	closedir($dir);
	return true;
}

try {
	$database = new Database("cla");
	$database->setSetting("updating", "1");

	//Dump the database
	$database->prepare("SET FOREIGN_KEY_CHECKS = 0")->execute();
		$database->prepare("TRUNCATE TABLE `@_skyboxes`")->execute();
	$database->prepare("SET FOREIGN_KEY_CHECKS = 1")->execute();

	iterate($database, "cla-git/data/");

	$database->setSetting("updating", "0");
} catch (Exception $e) {

}
