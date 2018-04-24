<?php

require_once dirname(dirname(__DIR__)) . "/bootstrap.php";

use Doctrine\ORM\Query\Expr\Join;

function flatten(array $array) {
	$return = array();
	array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
	return $return;
}

function jsonFormat($str) {
	$format = stripcslashes($str ?? "");
	return $format;
}

$em = GetEntityManager();

$builder = $em->createQueryBuilder();
$query = $builder
	->select('COUNT(m.id)')
	->from('CLAList\Entity\Mission', 'm')
	->join('m.fields', 'f')
	->where('f.name = :name')
	->setParameter(':name', "name")
	->getQuery()
;
$count = $query->getSingleScalarResult();
$missions = [];
try {
	$builder = $em->createQueryBuilder();
	$query = $builder
		->select('m.id', 'm.gems', 'm.easterEgg', 'm.modification', 'm.gameType', 'm.baseName',
		         'f.value  as fname', //Need aliases because these are all 'value' otherwise
		         'f2.value as fdesc',
		         'f3.value as fartist',
		         'b.baseName as bitmap')
		->from('CLAList\Entity\Mission', 'm')
		->join('m.fields', 'f', Join::WITH, 'f.name = :name') //Get only the name field
		->join('m.fields', 'f2', Join::WITH, 'f2.name = :desc') //Etc
		->join('m.fields', 'f3', Join::WITH, 'f3.name = :artist')
		->join('m.bitmap', 'b')
		->setParameters([":name" => "name", ":desc" => "desc", ":artist" => "artist"])
		->getQuery()
	;

	$results = $query->getArrayResult();
	foreach ($results as $result) {
		$missions[] = [
			"id" => $result["id"],
			"name" => $result["fname"],
			"desc" => $result["fdesc"],
			"artist" => $result["fartist"],
			"modification" => $result["modification"],
			"gameType" => $result["gameType"],
			"baseName" => $result["baseName"],
			"gems" => $result["gems"],
			"egg" => $result["easterEgg"],
			"bitmap" => $result["bitmap"]
		];
	}

} catch (Exception $e) {
	echo($e->getMessage() . "\n");
	echo($e->getTraceAsString());
}

$missions = array_slice($missions, 0, 100);

$body = json_encode($missions) . PHP_EOL;

header("Content-Length: " . strlen($body));
header("Content-Type: text/json");
echo($body);
