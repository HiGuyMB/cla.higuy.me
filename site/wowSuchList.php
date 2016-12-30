<?php

use CLAList\Mission;

require_once dirname(__DIR__) . '/bootstrap.php';

$em = GetEntityManager();
$all = $em->getRepository('CLAList\Mission')->findAll();

function GetBitmapLink(Mission $mission) {
	$bitmap = $mission->getBitmap();
	if (is_file(GetRealPath($bitmap)))
		return "getFile.php?file=" . urlencode($bitmap);
	return "NoImage.jpg";
}

function GetDownloadLink(Mission $mission) {
	return "getMissionZip.php?id=" . $mission->getId();
}

$page = $_REQUEST["page"] ?? 0;

$missions = array_slice($all, $page * 20, 20);

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
		$pages = count($all) / 20;
		for ($i = 0; $i < $pages; $i ++) { ?>
			<a href="?page=<?= $i ?>"><?= $i + 1 ?></a>
		<?php } ?>
	</div>
	<div id="list">
		<?php foreach ($missions as $mission) {
			/* @var \CLAList\Mission $mission */
			$name = $mission->getField("name");
			$name = nl2br(stripcslashes($name ? $name->getValue() : "Unnamed"));
			$desc = $mission->getField("desc");
			$desc = nl2br(stripcslashes($desc ? '"' . $desc->getValue() . '"' : "No Description"));
			$artist = $mission->getField("artist");
			$artist = nl2br(stripcslashes($artist ? $artist->getValue() : "No Author"));
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
