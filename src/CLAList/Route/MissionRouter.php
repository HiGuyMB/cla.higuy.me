<?php

namespace CLAList\Route;

use CLAList\Model\Entity\Field;
use CLAList\Model\Entity\Mission;
use CLAList\Model\Entity\Rating;
use CLAList\Paths;
use CLAList\Router;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;
use Imagick;
use ImagickPixel;
use ZipArchive;

class MissionRouter extends Router {
	public function register() {
		$this->klein->respond('GET', '/missions', function(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
			return $this->twig->render("MissionList.twig", [
			]);
		});

		$this->klein->respond('GET', '/missions/edit', function(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
			return $this->twig->render("EditMissions.twig", [
			]);
		});

		$this->klein->respond('GET', '/api/v1/missions/[:id]/[files|bitmap|zip:action]', [$this, 'get']);
		$this->klein->respond('POST', '/api/v1/missions/[:id]/[:action]', [$this, 'post']);
		$this->klein->respond('GET', '/api/v1/missions', [$this, 'renderMissionList']);
		$this->klein->respond('GET', '/api/v1/missions/all', [$this, 'renderFullMissionList']);
	}

	/**
	 * @param \Klein\Request         $request
	 * @param \Klein\Response        $response
	 * @param \Klein\ServiceProvider $service
	 * @param \Klein\App             $app
	 */
	public function get(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		$service->validateParam('id')->notNull();
		$id = $request->param('id');

		switch ($request->action) {
			case "files":
				$this->renderMissionFiles($response, $id);
				break;
			case "bitmap":
				$this->renderMissionBitmap($response, $id);
				break;
			case "zip":
				$this->renderMissionZip($response, $id);
				break;
		}
	}

	/**
	 * @param \Klein\Request         $request
	 * @param \Klein\Response        $response
	 * @param \Klein\ServiceProvider $service
	 * @param \Klein\App             $app
	 */
	public function post(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		$service->validateParam('id')->notNull()->isChars("0-9");
		$id = $request->param('id');

		/* @var Mission $mission */
		$mission = Mission::find(["id" => $id]);
		$user = $request->headers()->get('X_FORWARDED_FOR', $request->headers()->get('REMOTE_ADDR'));

		switch ($request->action) {
			case "rate":
				$this->rateMission($request, $response, $service, $user, $mission);
				break;
			case "update":
				$this->updateMission($request, $response, $service, $user, $mission);
				break;
		}
	}

	/**
	 * @param string $id
	 * @return Mission
	 */
	private function resolveMissionId(string $id) {
		if ($id === "random") {
			$em = GetEntityManager();
			$builder = $em->createQueryBuilder();
			$query = $builder
				->select('m.id')
				->from('CLAList\Model\Entity\Mission', 'm')
				->orderBy('RAND()', 'ASC')
				->getQuery()
				->setMaxResults(1)
			;
			$id = $query->getSingleScalarResult();
		}
		$mission = Mission::find(["id" => $id]);
		return $mission;
	}

