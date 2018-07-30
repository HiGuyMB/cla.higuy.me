<?php

require_once dirname(__DIR__) . '/bootstrap.php';

use CLAList\Entity\AbstractGameEntity;
use CLAList\Entity\Interior;
use CLAList\Entity\Shape;
use CLAList\Entity\Skybox;
use CLAList\Entity\Texture;
use CLAList\Filesystem;
use CLAList\Paths;

$em = GetEntityManager();

$base = $pathConfig["official"];
Paths::setContentDir($pathConfig["official"]);

/** @noinspection PhpUndefinedClassInspection */
$updateFn = function($file) {
	$gamePath = Paths::getGamePath($file);

	//See if we have it already
	/** @var AbstractGameEntity $item */
	$item = static::findByGamePath($gamePath, false);
	if ($item !== null) {
		//Check if it's up to date
		if ($item->getHash() === Paths::getHash($file)) {
			//Make sure it's official
			$item->setOfficial(true);
			return;
		}
		echo("Hash mismatch on $gamePath\n");
		//No, reload it
		$item->__construct($gamePath, $file);
		$item->setOfficial(true);
		return;
	}

	//Need to update this one
	$item = static::findByGamePath($gamePath, true);
	$item->setOfficial(true);

	echo("New official: $gamePath\n");
};

try {
	Filesystem::filterForEach($base . "/data", '/\.dif$/', $updateFn->bindTo(null, Interior::class));
	Filesystem::filterForEach($base . "/data", '/\.dml$/', $updateFn->bindTo(null, Skybox::class));
	Filesystem::filterForEach($base . "/data", '/\.dts$/', $updateFn->bindTo(null, Shape::class));

	//Textures that are for interiors, shapes, or skies. Everything else is
	// likely a mission image and we don't care.
	$texUpdateFn = $updateFn->bindTo(null, Texture::class);
	Filesystem::filterForEach($base . "/data", '/\/(lb)?interiors.*?\.(png|jpg|bmp|dds)$/i', $texUpdateFn);
	Filesystem::filterForEach($base . "/data", '/\/shapes.*?\.(png|jpg|bmp|dds)$/i', $texUpdateFn);
	Filesystem::filterForEach($base . "/data", '/\/skies.*?\.(png|jpg|bmp|dds)$/i', $texUpdateFn);

	SetQueryLogging(true);

	$em->flush();
} catch (Exception $e) {
	echo($e->getMessage());
}
