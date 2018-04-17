<?php

use CLAList\Upload\UploadedFile;
use CLAList\Upload\UploadedMission;

require_once dirname(__DIR__) . '/bootstrap.php';

$goodStuff = $argv[1];

for ($i = 0; $i < 1000; $i ++) {
	$files = [];
	$next = getNextTest($goodStuff);
	if ($next !== false) {
		set_error_handler(function() { /* ignore errors */ });
		$unzipped = new UploadedFile();
		$unzipped->loadFile($next);
		restore_error_handler();

		if ($unzipped->getError()) {
			echo("Error: " . $unzipped->getErrorString() . "\n");
		} else {
			$unzipped->collectFiles($files);

			$install = [];
			//Try to find a mission bitmap
			foreach ($files as $file) {
				/* @var UploadedFile $file */
				if ($file->getType() === "mission") {
					$mission = new UploadedMission($file, $files);
					if ($mission->loadFile()) {
						$mission->dryInstall();
					} else {
						echo("Mission load failed\n");
					}
				}
			}
		}

		//Clean up
		unset($file);
		unset($files);
		if (is_file($next)) {
			unlink($next);
		}
	}
}

function getNextTest($samples) {
	$outputFile = tempnam(sys_get_temp_dir(), "fuzzTest");
	unlink($outputFile);
	$outputFile .= ".zip";

	$descriptors = [
		0 => ["pipe", "r"],
		1 => ["file", $outputFile, "w"],
		2 => ["pipe", "w"]
	];

	$command = "radamsa -r " . escapeshellarg($samples);
	$process = proc_open($command, $descriptors, $pipes);

	//If it went through...
	if (is_resource($process)) {
		fclose($pipes[0]);
		fclose($pipes[2]);

		proc_close($process);

		return $outputFile;
	} else {
		return false;
	}
}
