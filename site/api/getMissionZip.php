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

$files = $mission->getFiles();
$files = array_filter($files, function($info) {
	//Adding this separately
	if ($info["type"] === "mission") {
		return false;
	}
	//Don't download stuff we should already have
	if ($info["official"]) {
		return false;
	}
	return true;
});

//Create a zip file to output to the user
$zipPath = tempnam(sys_get_temp_dir(), "mission");
$zip = new ZipArchive();
$zip->open($zipPath, ZipArchive::OVERWRITE | ZipArchive::CREATE);

//Add mission in a sensible place
$zip->addFile($mission->getRealPath(), "data/missions/{$mission->getBaseName()}");

//Add data files
foreach ($files as $info) {
	$file = $info["path"];
	$zip->addFile(Paths::getRealPath($file), str_replace("~/", "", $file));
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

