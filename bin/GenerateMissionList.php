<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\EnumGameType;
use CLAList\Field;
use CLAList\Filesystem;
use CLAList\Interior;
use CLAList\Mission;

SetQueryLogging(true);

$em = GetEntityManager();

//First find all interiors
//Filesystem::filterForEach("cla-git/data", '\.dif', function($file) use($em) {
//	$interior = $em->getRepository('CLAList\Interior')->findOneBy(["filePath" => $file]);
//	if ($interior === null) {
//		$interior = new Interior();
//		$interior->setBaseName(basename($file));
//		$interior->setFilePath($file);
//	}
//
//	$em->persist($interior);
//});

Filesystem::filterForEach("cla-git/data/missions/F-G", '\.mis', function ($file) use ($em) {
	$mission = $em->getRepository('CLAList\Mission')->findOneBy(["filePath" => $file]);

	if ($mission === null) {
		$mission = new Mission();
		$mission->setBaseName(basename($file));
		$mission->setFilePath($file);
		$mission->loadFile();
		$em->persist($mission);
	}

	$mission->loadFile();

	if (!$mission->hasField("file")) {
		$field = new Field($mission, "file", $file);
		$em->persist($field);
		$mission->getFields()->add($field);
	}
});

$em->flush();
