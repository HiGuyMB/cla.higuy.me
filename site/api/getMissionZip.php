<?php

use CLAList\Entity\Interior;
use CLAList\Entity\Mission;
use CLAList\Entity\Shape;
use CLAList\Entity\Texture;
use CLAList\Paths;

require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

$em = GetEntityManager();
/* @var Mission $mission */
$mission = $em->find('CLAList\Entity\Mission', $_REQUEST["id"]);

//Can't find it
if ($mission === null) {
	header("HTTP/1.1 404 Not Found");
	die();
}

$files = [];

foreach ($mission->getInteriors() as $interior) {
	/* @var Interior $interior */
	$files[] = $interior->getGamePath();

	foreach ($interior->getTextures() as $texture) {
		/* @var Texture $texture */
		$files[] = $texture->getGamePath();
	}
}

foreach ($mission->getShapes() as $shape) {
	/* @var Shape $shape */
	$files[] = $shape->getGamePath();

	foreach ($shape->getTextures() as $texture) {
		/* @var Texture $texture */
		$files[] = $texture->getGamePath();
	}
}

$files[] = $mission->getSkybox()->getGamePath();
foreach ($mission->getSkybox()->getTextures() as $texture) {
	/* @var Texture $texture */
	$files[] = $texture->getGamePath();
}

$files = array_unique($files);
sort($files);

//Create a zip file to output to the user
$zipPath = tempnam(sys_get_temp_dir(), "mission");
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::OVERWRITE | ZipArchive::CREATE);

$zip->addFile($mission->getRealPath(), "data/missions/{$mission->getBaseName()}");

//Add data files
foreach ($files as $file) {
	$zip->addFile(Paths::GetRealPath($file), str_replace("~/", "", $file));
}
$zip->close();
unset($zip);

//Let them know this is a real file
header("HTTP/1.1 200 OK");
header("Content-Length: " . filesize($zipPath));
header("Content-Type: application/octet-stream");
header("Content-disposition: attachment; filename=\"{$mission->getBaseName()}.zip\"");

//Send all the data and clean up
readfile($zipPath);
unlink($zipPath);

