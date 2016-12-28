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
