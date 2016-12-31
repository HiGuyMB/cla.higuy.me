<?php

require_once dirname(dirname(__DIR__)) . "/bootstrap.php";

use CLAList\Mission;

function flatten(array $array) {
	$return = array();
	array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
	return $return;
}

function GetBitmapLink(Mission $mission) {
	$bitmap = $mission->getBitmap();
	if (is_file(GetRealPath($bitmap)))
		return "getFile.php?file=" . urlencode($bitmap);
	return "NoImage.jpg";
}

function GetDownloadLink(Mission $mission) {
	return "getMissionZip.php?id=" . $mission->getId();
}

function jsonFormat($str) {
	$format = stripcslashes($str ?? "");
	return $format;
}

$em = GetEntityManager();
$page = intval($_REQUEST["page"] ?? 0);
$pageSize = 20;

$order = $_REQUEST["order"] ?? "name";
$direction = array_key_exists("desc", $_REQUEST) ? "DESC" : "ASC";

$builder = $em->createQueryBuilder();
$query = $builder
	->select('COUNT(m.id)')
	->from('CLAList\Mission', 'm')
	->join('m.fields', 'f')
	->where('f.name = :name')
	->setParameter(':name', $order)
	->getQuery()
;
$count = $query->getSingleScalarResult();

$builder = $em->createQueryBuilder();
$query = $builder
	->select('m.id')
	->from('CLAList\Mission', 'm')
	->join('m.fields', 'f')
	->where('f.name = :name')
	->setParameter(':name', $order)
	->orderBy('f.value', $direction)
	->setFirstResult($page * $pageSize)
	->setMaxResults($pageSize)
	->getQuery()
;
$ids = flatten($query->getArrayResult());

$missions = [
	"total" => $count,
	"page" => $page,
	"pageSize" => $pageSize,
	"missions" => []
];
foreach ($ids as $id) {
	/* @var Mission $mission */
	$mission = $em->find('CLAList\Mission', $id);

	$missions["missions"][] = [
		"id" => $id,
		"name" => jsonFormat($mission->getFieldValue("name")),
		"desc" => jsonFormat($mission->getFieldValue("desc")),
		"artist" => jsonFormat($mission->getFieldValue("artist")),
		"modification" => jsonFormat($mission->getModification()),
		"gems" => $mission->getGems(),
		"egg" => $mission->getEasterEgg(),
		"bitmapURL" => GetBitmapLink($mission),
		"downloadURL" => GetDownloadLink($mission)
	];
}

header("Content-Type: text/json");

echo(json_encode($missions));
