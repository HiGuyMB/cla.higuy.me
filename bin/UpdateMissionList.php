<?php

define("BASE_DIR", dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

use CLAList\Database;
use CLAList\\MissionInfo;

date_default_timezone_set("UTC");

function iterate($dirName, array &$list) {
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
			iterate($fullPath . "/", $list);
		} else if (is_file($fullPath)) {
			$extension = pathinfo($entry, PATHINFO_EXTENSION);

			if ($extension === "mis") {
				$hash = hash("sha256", file_get_contents($fullPath));
				$list[$fullPath] = $hash;
			}
		}
	}

	closedir($dir);
	return true;
}

function findFile($directory, $hash, $extension) {
	$dir = opendir($directory);
	if (!$dir) {
		return false;
	}

	while (($entry = readdir($dir)) !== FALSE) {
		if ($entry === "." || $entry === "..")
			continue;

		$fullPath = $directory . "/" . $entry;
		if (is_dir($fullPath)) {
			$subsearch = findFile($fullPath, $hash, $extension);
			if ($subsearch !== false) {
				closedir($dir);
				return $subsearch;
			}
		} else if (is_file($fullPath)) {
			$testExtension = pathinfo($entry, PATHINFO_EXTENSION);

			if ($testExtension === $extension) {
				$testHash =  hash("sha256", file_get_contents($fullPath));
				if ($testHash === $hash) {
					closedir($dir);
					return $fullPath;
				}
			}
		}
	}

	closedir($dir);
	return false;
}

try {
	$database = new Database("cla");
	$database->setSetting("updating", "1");

	//First, check all missions for anything that has been deleted
	$query = $database->prepare("
		SELECT `@_missions`.*, `@_mission_interiors`.*, `s`.`full_path`, `s`.`skybox_textures`, `s`.`missing_skybox_textures`
		FROM `@_missions`
		LEFT JOIN `@_mission_interiors` ON (`@_missions`.`id` = `@_mission_interiors`.`mission_id`)
		LEFT JOIN `@_skyboxes` AS `s` ON (`@_missions`.`skybox_id` = `s`.`id`)"
	);
	$query->execute();

	$files = array();

	//See if we have any new missions
	$missions = array();
	if (iterate("cla-git/data/", $missions)) {
		echo("Found " . count($missions) . " missions\n");

		//Go through them all
		while (($row = $query->fetch(PDO::FETCH_ASSOC)) !== FALSE) {
			$mission = MissionInfo::loadMySQLRow($database, $row);
			$full = $database->convertPathToAbsolute($mission->getFile());

			if (is_file($full)) {
				$files[] = $full;

				//Check if it's had an update
				$hash = hash("sha256", file_get_contents($full));
				if ($hash !== $mission->getHash()) {
					echo("Mission: " . $mission->getName() . " update hash: " . $mission->getHash() . " -> $hash\n");

					//Yes it has
					$mission->setHash($hash);
				}
			} else {
				echo("Missing mission: " . $mission->getFile() . "\n");

				//Try to find it elsewhere

				$find = array_search($mission->getHash(), $missions);

				if ($find === false) {
					//Delete it
					echo("Couldn't find it.\n");
					$mission->deleteFromDatabase();
				} else {
					//Update the mission
					echo("Found it at {$find}\n");
					$mission->setFile($database->convertPathToRelative($find));

					$files[] = $database->convertPathToAbsolute($find);
				}
			}
		}

		//See if any are new
		foreach ($missions as $full => $hash) {
			$full = $database->convertPathToAbsolute($full);
			//Check if it's in the found files
			if (!in_array($full, $files)) {
				echo("Found new mission: $full\n");

				$info = MissionInfo::loadFile($database, $full);
				$info->addToDatabase();
				echo("Added mission " . $info->getName() . "\n");
			}
		}
	}

	$database->setSetting("updating", "0");

} catch (Exception $e) {
}
