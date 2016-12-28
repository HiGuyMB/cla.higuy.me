<?php

define("BASE_DIR", dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

use CLAList\Database;
use CLAList\\MissionInfo;

try {
	$database = new Database("cla");
	$database->setSetting("updating", "1");

	$query = $database->prepare("SELECT `id`, `file_path` FROM `@_missions`");
	$query->execute();
	$missions = $query->fetchAll(PDO::FETCH_ASSOC);

	foreach ($missions as $row) {
		$full = $database->convertPathToAbsolute($row["file_path"]);
		$mission = MissionInfo::loadFile($database, $full);

		echo("Guess modification " . $mission->getModification() . " for mission " . $mission->getName() . "\n");

		$query = $database->prepare("UPDATE `@_missions` SET `modification` = :modification, `game_type` = :type, `game_modes` = :modes, `fields` = :fields WHERE `id` = :id");
		$query->bindParam(":modification", $mission->getModification());
		$query->bindParam(":type", $mission->getGameType());
		$query->bindParam(":modes", $mission->getGameModes());
		$query->bindParam(":fields", json_encode($mission->getFields()));
		$query->bindParam(":id", $row["id"]);
		$query->execute();
	}

	$database->setSetting("updating", "0");
} catch (Exception $e) {

}
