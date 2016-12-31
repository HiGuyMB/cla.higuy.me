<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\Filesystem;
use CLAList\Mission;

//SetQueryLogging(true);

$em = GetEntityManager();

Filesystem::filterForEach("cla-git/data", '\.mis', function ($file) use ($em) {
	$gamePath = GetGamePath($file);

	/* @var Mission $mission */
	$mission = $em->getRepository('CLAList\Mission')->findOneBy(["filePath" => $gamePath]);

	if ($mission === null) {
		$mission = new Mission($gamePath);
		echo("Added new mission: {$mission->getBaseName()}\n");
	} else {
		//Load changes
		echo("Same mission: {$mission->getBaseName()}\n");
//		$mission->loadFile();
	}
	$em->persist($mission);
	$em->flush();
	$em->detach($mission);
});

$em->flush();
