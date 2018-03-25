<?php

//Catch any errors
if (defined("MBDBRUN")) {
	die("Already ran\n");
}
define("MBDBRUN", 1);
define("BASE_DIR", __DIR__);
define("CONTENT_DIR", BASE_DIR . "/cla-git");

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
	if (substr($realPath, 0, 1) === "~")
		return $realPath;
	if (strpos($realPath, BASE_DIR) === false)
		return $realPath;

	$realPath = "~/" . str_replace(array(CONTENT_DIR . "/", BASE_DIR . "/", "~/"), "", $realPath);
	$realPath = str_replace("//", "/", $realPath);
	return $realPath;
}

/**
 * Turn a game path (~/data/etc) into a real path (cla-git/data/etc)
 * @param string $gamePath
 * @return string
 */
function GetRealPath($gamePath) {
	if (substr($gamePath, 0, 1) !== "~")
		return $gamePath;
	if (strpos($gamePath, BASE_DIR) !== false)
		return $gamePath;

	$full = str_replace("~/", CONTENT_DIR . "/", $gamePath);
	$full = str_replace("//", "/", $full);
	return $full;
}

/**
 * String SHA256 hash of a file or null if not exists
 * @param string $realPath
 * @return string|null
 */
function GetHash($realPath) {
	return is_file($realPath) ? hash("sha256", file_get_contents($realPath)) : null;
}

/**
 * Extract key/value information from a torque object line.
 * Basic line format should be something like
 *     key = "value";
 * @param string $line
 * @return array [key, value] extracted from the line
 */
function ExtractField($line) {
	//Extract the information out of the line
	$key = trim(substr($line, 0, strpos($line, "=")));
	$value = stripcslashes(trim(substr($line, strpos($line, "=") + 1, strlen($line))));

	//Sometimes people do this
	//Replaces '$usermods @ "/', '"marble/', and '"platinum/' with '"~/' so we can
	// parse them correctly
	$value = preg_replace('/(\$usermods\s*@\s*"\/)|("(marble|platinum(beta)?)\/)/i', '"~/', $value);

	$keyLen = strlen($key);
	$valueLen = strlen($value);

	if ($keyLen > 0) {
		if ($key[$keyLen - 1] === '"') {
			$key = substr($key, 0, $keyLen - 1);
		}
		if ($key[0] === '"') {
			$key = substr($key, 1);
		}
	}
	if ($valueLen > 0) {
		if ($value[$valueLen - 1] === ';') {
			$value = substr($value, 0, $valueLen - 1);
			$valueLen --;
		}
		if ($value[$valueLen - 1] === '"') {
			$value = substr($value, 0, $valueLen - 1);
		}
		if ($value[0] === '"') {
			$value = substr($value, 1);
		}
	}

	return [$key, $value];
}

/**
 * Encodes a string with base64, except splitting it up into chunks because
 * Torque likes to crash when you give it long strings
 * @param string $data      Input data to encode
 * @param int    $blockSize Maximum substring "block" size (default 1024)
 * @return array Array of "block"s of base64-encoded data
 */
function tbase64_encode($data, $blockSize = 1024) {
	$output = [];
	//Because Torque gets whiny when you try to write a lot of data
	for ($i = 0; $i < strlen($data); $i += $blockSize) {
		$chars = substr($data, $i, $blockSize);
		$output[] = base64_encode($chars);
	}
	return $output;
}

/**
 * Determine if the script is being requested by the game
 * @return bool
 */
function isTorque() {
	return strpos($_SERVER["HTTP_USER_AGENT"], "Torque") === 0;
}
