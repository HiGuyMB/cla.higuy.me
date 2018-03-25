<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\Filesystem;
use CLAList\Mission;

//SetQueryLogging(true);

$em = GetEntityManager();

$output = "./site/res";
//Goal size per mission
$size = [23, 17];
$padding = 1;

$missions = $em->getRepository('CLAList\Entity\Mission')->findAll();

$missionsPerRow = floor(4096 / ($size[0] + $padding));
$missionsPerCol = floor(4096 / ($size[1] + $padding));

$cols = ceil(count($missions) / $missionsPerCol);

$mapping = [];
$bitmap = new Imagick();
$bitmap->newImage($cols * ($size[0] + $padding), 4096, new ImagickPixel("#00000000"));

for ($i = 0; $i < $missionsPerRow; $i ++) {
	$xStart = $i * ($size[0] + $padding);

	$start = $i * $missionsPerCol;
	$end = min(count($missions), ($i + 1) * $missionsPerCol);
	for ($j = $start; $j < $end; $j ++) {
		/* @var Mission $mission */
		$mission = $missions[$j];

		$mbo = $mission->getBitmap();
		if (is_null($mbo) || !is_file($mbo->getRealPath())) {
			$mBitmapPath = dirname(__DIR__) . "/site/NoImage.jpg";
		} else {
			$mBitmapPath = $mbo->getRealPath();
		}

		$mBitmap = new Imagick($mBitmapPath);

		//Stretch image to fill the area so we can rescale without much trouble
		//This is the goal size scaled up to the size of the input image
		$minAxis = min($mBitmap->getImageWidth() / $size[0], $mBitmap->getImageHeight() / $size[1]);
		$fullSize = [$minAxis * $size[0], $minAxis * $size[1]];

		//Offsets so it fills into the center
		$offX = intval(($fullSize[0] - $mBitmap->getImageWidth()) / 2);
		$offY = intval(($fullSize[1] - $mBitmap->getImageHeight()) / 2);

		//Shrink it down
		$shrink = new Imagick();
		$shrink->newImage($fullSize[0], $fullSize[1], new ImagickPixel("#ffffffff"));
		$shrink->compositeImage($mBitmap, Imagick::COMPOSITE_DEFAULT, $offX, $offY);
		$shrink->flattenImages();
		$shrink->resizeImage($size[0], $size[1], Imagick::FILTER_LANCZOS, 1, false);

		$yStart = ($j - $start) * ($size[1] + $padding);
		$bitmap->compositeImage($shrink, Imagick::COMPOSITE_DEFAULT, $xStart, $yStart);

		$mapping[$mission->getId()] = [$xStart, $yStart];

		if ($j % 10 == 0)
			echo("Composited $j images\n");
	}
	echo("Finished " . ($i + 1) . " rows\n");
	$bitmap->flattenImages();
	$bitmap->writeImage("$output/list.png");
}

file_put_contents("$output/listMapping.json", json_encode($mapping));
