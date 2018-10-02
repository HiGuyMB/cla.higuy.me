<?php

namespace CLAList\Route;

use CLAList\Model\Entity\Mission;
use CLAList\Paths;
use CLAList\Router;
use Imagick;
use ImagickPixel;
use ZipArchive;

class MissionRouter extends Router {
	public function register() {
		$this->klein->respond('GET', '/missions', function(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
			$service->render("missionList.php");
		});

		$this->klein->respond('GET', '/api/missions/[i:id]/[files|bitmap|zip:action]', [$this, 'render']);

	}

	/**
	 * @param \Klein\Request         $request
	 * @param \Klein\Response        $response
	 * @param \Klein\ServiceProvider $service
	 * @param \Klein\App             $app
	 */
	public function render(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		$service->validateParam('id')->notNull()->isChars("0-9");
		$id = $request->param('id');

		/* @var Mission $mission */
		$mission = Mission::find(["id" => $id]);

		switch ($request->action) {
			case "files":
				$this->renderMissionFiles($response, $mission);
				break;
			case "bitmap":
				$this->renderMissionBitmap($response, $mission);
				break;
			case "zip":
				$this->renderMissionZip($response, $mission);
				break;
		}
	}

	/**
	 * @param \Klein\Response $response
	 * @param Mission $mission
	 */
	private function renderMissionFiles(\Klein\Response $response, $mission) {
		$files = $mission->getFiles();
		$files = array_filter($files, function ($info) {
			//Don't download stuff we should already have
			if ($info["official"]) {
				return false;
			}
			return true;
		});

		$response->json($files);
	}

	/**
	 * @param \Klein\Response $response
	 * @param Mission $mission
	 */
	private function renderMissionBitmap(\Klein\Response $response, Mission $mission) {
		$bitmap = $mission->getBitmap()->getRealPath();

		if (!is_file($bitmap)) {
			$response->code(404);
			return;
		}

		$filename = pathinfo($mission->getBaseName(), PATHINFO_FILENAME) . ".jpg";

		//Goal size
		$size = [200, 127];

		//See if we have a cached
		$cachePath = BASE_DIR . "/cache/{$size[0]}x{$size[1]}/{$mission->getId()}.jpg";
		if (is_file($cachePath)) {
			//Yep
			header("Content-Length: " . filesize($cachePath));
			header("Content-Type: image/jpeg");

			$response->file($cachePath, $filename, "image/jpg");
			return;
		}

		$tmp = tempnam(sys_get_temp_dir(), "bitmap");
		$im = new Imagick($bitmap);

		//Stretch image to fill the area so we can rescale without much trouble
		//This is the goal size scaled up to the size of the input image
		$minAxis = min($im->getImageWidth() / $size[0], $im->getImageHeight() / $size[1]);
		$fullSize = [$minAxis * $size[0], $minAxis * $size[1]];

		//Offsets so it fills into the center
		$offX = intval(($fullSize[0] - $im->getImageWidth()) / 2);
		$offY = intval(($fullSize[1] - $im->getImageHeight()) / 2);

		//Get the image on a canvas we can deal with
		$canvas = new Imagick();
		$canvas->newImage($fullSize[0], $fullSize[1], new ImagickPixel("#ffffff00"));
		$canvas->compositeImage($im, Imagick::COMPOSITE_DEFAULT, $offX, $offY);
		$canvas->flattenImages();

		//And finally get the output
		$canvas->resizeImage($size[0], $size[1], Imagick::FILTER_LANCZOS, 1, false);
		$canvas->setImageFormat("JPEG");
		$canvas->writeImage($tmp);

		//Spit it out
		$response->file($tmp, $filename, "image/jpg");
	}

	/**
	 * @param \Klein\Response $response
	 * @param Mission $mission
	 */
	private function renderMissionZip(\Klein\Response $response, Mission $mission) {
		$filename = pathinfo($mission->getBaseName(), PATHINFO_FILENAME) . ".zip";

		$files = $mission->getFiles();
		$files = array_filter($files, function($info) {
			//Adding this separately
			if ($info["type"] === "mission" || $info["type"] === "bitmap") {
				return false;
			}
			//Don't download stuff we should already have
			if ($info["official"]) {
				return false;
			}
			return true;
		});

		//Create a zip file to output to the user
		$zipPath = tempnam(sys_get_temp_dir(), "mission");
		$zip = new ZipArchive();
		$zip->open($zipPath, ZipArchive::OVERWRITE | ZipArchive::CREATE);

		//Add mission in a sensible place
		$zip->addFile($mission->getRealPath(), "data/missions/{$mission->getBaseName()}");
		if ($mission->getBitmap() !== null) {
			$zip->addFile($mission->getBitmap()->getRealPath(), "data/missions/{$mission->getBitmap()->getBaseName()}");
		}

		//Add data files
		foreach ($files as $info) {
			$file = $info["path"];
			$zip->addFile(Paths::getRealPath($file), str_replace("~/", "", $file));
		}
		$zip->close();
		unset($zip);

		//Send all the data and clean up
		$response->file($zipPath, $filename, "application/octet-stream");
		unlink($zipPath);
	}

}