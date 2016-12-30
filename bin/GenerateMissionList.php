<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\EnumGameType;
use CLAList\Field;
use CLAList\Filesystem;
use CLAList\Interior;
use CLAList\Mission;

xdebug_break();

//SetQueryLogging(true);

$em = GetEntityManager();

Filesystem::filterForEach("cla-git/data/missions", '\.mis', function ($file) use ($em) {
	$gamePath = GetGamePath($file);

	/* @var Mission $mission */
	$mission = $em->getRepository('CLAList\Mission')->findOneBy(["filePath" => $gamePath]);

	if ($mission === null) {
		$mission = new Mission($gamePath);
		echo("Added new mission: {$mission->getBaseName()}\n");
	} else {
		//Load changes
		echo("Same mission: {$mission->getBaseName()}\n");
		$mission->loadFile();
	}
	$em->persist($mission);
	$em->flush();
	$em->detach($mission);
});

$em->flush();
