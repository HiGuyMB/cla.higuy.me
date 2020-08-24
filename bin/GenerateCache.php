<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\Model\Entity\Mission;

$size = [200, 127];
define('CACHE_DIR', BASE_DIR . "/cache/{$size[0]}x{$size[1]}");

if (!is_dir(CACHE_DIR)) {
	mkdir(CACHE_DIR, 0775, true);
}

$em = GetEntityManager();
$missions = $em->getRepository('CLAList\Model\Entity\Mission')->findAll();
foreach ($missions as $i => $mission) {
	/* @var Mission $mission */
	echo("$i / " . count($missions) . ": " . $mission->getBaseName() . "\n");
	renderMissionBitmap($mission, $size);
}

/**
 * @param Mission $mission
 */
function renderMissionBitmap(Mission $mission, $size) {
	if ($mission->getBitmap() === null) {
		return;
	}
	$bitmap = $mission->getBitmap()->getRealPath();

	if (!is_file($bitmap)) {
		return;
	}

	//See if we have a cached
	$cachePath = CACHE_DIR . "/{$mission->getId()}.jpg";

	if (is_file($cachePath)) {
		//Yep
		unlink($cachePath);
	}

	$im = new Imagick($bitmap);

	//Stretch image to fill the area so we can rescale without much trouble
	//This is the goal size scaled up to the size of the input image
	$minAxis = min($im->getImageWidth() / $size[0], $im->getImageHeight() / $size[1]);
	$fullSize = [$minAxis * $size[0], $minAxis * $size[1]];

	//Offsets so it fills into the center
	$offX = intval(($fullSize[0] - $im->getImageWidth()) / 2);
	$offY = intval(($fullSize[1] - $im->getImageHeight()) / 2);

	//Get the image on a canvas we can deal with
	$canvas = new Imagick();
	$canvas->newImage($fullSize[0], $fullSize[1], new ImagickPixel("#ffffff00"));
	$canvas->compositeImage($im, Imagick::COMPOSITE_DEFAULT, $offX, $offY);
	$canvas->flattenImages();

	//And finally get the output
	$canvas->resizeImage($size[0], $size[1], Imagick::FILTER_LANCZOS, 1, false);
	$canvas->setImageFormat("JPEG");
	$canvas->writeImage($cachePath);
}
