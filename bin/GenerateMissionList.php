<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\Filesystem;
use CLAList\Mission;

//SetQueryLogging(true);

$em = GetEntityManager();

Filesystem::filterForEach(CONTENT_DIR . "/data", '\.mis', function ($file) use ($em) {
	$gamePath = GetGamePath($file);

	/* @var Mission $mission */
	$mission = $em->getRepository('CLAList\Entity\Mission')->findOneBy(["gamePath" => $gamePath]);

	if ($mission === null) {
		$mission = new Mission($gamePath);
		echo("Added new mission: {$mission->getBaseName()}\n");
	} else {
		//Load changes
//		echo("Same mission: {$mission->getBaseName()}\n");
//		$mission->loadFile();
	}
	$em->persist($mission);
	$em->flush();
	$em->detach($mission);
});

$em->flush();

//Now delete any that don't exist anymore
require "CleanupMissionList.php";
