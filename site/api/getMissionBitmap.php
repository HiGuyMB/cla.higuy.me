<?php

require_once dirname(dirname(__DIR__)) . "/bootstrap.php";

use CLAList\Mission;

$em = GetEntityManager();
/* @var Mission $mission */
$mission = $em->find('CLAList\Entity\Mission', $_REQUEST["id"]);

$bitmap = $mission->getBitmap()->getRealPath();

if (is_file($bitmap)) {
	header("HTTP/1.1 200 OK");
} else {
	header("HTTP/1.1 404 Not Found");
	$bitmap = dirname(__DIR__) . "/NoImage.jpg";
}

//Goal size
$size = [200, 127];

//See if we have a cached
$cachePath = BASE_DIR . "/cache/{$size[0]}x{$size[1]}/{$mission->getId()}.jpg";
if (is_file($cachePath)) {
	//Yep
	header("Content-Length: " . filesize($cachePath));
	header("Content-Type: image/jpeg");

	readfile($cachePath);
	return;
}

$tmp = tempnam(sys_get_temp_dir(), "bitmap");
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
$canvas->writeImage($tmp);

//Spit it out
$contents = file_get_contents($tmp);

//Yep
header("Content-Length: " . filesize($tmp));
header("Content-Type: image/jpeg");

//Cache
$maxAge = 60 * 60 * 24; //One day
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $maxAge) . " GMT");
header("Cache-Control: maxage=" . $maxAge);
readfile($tmp);

mkdir(dirname($cachePath), 0775, true);
rename($tmp, $cachePath);
