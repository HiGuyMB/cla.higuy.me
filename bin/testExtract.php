<?php

require_once dirname(__DIR__) . "/bootstrap.php";

$tests = <<<HERE
		thing = "other thing";
	this = "that";
	im = anIdiot;
	maybe["anarray"] = "not a bad thing";
	thisisvalid[2] = 1200;
	ohboy = \$usermods @ "/something/else/woo.dif";
	ohboy = \$uSeRmOdS @ "/something/else/woo.dif";
HERE;

$lines = explode("\n", $tests);
foreach ($lines as $line) {
	echo("Extracting $line\n");
	print_r(ExtractField($line));
}
