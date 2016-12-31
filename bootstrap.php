<?php

//Catch any errors
if (defined("MBDBRUN")) {
	die("Already ran\n");
}
define("MBDBRUN", 1);
define("BASE_DIR", __DIR__);

require_once BASE_DIR . "/vendor/autoload.php";

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use CLAList\Logger;

$paths = [
	"src/CLAList"
];
$devMode = true;

/**
 * $dbConfig = [
 *     'driver'   => 'pdo_mysql',
 *     'user'     => 'cla',
 *     'password' => '',
 *     'dbname'   => 'cla',
 * ];
 */
require_once BASE_DIR . "/config/config.php";

$config = Setup::createAnnotationMetadataConfiguration($paths, $devMode);
$config->addCustomNumericFunction("RAND", 'CLAList\Rand');
$entityManager = EntityManager::create($dbConfig, $config);

Type::addType("EnumGameType", 'CLAList\EnumGameType');

/**
 * @return EntityManager
 */
function GetEntityManager() {
	global $entityManager;
	return $entityManager;
}

/**
 * @param bool $logging
 */
function SetQueryLogging($logging) {
	global $config;
	if ($logging) {
		$config->setSQLLogger(new Logger());
	} else {
		$config->setSQLLogger();
	}
}

/**
 * Turn a real path (cla-git/data/etc) into a game path (~/data/etc)
 * @param string $realPath
 * @return string
 */
function GetGamePath($realPath) {
	if (substr($realPath, 0, 1) == "~")
		return $realPath;

	$realPath = "~/" . str_replace(array(BASE_DIR . "/", "cla-git/", "~/"), "", $realPath);
	$realPath = str_replace("//", "/", $realPath);
	return $realPath;
}

/**
 * Turn a game path (~/data/etc) into a real path (cla-git/data/etc)
 * @param string $gamePath
 * @return string
 */
function GetRealPath($gamePath) {
	if (strpos($gamePath, BASE_DIR) !== false)
		return $gamePath;

	$full = str_replace("~/", "cla-git/", $gamePath);
	$full = BASE_DIR . "/" . $full;
	$full = str_replace("//", "/", $full);
	return $full;
}

/**
 * @param string $realPath
 * @return string|null
 */
function GetHash($realPath) {
	return is_file($realPath) ? hash("sha256", file_get_contents($realPath)) : null;
}
