<?php

define("BASE_DIR", dirname(__DIR__));
require BASE_DIR . '/vendor/autoload.php';

use CLAList\Database;

try {
	$database = new Database("cla");
	$query = $database->prepare("SELECT `id`, `file` FROM `@_missions`");
	$query->execute();
	$missions = $query->fetchAll(PDO::FETCH_ASSOC);

	foreach ($missions as $row) {
		$full = $database->convertPathToAbsolute($row["file"]);
		$sha256 = hash("sha256", file_get_contents($full));

		$query = $database->prepare("UPDATE `@_missions` SET `hash` = :hash WHERE `id` = :id");
		$query->bindParam(":hash", $sha256);
		$query->bindParam(":id", $row["id"]);
		$query->execute();
	}

} catch (Exception $e) {

}
