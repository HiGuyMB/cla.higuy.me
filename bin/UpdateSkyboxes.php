<?php

define("BASE_DIR", dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

use CLAList\Database;
use CLAList\Mission\MissionInfo;

try {
	$database = new Database("cla");
	$database->setSetting("updating", "1");
	$query = $database->prepare("SELECT `id`, `file_path` FROM `@_missions` WHERE `skybox_id` = 0");
	$query->execute();
	$missions = $query->fetchAll(PDO::FETCH_ASSOC);

	foreach ($missions as $row) {
		$full = $database->convertPathToAbsolute($row["file_path"]);
		$mission = MissionInfo::loadFile($database, $full);

		echo("Update skybox for " . $mission->getName() . " skybox id is " . $mission->getSkybox()->getId() . "\n");

		$query = $database->prepare("UPDATE `@_missions` SET `skybox_id` = :skybox WHERE `id` = :id");
		$query->bindParam(":skybox", $mission->getSkybox()->getId());
		$query->bindParam(":id", $row["id"]);
		$query->execute();
	}

	$database->setSetting("updating", "0");
} catch (Exception $e) {

}
