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

$files = $mission->getFiles();
$files = array_filter($files, function($info) {
	//Don't download stuff we should already have
	if ($info["official"]) {
		return false;
	}
	return true;
});


$json = json_encode($files);

header("Content-Length: " . strlen($json));
header("Content-Type: text/json");
echo($json . PHP_EOL);
