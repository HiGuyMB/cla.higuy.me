<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\Model\Entity\Mission;


//SetQueryLogging(true);

$em = GetEntityManager();

//Delete any missions that don't exist anymore
$missions = $em->getRepository('CLAList\Model\Entity\Mission')->findAll();
foreach ($missions as $mission) {
	/* @var Mission $mission */
	if (is_file($mission->getRealPath())) {
		continue;
	}

	//Doesn't exist
	echo("Removed deleted mission {$mission->getGamePath()}\n");
	$em->remove($mission);
}
$em->flush();

//TODO:
//Delete any interiors that have no references
//Delete any shapes that have no references
//Delete any textures that have no references

