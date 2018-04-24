<?php

require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

use CLAList\Paths;

$files = $_REQUEST["files"];

$results = [];
foreach ($files as $file) {
	//Check for dumb things
	if (strpos($file, "../") !== false || $file[0] === "/") {
		//Get out of here
		continue;
	}

	$real = Paths::getRealPath($file);
	if (!is_file($real)) {
		continue;
	}

	$conts = file_get_contents($real);
	$results[] = [
		"path" => $file,
		"contents" => tbase64_encode($conts)
	];
}

$json = json_encode($results);

header("Content-Length: " . strlen($json));
header("Content-Type: text/json");
echo($json . PHP_EOL);
