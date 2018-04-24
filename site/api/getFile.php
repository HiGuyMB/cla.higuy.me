<?php

require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

$file = $_REQUEST["file"];

//Check for dumb things
if ($file[0] !== "~") {
	//Get out of here
	header("HTTP/1.1 404 Not Found");
	die();
}

$real = GetRealPath($file);
if (!is_file($real)) {
	header("HTTP/1.1 404 Not Found");
	die();
}
readfile($real);
