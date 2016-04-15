<?php

define("BASE_DIR", dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

use CLAList\Mission\Info;
use CLAList\Database;

//Let them know that we're a zip file and to download
header("Content-Type: application/text");
header("Content-Disposition: attachment; filename=cla.txt");

try {
	$database = new Database("cla");

	//Check if the db is updating
	if ($database->getSetting("updating") == "1") {
		//Yes, don't show the list
		die("ERROR Updating the database! Please try again later.\n");
	}

	$query = $database->prepare("SELECT * FROM `@_missions` LEFT JOIN `@_mission_interiors` ON (`@_missions`.`id` = `@_mission_interiors`.`mission_id`)");
	$query->execute();
	if ($query->rowCount()) {
		$rows = $query->fetchAll(\PDO::FETCH_ASSOC);

		$i = 0;
		echo("START " . count($rows) . "\n");

		foreach ($rows as $row) {
			/* @var array $row */
			$info = Info::loadMySQLRow($database, $row);
			//Spit out some stuff about the mission

			echo("CLA $i info file " . $info->getFile()  . "\n");
			echo("CLA $i info gems " . $info->getGems()  . "\n");
			echo("CLA $i info hash " . $info->getHash() . "\n");
			foreach ($info->getFields() as $key => $value) {
				echo("CLA $i info $key $value\n");
			}
			echo("CLA $i info searchName " . strtolower($info->getName()) . "\n");
			echo("CLA $i info searchArtist " . strtolower($info->getArtist()) . "\n");
			echo("CLA $i info searchFile " . strtolower($info->getFile()) . "\n");

			$interiors = json_decode($row["interiors"], true);
			$textures = json_decode($row["textures"], true);

			foreach ($interiors as $interior) {
				echo("CLA $i interior $interior\n");
			}
			foreach ($textures as $texture) {
				echo("CLA $i texture $texture\n");
			}

			$i ++;
		}

		echo("DONE\n");
	}
} catch (Exception $e) {

}
