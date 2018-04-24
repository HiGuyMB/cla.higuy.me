<pre>
<?php

use CLAList\Upload\UploadedFile;
use CLAList\Upload\UploadedMission;

require_once dirname(dirname(__DIR__)) . '/bootstrap.php';

$roots = [];
$files = [];

$fileCount = count($_FILES["mission"]["name"]);
for ($i = 0; $i < $fileCount; $i ++) {
	$file = new UploadedFile();
	$file->loadFilesArray($i);
	if ($file->getError()) {
		echo("Error: " . $file->getErrorString());
		continue;
	}

	$file->collectFiles($files);

	$roots[] = $file;
}

$install = [];

//Try to find a mission bitmap
foreach ($files as $file) {
	/* @var UploadedFile $file */
	if ($file->getType() === "mission") {
		$mission = new UploadedMission($file, $files);
		if ($mission->loadFile()) {
			$mission->install();
		}
	}
}
