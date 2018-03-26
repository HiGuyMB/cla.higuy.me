<?php

namespace CLAList\Upload;

use CLAList\Entity\Interior;
use CLAList\Entity\Mission;
use CLAList\Entity\Shape;
use CLAList\Entity\Texture;

class UploadedMission {
	/**
	 * @var UploadedFile $mission
	 */
	private $mission;
	/**
	 * @var array $files;
	 */
	private $files;
	/**
	 * @var UploadedFile $bitmap
	 */
	private $bitmap;
	/**
	 * @var array $interiors
	 */
	private $interiors;
	/**
	 * @var array $shapes
	 */
	private $shapes;
	/**
	 * @var string $skybox
	 */
	private $skybox;
	/**
	 * @var array $textures
	 */
	private $textures;
	/**
	 * @var array $install
	 */
	private $install;

	public function __construct(UploadedFile $file, array $files) {
		$this->install = [];
		$this->mission = $file;
		$this->files = $files;

		$this->interiors = [];
		$this->shapes = [];
		$this->textures = [];
		$this->skybox = null;

		if (!$this->loadFile()) {
			echo("Could not load mission");
		} else {
			$this->install();
		}
	}

	protected function loadFile() {
		list($finalPath, $finalName) = $this->resolveFinalPath();

		//See if this mission already exists
		if (!$this->hasUniqueHash()) {
			echo("This mission already exists\n");
			return false;
		}

		echo("Found new mission {$this->mission->getName()}\n");
		$this->addInstallFile($this->mission, GetRealPath($finalPath));

		//See if we have an image with the same name
		$this->bitmap = self::findClosestFile($this->files, $this->mission->getPath(), "image");
		if ($this->bitmap !== null) {
			$extension = pathinfo($this->bitmap->getName(), PATHINFO_EXTENSION);

			echo("Found mission image {$this->bitmap->getName()}\n");
			$this->addInstallFile($this->bitmap, GetRealPath("~/data/missions/custom/" . $finalName . "." . $extension));
		} else {
			echo("No mission bitmap found\n");
			return false;
		}

		//Load the mission and extract its info
		$missionData = Mission::loadFileData($this->mission->getPath());

		foreach ($missionData as $data) {
			switch ($data["type"]) {
				case "interior":
					if (!$this->loadInterior($data["data"])) {
						return false;
					}
					break;
				case "skybox":
					if (!$this->loadSkybox($data["data"])) {
						return false;
					}
					break;
				case "shape":
					if (!$this->loadShape($data["data"])) {
						return false;
					}
					break;
				default:
					break;
			}
		}

		return true;
	}

	private function loadInterior($gamePath) {
		$localFile = self::findClosestFile($this->files, $gamePath, "interior");

		//Check if we have it already
		if (in_array([$localFile, $gamePath], $this->interiors)) {
			return true;
		}

		$dbFile = Interior::findByGamePath($gamePath, false);
		if ($dbFile !== null) {
			if ($localFile === null) {
				//Nothing new here
				return true;
			}
			//Check hashes and see if they are the same
			if ($dbFile->getHash() === $localFile->getHash()) {
				//Yep already got it, ignore
				return true;
			}
			if ($dbFile->isConstructed()) {
				//We're already installing this one
				//TODO: What if the previous mission actually failed so we're not installing it?
				return true;
			}
			//TODO: Handle this
			echo("Interior conflict: {$dbFile->getGamePath()} hash is {$dbFile->getHash()} on db and {$localFile->getHash()} locally\n");
			return true;
		}
		if ($localFile === null) {
			echo("Missing interior: $gamePath\n");
			return false;
		}

		//Need to install the local file
		$this->addInstallFile($localFile, GetRealPath($gamePath));
		$this->interiors[] = [$localFile, $gamePath];
		Interior::construct([$gamePath]);

		//And check all its textures
		$textures = Interior::loadFileTextures($localFile->getPath());
		foreach ($textures as $texture) {
			$basePath = pathinfo($gamePath, PATHINFO_DIRNAME);
			if (!$this->loadTexture($basePath, $texture)) {
				return false;
			}
		}
		return true;
	}

	private function loadShape($gamePath) {
		$localFile = self::findClosestFile($this->files, $gamePath, "shape");

		//Check if we have it already
		if (in_array([$localFile, $gamePath], $this->shapes)) {
			return true;
		}

		$dbFile = Shape::findByGamePath($gamePath, false);

		if ($dbFile !== null) {
			if ($localFile === null) {
				//Nothing new here
				return true;
			}
			//Check hashes and see if they are the same
			if ($dbFile->getHash() === $localFile->getHash()) {
				//Yep already got it, ignore
				return true;
			}
			if ($dbFile->isConstructed()) {
				//We're already installing this one
				//TODO: What if the previous mission actually failed so we're not installing it?
				return true;
			}
			//TODO: Handle this
			echo("Shape conflict: {$dbFile->getGamePath()} hash is {$dbFile->getHash()} on db and {$localFile->getHash()} locally\n");
			return true;
		}
		if ($localFile === null) {
			echo("Missing shape: $gamePath\n");
			return false;
		}

		//Need to install the local file
		$this->addInstallFile($localFile, GetRealPath($gamePath));
		$this->shapes[] = [$localFile, $gamePath];
		Shape::construct([$gamePath]);

		//And check all its textures
		$textures = Shape::loadFileTextures($localFile->getPath());
		foreach ($textures as $texture) {
			//Some textures like Material.001 don't exist because they're temps
			// from the blender exporter. So ignore them if we don't have them
			$care = preg_match('/\.\d+$/', $texture) !== 1;

			$basePath = pathinfo($gamePath, PATHINFO_DIRNAME);
			if (!$this->loadTexture($basePath, $texture) && $care) {
				return false;
			}
		}
		return true;
	}

