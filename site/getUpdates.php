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

	$last = $_GET["last"];

	$query = $database->prepare(
		"SELECT * FROM
			(SELECT * FROM `uxwba_mission_updates` WHERE `id` > :id) AS `updates`
		LEFT JOIN
			(SELECT * FROM `uxwba_missions` UNION SELECT * FROM `uxwba_missions_deleted`) AS `missions`
		ON `missions`.`id` = `updates`.`mission_id`"
	);
	$query->bindParam(":id", $last);
	$query->execute();

	while (($row = $query->fetch(PDO::FETCH_ASSOC)) !== false) {
		switch ($row["update_type"]) {
		case "added":
			echo("added mission " . $row["name"] . "<br>");
			break;
		case "updated":
			echo("updated mission " . $row["name"] . "<br>");
			break;
		case "moved":
			echo("moved mission " . $row["name"] . " from " . $row["update_data"] . " to " . $row["file_path"] . "<br>");
			break;
		case "deleted":
			echo("deleted mission " . $row["name"] . "<br>");
			break;
		}
	}

} catch (Exception $e) {

}
