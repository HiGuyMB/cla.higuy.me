<?php

define("BASE_DIR", dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

use CLAList\Database;

try {
	$database = new Database("cla");
	$database->setSetting("updating", "1");

	$query = $database->prepare("SELECT `id`, `file_path` FROM `@_missions`");
	$query->execute();
	$missions = $query->fetchAll(PDO::FETCH_ASSOC);

	foreach ($missions as $row) {
		$full = $database->convertPathToAbsolute($row["file_path"]);
		$sha256 = hash("sha256", file_get_contents($full));

		$query = $database->prepare("UPDATE `@_missions` SET `hash` = :hash WHERE `id` = :id");
		$query->bindParam(":hash", $sha256);
		$query->bindParam(":id", $row["id"]);
		$query->execute();
	}

	$database->setSetting("updating", "0");
} catch (Exception $e) {

}
