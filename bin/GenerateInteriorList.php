<?php

define("BASE_DIR", dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

use CLAList\Database;
use CLAList\Mission\Interior;

function iterate(Database $database, $dirName, array &$list) {
	$dir = opendir($dirName);
	if (!$dir) {
		return false;
	}
	echo("Iterate dir " . $dirName . "\n");

	while (($entry = readdir($dir)) !== FALSE) {
		if ($entry === "." || $entry === "..")
			continue;

		$fullPath = $dirName . $entry;
		if (is_dir($fullPath)) {
			iterate($database, $fullPath . "/", $list);
		} else if (is_file($fullPath)) {
			$extension = pathinfo($entry, PATHINFO_EXTENSION);

			if ($extension === "dif") {
				$fullPath = $database->convertPathToAbsolute($fullPath);
				$info = new Interior($database, $fullPath);
				$list[] = $info;
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
		$query = $database->prepare("TRUNCATE TABLE `@_interiors`");
		$query->execute();
		$query = $database->prepare("TRUNCATE TABLE `@_textures`");
		$query->execute();
	$database->prepare("SET FOREIGN_KEY_CHECKS = 1")->execute();

	$interiors = array();
	if (iterate($database, "cla-git/data/", $interiors)) {
		echo("Found " . count($interiors) . " interiors.\n");
		echo("Database time\n");

		foreach ($interiors as $info) {
			/* @var CLAList\Mission\Interior $info */

			$query = $database->prepare("INSERT INTO `@_interiors` SET `file_path` = :file, `full_path` = :full, `interior_textures` = :textures, `missing_interior_textures` = :missingTextures");
			$query->bindParam(":file",            $info->getFile());
			$query->bindParam(":full",            $info->getFull());
			$query->bindParam(":textures",        json_encode($info->getTextures()));
			$query->bindParam(":missingTextures", $info->getMissingTextures());
			$query->execute();

			//What ID did we just insert
			$id = $database->lastInsertId();

			$textures = $info->getTextures();
			foreach ($textures as $texture) {
				$full = str_replace("~/", "cla-git/", $texture);
				$full = dirname(__DIR__) . "/" . $full;

				//Check if it exists
				$query = $database->prepare("SELECT COUNT(*) FROM `@_textures` WHERE `full_path` = :full");
				$query->bindParam(":full", $full);
				$query->execute();

				$count = $query->fetchColumn(0);
				if ($count == 0) {
					$query = $database->prepare("INSERT INTO `@_textures` SET `file_path` = :file, `full_path` = :full");
					$query->bindParam(":file", $texture);
					$query->bindParam(":full", $full);
					$query->execute();
				}
			}

			echo(count($textures) . " textures for " . $info->getFile() . "\n");
		}
	}

	$database->setSetting("updating", "0");
} catch (Exception $e) {

}
