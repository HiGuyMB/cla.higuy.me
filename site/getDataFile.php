<?php

$file = $_GET["file"];
$file = str_replace("~/", "cla-git/", $file);

$full = dirname(__DIR__) . "/" . $file;

if (is_file($full)) {
	$misname = pathinfo($file, PATHINFO_BASENAME);

	//Let them know that we're a zip file and to download
	header("Content-Length: " . filesize($full) * 2);

	//Spit it out
	$conts = file_get_contents($full);

	echo("HASH " . hash("sha256", $conts) . "\n");

	for ($i = 0; $i < strlen($conts); $i += 512) {
		$chars = substr($conts, $i, 512);
		for ($j = 0; $j < strlen($chars); $j ++) {
			$char = substr($chars, $j, 1);
			printf("%02X", ord($char));
		}
		printf("\n");
	}

} else {
	header("HTTP/1.1 404 Not Found");
}

