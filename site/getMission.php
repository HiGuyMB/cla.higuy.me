<?php

$file = $_GET["file"];
$file = str_replace("~/", "cla-git/", $file);

$full = dirname(__DIR__) . "/" . $file;

if (is_file($full)) {
	$misname = pathinfo($file, PATHINFO_BASENAME);

	//Let them know that we're a zip file and to download
	header("Content-Disposition: attachment; filename=$misname");
	header("Content-Type: application/text");
	header("Content-Length: " . filesize($full));

	//Spit it out
	readfile($full);
} else {
	header("HTTP/1.1 404 Not Found");
}
