<?php

require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

$file = $_REQUEST["file"];

//Check for dumb things
if (strpos($file, "../") !== false || $file[0] === "/") {
	//Get out of here
	return;
}

$real = dirname(__DIR__) . "/res/$file";
if (!is_file($real)) {
	return;
}

readfile($real);
