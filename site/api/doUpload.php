<pre>
<?php

use CLAList\Model\Entity\Mission;
use CLAList\Model\Upload\UploadedFile;
use CLAList\Model\Upload\UploadedMission;

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

$em = GetEntityManager();

//Try to find a mission bitmap
foreach ($files as $file) {
	/* @var UploadedFile $file */
	if ($file->getType() === "mission") {
		$umission = new UploadedMission($file, $files);
		if ($umission->loadFile()) {
			if ($umission->install()) {
			    //Now create a new mission to enter this into the db
                $mission = new Mission($umission->getGamePath());
                $em->persist($mission);
				try {
					$em->flush();
					echo("Inserted into the db with path {$mission->getGamePath()} id {$mission->getId()}\n");
				} catch (\Doctrine\ORM\OptimisticLockException $e) {
				    echo("Error inserting into the database\n");
				}
			} else {
			    echo("Could not copy mission\n");
            }
		} else {
		    echo("Not a valid mission\n");
        }
	}
}
