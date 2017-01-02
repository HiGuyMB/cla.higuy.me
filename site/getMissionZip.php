<?php

use CLAList\Interior;
use CLAList\Mission;
use CLAList\Shape;
use CLAList\Texture;

require_once dirname(__DIR__) . '/bootstrap.php';

$em = GetEntityManager();
/* @var Mission $mission */
$mission = $em->find('CLAList\Mission', $_REQUEST["id"]);

//Can't find it
if ($mission === null) {
	header("HTTP/1.1 404 Not Found");
	die();
}

$files = [];

foreach ($mission->getInteriors() as $interior) {
	/* @var Interior $interior */
	$files[] = $interior->getFilePath();

	foreach ($interior->getTextures() as $texture) {
		/* @var Texture $texture */
		$files[] = $texture->getFilePath();
	}
}

foreach ($mission->getShapes() as $shape) {
	/* @var Shape $shape */
	$files[] = $shape->getFilePath();

	foreach ($shape->getTextures() as $texture) {
		/* @var Texture $texture */
		$files[] = $texture->getFilePath();
	}
}

$files[] = $mission->getSkybox()->getFilePath();
foreach ($mission->getSkybox()->getTextures() as $texture) {
	/* @var Texture $texture */
	$files[] = $texture->getFilePath();
}

$files = array_unique($files);
sort($files);

//Create a zip file to output to the user
$zipPath = tempnam("/tmp", "mission") . ".zip";
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::OVERWRITE | ZipArchive::CREATE);

$zip->addFile($mission->getRealPath(), "data/missions/{$mission->getBaseName()}");

//Add data files
foreach ($files as $file) {
	$zip->addFile(GetRealPath($file), str_replace("~/", "", $file));
}
$zip->close();

//Let them know this is a real file
header("HTTP/1.1 200 OK");
header("Content-Length: " . filesize($zipPath));
header("Content-Type: application/octet-stream");
header("Content-disposition: attachment; filename=\"{$mission->getBaseName()}.zip\"");

//Send all the data and clean up
readfile($zipPath);
unlink($zipPath);
