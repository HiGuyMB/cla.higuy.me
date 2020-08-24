<?php

namespace CLAList\Route;

use CLAList\Model\Entity\Mission;
use CLAList\Model\Upload\UploadedFile;
use CLAList\Model\Upload\UploadedMission;
use CLAList\Router;

class UploadRouter extends Router {
	public function register() {
		$this->klein->respond('GET', '/upload', [$this, 'renderPage']);
		$this->klein->respond('POST', '/api/v1/upload', [$this, 'upload']);
	}

	public function renderPage(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		return $this->twig->render("Upload.twig", []);
	}

	public function upload(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		$files = $request->files();
		$missions = $files->get("mission");

		$roots = [];
		$files = [];

		echo("<pre>");

		//Load
		$fileCount = count($missions);
		for ($i = 0; $i < $fileCount; $i ++) {
			$file = new UploadedFile();
			$file->loadFilesArrayData([
				"name" => $missions["name"][$i],
				"type" => $missions["type"][$i],
				"tmp_name" => $missions["tmp_name"][$i],
				"error" => $missions["error"][$i],
			]);
			if ($file->getError()) {
				echo("Error: " . $file->getErrorString());
				continue;
			}

			$file->collectFiles($files);

			$roots[] = $file;
		}

		$install = [];
		$umissions = [];

		//Try to find a mission bitmap
		foreach ($files as $file) {
			/* @var UploadedFile $file */
			if ($file->getType() === "mission") {
				$umission = new UploadedMission($file, $files);
				if ($umission->loadFile()) {
					$umissions[] = $umission;
					foreach ($umission->getInstallFiles() as list($ifile, $ipath)) {
						/* @var UploadedFile $ifile */
						$install[] = $ifile->getPath();
					}
				} else {
					echo("Not a valid mission\n");
				}
			}
		}

		foreach ($files as $file) {
			if (!in_array($file->getPath(), $install)) {
				echo("Unused file: {$file->getRelativePath()}\n");
			}
		}

		$dryRun = true;

		$em = GetEntityManager();
		foreach ($umissions as $umission) {
			if ($dryRun) {
				$umission->dryInstall();
			} else {
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
			}
		}
	}

}