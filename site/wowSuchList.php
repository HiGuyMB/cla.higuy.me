<?php

use CLAList\Mission;

require_once dirname(__DIR__) . '/bootstrap.php';

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

function htmlFormat($str) {
    $format = nl2br(htmlentities(stripcslashes($str), ENT_QUOTES));
    $format = preg_replace('/(?<= ) /', "&nbsp;", $format);
    return $format;
}

$em = GetEntityManager();
$page = $_REQUEST["page"] ?? 0;
$pageSize = 20;

$builder = $em->createQueryBuilder();
$query = $builder
    ->select('m.id')
    ->from('CLAList\Mission', 'm')
    ->join('m.fields', 'f')
    ->where('f.name = :name')
    ->setParameter(":name", "name")
    ->orderBy('f.value', 'ASC')
    ->setFirstResult($page * $pageSize)
    ->setMaxResults($pageSize)
    ->getQuery()
;
$ids = flatten($query->getArrayResult());

$builder = $em->createQueryBuilder();
$query = $builder
    ->select('COUNT(m.id)')
    ->from('CLAList\Mission', 'm')
    ->getQuery()
;
$count = $query->getSingleScalarResult();
$pages = ceil($count / 20);

?>
<html>
<head>
	<title>Wow Such List</title>
	<style>
		#list {
			display: flex;
			flex-wrap: wrap;
			flex-direction: row;
			justify-content: center;
		}
		#list .mission {
			width: 240px;
			min-height: 160px;
			border: 1px solid #999;
			border-radius: 10px;
			margin: 10px;
			display: flex;
			flex-direction: column;
		}
		#list .mission .image {
			width: 200px;
			height: 127px;
			align-self: center;
		}
		#list .mission .title {
			text-align: center;
		}
	</style>
</head>
<body>
	<div id="pagination">
		Page:
		<?php
		for ($i = 0; $i < $pages; $i ++) { ?>
			<a href="?page=<?= $i ?>"><?= $i + 1 ?></a>
		<?php } ?>
	</div>
	<div id="list">
		<?php foreach ($ids as $id) {
		    $mission = $em->find('CLAList\Mission', $id);
			/* @var \CLAList\Mission $mission */
			$name = $mission->getField("name");
			$name = htmlFormat($name ? $name->getValue() : "Unnamed");
			$desc = $mission->getField("desc");
			$desc = htmlFormat($desc ? '"' . $desc->getValue() . '"' : "No Description");
			$artist = $mission->getField("artist");
			$artist = htmlFormat($artist ? $artist->getValue() : "No Author");
		?>
		<div class="mission">
			<img src="<?= GetBitmapLink($mission) ?>" class="image" />
			<div class="title"><?= $name ?></div>
			<div class="desc"><?= $desc ?></div>
			<div class="artist">By <?= $artist ?></div>
			<div class="modification">Mod (Probably): <?= $mission->getModification() ?></div>
			<div class="gems">Gem Count: <?= $mission->getGems() ?></div>
			<div class="easteregg">Has Easter Egg: <?= $mission->getEasterEgg() ? "Yes" : "No" ?></div>
			<div class="download">
				<a href="<?=GetDownloadLink($mission) ?>">Download (WIP)</a>
			</div>

		</div>
		<?php } ?>
	</div>
</body>
</html>