	/**
	 * @param \Klein\Request         $request
	 * @param \Klein\Response        $response
	 * @param \Klein\ServiceProvider $service
	 * @param \Klein\App             $app
	 */
	public function renderMissionList(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		$em = GetEntityManager();

		$missions = [];
		try {
			$rsm = new ResultSetMapping();
			$rsm->addScalarResult('id', 'id', 'integer');
			$rsm->addScalarResult('gems', 'gems', 'integer');
			$rsm->addScalarResult('easter_egg', 'egg', 'boolean');
			$rsm->addScalarResult('modification', 'modification');
			$rsm->addScalarResult('game_type', 'gameType');
			$rsm->addScalarResult('base_name', 'baseName');
			$rsm->addScalarResult('add_time', 'addTime');
			$rsm->addScalarResult('fname', 'fname');
			$rsm->addScalarResult('fdesc', 'fdesc');
			$rsm->addScalarResult('fartist', 'fartist');
			$rsm->addScalarResult('fdiff', 'fdiff');
			$rsm->addScalarResult('bitmap', 'bitmap');
			$rsm->addScalarResult('rating', 'rating', 'float');
			$rsm->addScalarResult('weight', 'weight', 'integer');
			$query = $em->createNativeQuery("
				SELECT m.id           AS id,
				       m.gems         AS gems,
				       m.easter_egg   AS easter_egg,
				       m.modification AS modification,
				       m.game_type    AS game_type,
				       m.base_name    AS base_name,
				       m.add_time     AS add_time,
				       IF(f1.value IS NULL, '', f1.value) AS fname,
				       IF(f2.value IS NULL, '', f2.value) AS fdesc,
				       IF(f3.value IS NULL, '', f3.value) AS fartist,
				       IF(f4.value IS NULL, NULL, f4.value) AS fdiff,
				       IF(b.base_name IS NULL, NULL, b.base_name) AS bitmap,
				       IF(r.weighted IS NULL, -1, r.weighted) AS rating,
				       IF(r.weight IS NULL, 0, r.weight) AS weight
				FROM uxwba_missions m
				       INNER JOIN uxwba_mission_fields f1 ON m.id = f1.mission_id AND (f1.name = :name)
				       LEFT JOIN uxwba_mission_fields f2 ON m.id = f2.mission_id AND (f2.name = :desc)
				       LEFT JOIN uxwba_mission_fields f3 ON m.id = f3.mission_id AND (f3.name = :artist)
				       LEFT JOIN uxwba_mission_fields f4 ON m.id = f4.mission_id AND (f4.name = :diff)
				       LEFT JOIN uxwba_textures b ON m.bitmap_id = b.id
				       LEFT JOIN (
				           SELECT
				               SUM(r.value * r.weight) / SUM(r.weight) AS weighted,
				               SUM(r.weight) AS weight,
				               mission_id
				           FROM uxwba_mission_ratings r
				           GROUP BY r.mission_id) AS r
			           ON m.id = r.mission_id
			", $rsm)->setParameters([":name" => "name", ":desc" => "desc", ":artist" => "artist", ":diff" => "_difficulty"]);

			$results = $query->getArrayResult();
			foreach ($results as $result) {
				$missions[] = [
					"id" => $result["id"],
					"name" => $result["fname"],
					"desc" => $result["fdesc"],
					"artist" => $result["fartist"],
					"difficulty" => $result["fdiff"],
					"modification" => $result["modification"],
					"gameType" => $result["gameType"],
					"baseName" => $result["baseName"],
					"addTime" => $result["addTime"],
					"gems" => $result["gems"],
					"egg" => $result["egg"],
					"bitmap" => $result["bitmap"],
					"rating" => $result["rating"],
					"weight" => $result["weight"]
				];
			}

		} catch (Exception $e) {
			echo($e->getMessage() . "\n");
			echo($e->getTraceAsString());
		}

		$response->json($missions);
	}

	/**
	 * @param \Klein\Request         $request
	 * @param \Klein\Response        $response
	 * @param \Klein\ServiceProvider $service
	 * @param \Klein\App             $app
	 */
	public function renderFullMissionList(\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, \Klein\App $app) {
		$em = GetEntityManager();

		$missions = [];
		try {
			$rsm = new ResultSetMapping();
			$rsm->addScalarResult('id', 'id', 'integer');
			$rsm->addScalarResult('gems', 'gems', 'integer');
			$rsm->addScalarResult('easter_egg', 'egg', 'boolean');
			$rsm->addScalarResult('modification', 'modification');
			$rsm->addScalarResult('game_type', 'gameType');
			$rsm->addScalarResult('base_name', 'baseName');
			$rsm->addScalarResult('add_time', 'addTime');
			$rsm->addScalarResult('fname', 'fname');
			$rsm->addScalarResult('fdesc', 'fdesc');
			$rsm->addScalarResult('fartist', 'fartist');
			$rsm->addScalarResult('fdiff', 'fdiff');
			$rsm->addScalarResult('bitmap', 'bitmap');
			$rsm->addScalarResult('rating', 'rating', 'float');
			$rsm->addScalarResult('weight', 'weight', 'integer');
			$query = $em->createNativeQuery("
				SELECT m.id           AS id,
				       m.gems         AS gems,
				       m.easter_egg   AS easter_egg,
				       m.modification AS modification,
				       m.game_type    AS game_type,
				       m.base_name    AS base_name,
				       m.add_time     AS add_time,
				       IF(f1.value IS NULL, '', f1.value) AS fname,
				       IF(f2.value IS NULL, '', f2.value) AS fdesc,
				       IF(f3.value IS NULL, '', f3.value) AS fartist,
				       IF(f4.value IS NULL, NULL, f4.value) AS fdiff,
				       IF(b.base_name IS NULL, NULL, b.base_name) AS bitmap,
				       IF(r.weighted IS NULL, -1, r.weighted) AS rating,
				       IF(r.weight IS NULL, 0, r.weight) AS weight
				FROM uxwba_missions m
				       INNER JOIN uxwba_mission_fields f1 ON m.id = f1.mission_id AND (f1.name = :name)
				       LEFT JOIN uxwba_mission_fields f2 ON m.id = f2.mission_id AND (f2.name = :desc)
				       LEFT JOIN uxwba_mission_fields f3 ON m.id = f3.mission_id AND (f3.name = :artist)
				       LEFT JOIN uxwba_mission_fields f4 ON m.id = f4.mission_id AND (f4.name = :diff)
				       LEFT JOIN uxwba_textures b ON m.bitmap_id = b.id
				       LEFT JOIN (
				           SELECT
				               SUM(r.value * r.weight) / SUM(r.weight) AS weighted,
				               SUM(r.weight) AS weight,
				               mission_id
				           FROM uxwba_mission_ratings r
				           GROUP BY r.mission_id) AS r
			           ON m.id = r.mission_id
			", $rsm)->setParameters([":name" => "name", ":desc" => "desc", ":artist" => "artist", ":diff" => "_difficulty"]);

			$frsm = new ResultSetMapping();
			$frsm->addScalarResult('name', 'name');
			$frsm->addScalarResult('value', 'value');

			$results = $query->getArrayResult();
			foreach ($results as $result) {
				$query = $em->createNativeQuery("
					SELECT f.name as name, f.value as value
					FROM uxwba_mission_fields f
		            WHERE f.mission_id = :id
				", $frsm)->setParameters([":id" => $result["id"]]);
				$fields = $query->getArrayResult();

				$missions[] = [
					"id" => $result["id"],
					"name" => $result["fname"],
					"desc" => $result["fdesc"],
					"artist" => $result["fartist"],
					"difficulty" => $result["fdiff"],
					"modification" => $result["modification"],
					"gameType" => $result["gameType"],
					"baseName" => $result["baseName"],
					"addTime" => $result["addTime"],
					"gems" => $result["gems"],
					"egg" => $result["egg"],
					"bitmap" => $result["bitmap"],
					"rating" => $result["rating"],
					"weight" => $result["weight"],
					"fields" => $fields
				];
			}

		} catch (Exception $e) {
			echo($e->getMessage() . "\n");
			echo($e->getTraceAsString());
		}

		$response->json($missions);
	}
	/**
	 * @param \Klein\Response $response
	 * @param int $id
	 */
	public function renderMissionFiles(\Klein\Response $response, $id) {
		$mission = $this->resolveMissionId($id);

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
	 * @param int $id
	 */
	private function renderMissionBitmap(\Klein\Response $response, int $id) {
		//Goal size
		$size = [200, 127];

		//See if we have a cached
		$cachePath = BASE_DIR . "/cache/{$size[0]}x{$size[1]}/$id.jpg";

		$useCache = is_file($cachePath);
		if ($useCache) {
			//Check modify time
			$cstat = stat($cachePath);

			//Yep
			$response->header('Content-type', 'image/jpg');
			$response->header('Content-Disposition', 'attachment; filename="' . $id . '.jpg"');
			$response->header('Content-Length', filesize($cachePath));
			$response->header('Cache-Control', 'max-age=86400');
			$response->body(readfile($cachePath));
			return;
		}

		$mission = $this->resolveMissionId($id);

		if ($mission->getBitmap() === null) {
			$response->code(404);
			return;
		}

		$bitmap = $mission->getBitmap()->getRealPath();

		if (!is_file($bitmap)) {
			$response->code(404);
			return;
		}

		$filename = pathinfo($mission->getBaseName(), PATHINFO_FILENAME) . ".jpg";

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
		$canvas->writeImage($cachePath);

		//Spit it out
		$response->header("Cache-Control", "max-age=86400");
		$response->file($cachePath, $filename, "image/jpg");
	}

	/**
	 * @param \Klein\Response $response
	 * @param string $id
	 */
	private function renderMissionZip(\Klein\Response $response, string $id) {
		$mission = $this->resolveMissionId($id);

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
		if ($mission->getPreview() !== null) {
			$zip->addFile($mission->getPreview()->getRealPath(), "data/missions/{$mission->getPreview()->getBaseName()}");
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

	private function rateMission(
		\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, string $user, Mission $mission) {
		$service->validateParam('direction')->notNull();
		$direction = $request->param("direction");

		$value = ($direction > 0 ? 5 : ($direction < 0 ? 1 : 3));

		$rating = Rating::find(["user" => $user, "mission" => $mission], [$user, $mission, $value, 1]);
		$rating->setValue($value);

		GetEntityManager()->flush($rating);

		$response->json([
			"rating" => $mission->getRating(),
			"weight" => $mission->getRatingWeight()
		]);
	}

	private function updateMission(
		\Klein\Request $request, \Klein\Response $response, \Klein\ServiceProvider $service, string $user, Mission $mission) {
		$service->validateParam('key')->notNull();
		$service->validateParam('fields')->notNull();

		if ($request->param("key") !== "pq where") {
			$response->code(404);
			return;
		}

		$fields = $request->param("fields");

		foreach ($fields as $name => $value) {
			$mission->setFieldValue($name, $value);
		}

		GetEntityManager()->flush();

		$response->code(200);
		$response->json($mission->getFieldValue("artist"));
	}
}