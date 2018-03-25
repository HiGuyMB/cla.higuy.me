<?php

require_once dirname(__DIR__) . "/bootstrap.php";

$file = $_REQUEST["file"];
//Check for dumb things
if (strpos($file, "../") !== false || $file[0] === "/") {
	//Get out of here
	header("HTTP/1.1 404 Not Found");
	die();
}

if (strpos($_SERVER["HTTP_USER_AGENT"], "Torque") !== false) {
	//Torque mode
} else {
	//Web

	//See if we have it
	$real = GetRealPath($file);
	if (is_file($real)) {
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$type = $finfo->file($real);

		//Yep
		header("HTTP/1.1 200 OK");
		header("Content-Length: " . filesize($real));
		header("Content-Type: $type");

		$maxage = 60 * 60 * 24; //One day

		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxage) . ' GMT');
		header('Cache-Control: maxage=' . $maxage);

		readfile($real);
	} else {
		//Nope
		header("HTTP/1.1 404 Not Found");
	}
}
