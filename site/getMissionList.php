<?php

define("BASE_DIR", dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

use CLAList\Mission\MissionInfo;
use CLAList\Database;

try {
	$database = new Database("cla");

	//Check if the db is updating
	if ($database->getSetting("updating") == "1") {
		//Yes, don't show the list
		die("ERROR Updating the database! Please try again later.\n");
	}

	$query = $database->prepare("
		SELECT `@_missions`.*, `@_mission_interiors`.*, `s`.`full_path`, `s`.`skybox_textures`, `s`.`missing_skybox_textures`
		FROM `@_missions`
		LEFT JOIN `@_mission_interiors` ON (`@_missions`.`id` = `@_mission_interiors`.`mission_id`)
		LEFT JOIN `@_skyboxes` AS `s` ON (`@_missions`.`skybox_id` = `s`.`id`)"
	);
	$query->execute();
	if ($query->rowCount()) {
		$rows = $query->fetchAll(\PDO::FETCH_ASSOC);

		$i = 0;
		echo("START " . count($rows) . "\n");

		foreach ($rows as $row) {
			/* @var array $row */
			$info = MissionInfo::loadMySQLRow($database, $row);
			//Spit out some stuff about the mission

			echo("CLA $i info file " . $info->getFile() . "\n");
			echo("CLA $i info gems " . $info->getGems() . "\n");
			echo("CLA $i info hash " . $info->getHash() . "\n");
			foreach ($info->getFields() as $key => $value) {
				echo("CLA $i info $key $value\n");
			}
			echo("CLA $i info searchName " . strtolower($info->getName()) . "\n");
			echo("CLA $i info searchArtist " . strtolower($info->getArtist()) . "\n");
			echo("CLA $i info searchFile " . strtolower($info->getFile()) . "\n");

			echo("CLA $i info searchModification " . $info->getModification() . "\n");
			echo("CLA $i info searchGameType " . $info->getGameType() . "\n");
			echo("CLA $i info searchGameModes " . strtolower($info->getGameModes()) . "\n");
			echo("CLA $i info searchDifficulty " . $info->getDifficulty() . "\n");
			echo("CLA $i info searchTimestamp " . $info->getTimestamp() . "\n");

			$interiors = json_decode($row["interiors"], true);
			$interiorTextures = json_decode($row["interior_textures"], true);

			foreach ($interiors as $interior) {
				echo("CLA $i interior $interior\n");
			}
			foreach ($interiorTextures as $texture) {
				echo("CLA $i texture $texture\n");
			}

			//Pretend the skybox is textures
			$skyboxTextures = json_decode($row["skybox_textures"], true);
			$skyboxFile     = $database->convertPathToRelative($row["full_path"]);

			echo("CLA $i texture $skyboxFile\n");
			foreach ($skyboxTextures as $texture) {
				echo("CLA $i texture $texture\n");
			}

			$i ++;
		}

		echo("DONE\n");
	}
} catch (Exception $e) {

}
