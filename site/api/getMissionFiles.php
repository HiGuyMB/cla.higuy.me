<?php

use CLAList\Entity\Interior;
use CLAList\Entity\Mission;
use CLAList\Entity\Shape;
use CLAList\Entity\Texture;

require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

$em = GetEntityManager();
/* @var Mission $mission */
$mission = $em->find('CLAList\Entity\Mission', $_REQUEST["id"]);

//Can't find it
if ($mission === null) {
	header("HTTP/1.1 404 Not Found");
	die();
}

function addFile(&$files, $file, $hash, $type = "data") {
	foreach ($files as $entry) {
		if ($entry["path"] === $file)
			return;
	}
	$array = [
		"path" => $file,
		"hash" => $hash,
		"type" => $type
	];
	if ($hash === null)
		$array["missing"] = true;
	$files[] = $array;
}

$files = [];

addFile($files, $mission->getGamePath(), $mission->getHash(), "mission");
addFile($files, $mission->getBitmap()->getGamePath(), $mission->getBitmap()->getHash(), "bitmap");

foreach ($mission->getInteriors() as $interior) {
	/* @var Interior $interior */
	addFile($files, $interior->getGamePath(), $interior->getHash());

	foreach ($interior->getTextures() as $texture) {
		/* @var Texture $texture */
		addFile($files, $texture->getGamePath(), $texture->getHash());
	}
}

foreach ($mission->getShapes() as $shape) {
	/* @var Shape $shape */
	addFile($files, $shape->getGamePath(), $shape->getHash());

	foreach ($shape->getTextures() as $texture) {
		/* @var Texture $texture */
		addFile($files, $texture->getGamePath(), $texture->getHash());
	}
}

addFile($files, $mission->getSkybox()->getGamePath(), $mission->getSkybox()->getHash());
foreach ($mission->getSkybox()->getTextures() as $texture) {
	/* @var Texture $texture */
	addFile($files, $texture->getGamePath(), $texture->getHash());
}

$json = json_encode($files);

header("Content-Length: " . strlen($json));
header("Content-Type: text/json");
echo($json . PHP_EOL);
