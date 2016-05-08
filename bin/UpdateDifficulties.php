<?php

define("BASE_DIR", dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

use CLAList\Database;

$difficultyMap = array(
	0 => "very easy",
	1 => "easy",
	2 => "easy-medium",
	3 => "medium",
	4 => "medium-hard",
	5 => "hard",
	6 => "very hard",
	7 => "impossible",
	8 => "unknown",
);

try {
	$database = new Database("cla");
	$database->setSetting("updating", "1");

	$query = $database->prepare("SELECT `id`, `file_path` FROM `@_missions`");
	$query->execute();
	$missions = $query->fetchAll(PDO::FETCH_ASSOC);

	foreach ($missions as $row) {
		$basename = pathinfo($row["file_path"], PATHINFO_BASENAME);
		$filename = pathinfo($row["file_path"], PATHINFO_FILENAME);

//		echo($filename . "\n");

		$query = $database->prepare("SELECT `difficulty` FROM `@_mission_ratings_raw` WHERE `mission_file` = :basename OR `mission_file` = :filename");
		$query->bindParam(":basename", $basename);
		$query->bindParam(":filename", $filename);
		$query->execute();

		if (!$query->rowCount()) {
			echo("Couldn't find difficulty rating for " . $basename . " / " . $filename . "\n");
			continue;
		}

		$difficulty = $query->fetchColumn(0);

		$query = $database->prepare("UPDATE `@_missions` SET `difficulty` = :difficulty WHERE `id` = :id");
		$query->bindParam(":difficulty", $difficultyMap[$difficulty]);
		$query->bindParam(":id", $row["id"]);
		$query->execute();
	}

	$database->setSetting("updating", "0");
} catch (Exception $e) {

}
