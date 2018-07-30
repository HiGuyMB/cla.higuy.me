<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\Model\Filesystem;
use CLAList\Model\Entity\Mission;
use CLAList\Paths;

//SetQueryLogging(true);

$em = GetEntityManager();

function createMission($file) {
	$em = GetEntityManager();

	$gamePath = Paths::getGamePath($file);

	/* @var Mission $mission */
	$mission = $em->getRepository('CLAList\Model\Entity\Mission')->findOneBy(["gamePath" => $gamePath]);

	if ($mission === null) {
		$mission = new Mission($gamePath);
		echo("Added new mission: {$mission->getBaseName()}\n");
	} else {
		//Load changes
		//		echo("Same mission: {$mission->getBaseName()}\n");
		//		$mission->loadFile();
	}
	$em->persist($mission);
	$em->flush($mission);
	$em->detach($mission);
}

$em->beginTransaction();
try {
	Filesystem::filterForEach(Paths::getContentDir() . "/data", '/\.mis/i', 'createMission');
} catch (Exception $e) {
	echo("Exception: " . $e->getMessage() . "\n");
}


$em->flush();
$em->commit();

//Now delete any that don't exist anymore
//require "CleanupMissionList.php";