	private function loadSkybox($gamePath) {
		$file = self::findClosestFile($this->files, $gamePath, "skybox");

		return true;
	}

	private function loadTexture($basePath, $texture) {
		$localFile = self::findClosestFile($this->files, $texture, "image");

		//Check if we have it already
		if (in_array([$localFile, $basePath, $texture], $this->textures)) {
			return true;
		}

		$dbFile = self::findDBTexture($basePath, $texture);

		if ($dbFile !== null) {
			if ($localFile === null) {
				//Nothing new here
				return true;
			}
			//Check hashes and see if they are the same
			if ($dbFile->getHash() === $localFile->getHash()) {
				//Yep already got it, ignore
				return true;
			}
			if ($dbFile->isConstructed()) {
				//We're already installing this one
				//TODO: What if the previous mission actually failed so we're not installing it?
				return true;
			}
			//TODO: Handle this
			echo("Texture conflict: {$dbFile->getGamePath()} hash is {$dbFile->getHash()} on db and {$localFile->getHash()} locally\n");
			return true;
		}
		if ($localFile === null) {
			echo("Missing texture: $basePath/$texture\n");
			return false;
		}

		$texPath = $basePath . "/" . $localFile->getName();

		//Need to install the local file
		$this->addInstallFile($localFile, GetRealPath($texPath));
		$this->textures[] = [$localFile, $basePath, $texture];

		Texture::construct([$texPath]);

		return true;
	}

	private static function findDBTexture($base, $texture) {
		$candidates = Texture::getCandidates($base, $texture);
		//See if any of those resolve to a database path
		foreach ($candidates as $candidate) {
			$dbTexture = Texture::findByGamePath($candidate, false);
			if ($dbTexture !== null) {
				return $dbTexture;
			}
		}
		return null;
	}

	/**
	 * Add a file to the list of files to install
	 * @param UploadedFile $file File object
	 * @param string       $installPath Where to install it
	 */
	private function addInstallFile(UploadedFile $file, $installPath) {
		$this->install[] = [$file, $installPath];
	}

	/**
	 * Install this mission, copying all its files
	 */
	public function install() {
		foreach ($this->install as list($file, $installPath)) {
			/* @var UploadedFile $file */
			echo("Installing " . $file->getPath() . " into " . $installPath . "\n");
		}
	}

	/**
	 * Figure an install path that is unique
	 * @return array [path, name]
	 */
	private function resolveFinalPath() {
		$name = pathinfo($this->mission->getName(), PATHINFO_FILENAME);

		//Final path
		$finalName = $name;
		preg_replace('/[^a-z0-9_ \-.]/s', '', $finalName);
		$finalPath = "~/data/missions/custom/" . $finalName . ".mis";

		$i = 0;
		while (is_file(GetRealPath($finalPath))) {
			//We already have a mission with this name.
			$finalName = $name . "_" . $i;
			preg_replace('/[^a-z0-9_ \-.]/s', '', $finalName);

			$finalPath = "~/data/missions/custom/" . $finalName . ".mis";
			$i ++;
		}

		return [$finalPath, $finalName];
	}

	/**
	 * Check if we already have this mission
	 * @return bool True if we don't have it
	 */
	private function hasUniqueHash() {
		$em = GetEntityManager();

		$builder = $em->createQueryBuilder();
		$query = $builder
			->select('COUNT(m.id)')
			->from('CLAList\Entity\Mission', 'm')
			->where('m.hash = :hash')
			->setParameter(':hash', $this->mission->getHash())
			->getQuery()
		;
		$missions = $query->getSingleScalarResult();

		return $missions == 0;
	}

	/**
	 * @param array $files Possible files
	 * @param string $findPath Path to compare against
	 * @param string $type Optional: only check files matching this type
	 * @return UploadedFile|null
	 */
	private static function findClosestFile($files, $findPath, $type = "") {
		$files = array_filter($files, function(UploadedFile $file) use($type) {
			return stripos($file->getType(), $type) !== FALSE;
		});
		usort($files, function(UploadedFile $file1, UploadedFile $file2) use ($findPath) {
			/* @var UploadedFile $file1 */
			/* @var UploadedFile $file2 */
			$path1 = $file1->getPath();
			$path2 = $file2->getPath();

			$dist1 = levenshtein($path1, $findPath);
			$dist2 = levenshtein($path2, $findPath);
			if (strcasecmp(pathinfo($path1, PATHINFO_FILENAME), pathinfo($findPath, PATHINFO_FILENAME)) === 0)
				$dist1 -= 10;
			if (strcasecmp(pathinfo($path2, PATHINFO_FILENAME), pathinfo($findPath, PATHINFO_FILENAME)) === 0)
				$dist2 -= 10;

			return $dist1 <=> $dist2;
		});

		//Try to find a file with the same pathname
		foreach ($files as $closest) {
			/* @var UploadedFile $closest */
			if (strcasecmp(pathinfo($closest->getPath(), PATHINFO_FILENAME), pathinfo($findPath, PATHINFO_FILENAME)) === 0) {
				return $closest;
			}
		}
		//No? Try the same basename
		foreach ($files as $closest) {
			/* @var UploadedFile $closest */
			if (strcasecmp(pathinfo($closest->getPath(), PATHINFO_BASENAME), pathinfo($findPath, PATHINFO_BASENAME)) === 0) {
				return $closest;
			}
		}
		//No this is clearly not working
		return null;
	}
}
