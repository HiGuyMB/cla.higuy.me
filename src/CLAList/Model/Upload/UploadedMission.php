<?php

namespace CLAList\Model\Upload;

use CLAList\Model\Entity\AbstractGameEntity;
use CLAList\Model\Entity\Interior;
use CLAList\Model\Entity\Mission;
use CLAList\Model\Entity\Shape;
use CLAList\Model\Entity\Skybox;
use CLAList\Model\Entity\Texture;
use CLAList\Model\Filesystem;
use CLAList\Paths;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

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
	 * @var UploadedFile $preview
	 */
	private $preview;
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
	/**
	 * @var string gamePath
	 */
	private $gamePath;

	public function __construct(UploadedFile $file, array $files) {
		$this->install = [];
		$this->mission = $file;
		$this->files = $files;

		$this->interiors = [];
		$this->shapes = [];
		$this->textures = [];
		$this->skybox = null;
		$this->bitmap = null;
		$this->preview = null;
	}

	public function loadFile() {
		list($finalPath, $finalName) = $this->resolveFinalPath();
		$this->gamePath = $finalPath;

		//See if this mission already exists
		if (!$this->hasUniqueHash()) {
			echo("This mission already exists\n");
			return false;
		}

		echo("Found new mission {$this->mission->getName()}\n");
		$this->addInstallFile($this->mission, Paths::getRealPath($finalPath));

		//See if we have an image with the same name
		$this->bitmap = UploadedFile::findClosestFile($this->files, $this->mission->getName(), "image");
		if ($this->bitmap !== null) {
			$extension = pathinfo($this->bitmap->getName(), PATHINFO_EXTENSION);

			echo("Found mission image {$this->bitmap->getName()}\n");
			$this->addInstallFile($this->bitmap, Paths::getRealPath("~/data/missions/custom/" . $finalName . "." . $extension));
		} else {
			echo("No mission bitmap found\n");
			return false;
		}

		//See if we have a preview image
		$this->preview = UploadedFile::findClosestFile($this->files, pathinfo($this->mission->getName(), PATHINFO_FILENAME) . ".prev.png", "image");
		if ($this->preview !== null) {
			$extension = pathinfo($this->preview->getName(), PATHINFO_EXTENSION);

			echo("Found mission preview {$this->preview->getName()}\n");
			$this->addInstallFile($this->preview, Paths::getRealPath("~/data/missions/custom/" . $finalName . ".prev." . $extension));
		} else {
			echo("No preview found\n");
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
		$localFile = UploadedFile::findClosestFile($this->files, $gamePath, "interior");

		//Check if we have it already
		if (in_array([$localFile, $gamePath], $this->interiors)) {
			return true;
		}

		$dbFile = Interior::findByGamePath($gamePath, false);
		if ($dbFile !== null) {
			return $this->checkConflict($localFile, $dbFile);
		}
		if ($localFile === null) {
			echo("Missing interior: $gamePath\n");
			return false;
		}

		//Need to install the local file
		$this->addInstallFile($localFile, Paths::getRealPath($gamePath));
		$this->interiors[] = [$localFile, $gamePath];

		//And check all its textures
		try {
			$textures = Interior::loadFileTextures($localFile->getPath());
		} catch (\Exception $e) {
			return false;
		}

		foreach ($textures as $texture) {
			$basePath = pathinfo($gamePath, PATHINFO_DIRNAME);
			if (!$this->loadTexture($basePath, $texture, true)) {
				return false;
			}
		}
		return true;
	}

	private function loadShape($gamePath) {
		$localFile = UploadedFile::findClosestFile($this->files, $gamePath, "shape");

		//Check if we have it already
		if (in_array([$localFile, $gamePath], $this->shapes)) {
			return true;
		}

		$dbFile = Shape::findByGamePath($gamePath, false);

		if ($dbFile !== null) {
			return $this->checkConflict($localFile, $dbFile);
		}
		if ($localFile === null) {
			echo("Missing shape: $gamePath\n");
			return false;
		}

		//Need to install the local file
		$this->addInstallFile($localFile, Paths::getRealPath($gamePath));
		$this->shapes[] = [$localFile, $gamePath];

		//And check all its textures
		try {
			$textures = Shape::loadFileTextures($localFile->getPath());
		} catch (\Exception $e) {
			echo($e->getMessage() . "\n");
			return false;
		}
		foreach ($textures as $texture) {
			//Some textures like Material.001 don't exist because they're temps
			// from the blender exporter. So ignore them if we don't have them
			$care = preg_match('/\.\d+$/', $texture) !== 1;

			$basePath = pathinfo($gamePath, PATHINFO_DIRNAME);
			if (!$this->loadTexture($basePath, $texture, false, $localFile->getParent()->getContents()) && $care) {
				return false;
			}
		}
		return true;
	}

	private function loadSkybox($gamePath) {
		$localFile = UploadedFile::findClosestFile($this->files, $gamePath, "skybox");

		$dbFile = Skybox::findByGamePath($gamePath, false);

		if ($dbFile !== null) {
			return $this->checkConflict($localFile, $dbFile);
		}
		if ($localFile === null) {
			echo("Missing skybox: $gamePath\n");
			return false;
		}

		$this->addInstallFile($localFile, Paths::getRealPath($gamePath));

		$textures = Skybox::loadFileTextures($localFile->getPath());
		foreach ($textures as $texture) {
			$basePath = pathinfo($gamePath, PATHINFO_DIRNAME);
			if (!$this->loadTexture($basePath, $texture, false, $localFile->getParent()->getContents())) {
				return false;
			}
		}
		return true;
	}

	private function loadTexture($basePath, $texture, $recursive = true, array $fileGroup = null) {
		$fileGroup = $fileGroup ?? $this->files;
		$localFile = UploadedFile::findClosestFile($fileGroup, $texture, "image");

		//Check if we have it already
		if (in_array([$localFile, $basePath, $texture], $this->textures)) {
			return true;
		}

		$dbFile = self::findDBTexture($basePath, $texture, $recursive);
		if ($dbFile !== null) {
			$check = $this->checkConflict($localFile, $dbFile);
			if ($check) {
				return true;
			}
			if (!$recursive) {
				return false;
			}
			//Try without recursive
			$dbFile = self::findDBTexture($basePath, $texture, false);
			if ($dbFile !== null) {
				$check = $this->checkConflict($localFile, $dbFile);
				if (!$check) {
					echo("Could not resolve it\n");
				}
				return $check;
			}
		}
		if ($localFile === null) {
			echo("Missing texture: $basePath/$texture\n");
			return false;
		}

		$texPath = $basePath . "/" . $localFile->getName();

		$candidates = Texture::getQualityCandidates($texPath);
		foreach ($candidates as $candidate) {
			$cFile = UploadedFile::findClosestFile($this->files, $candidate, "image");

			if ($cFile !== null) {
				//Need to install the local file
				$this->addInstallFile($cFile, Paths::getRealPath($candidate));
			}
		}
		$this->textures[] = [$localFile, $basePath, $texture];

		return true;
	}

	/**
	 * @param UploadedFile|null $localFile
	 * @param AbstractGameEntity|null $dbFile
	 * @return bool
	 */
	private function checkConflict($localFile, $dbFile): bool {
		if ($localFile === null) {
			//Nothing new here
			return true;
		}
		if ($dbFile === null) {
			//Can't conflict if it doesn't exist
			return true;
		}
		//Check hashes and see if they are the same
		if ($dbFile->getHash() === $localFile->getHash()) {
			//Yep already got it, ignore
			return true;
		}
		//TODO: Handle this
		echo("Conflict: {$dbFile->getGamePath()} hash is {$dbFile->getHash()} on db and {$localFile->getHash()} locally\n");
		return false;
	}

	private static function findDBTexture($base, $texture, $recursive) {
		$candidates = Texture::getCandidates($base, $texture, $recursive);
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
			echo("Installing " . $file->getRelativePath() . " into " . Paths::getGamePath($installPath) . "\n");
			if (!Filesystem::copy($file->getPath(), $installPath)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Install this mission, copying all its files
	 */
	public function dryInstall() {
		foreach ($this->install as list($file, $installPath)) {
			/* @var UploadedFile $file */
			echo("Would install " . $file->getRelativePath() . " into " . Paths::getGamePath($installPath) . "\n");
		}
	}

	/**
	 * Get a list of all files to be installed
	 * @return array[string => string]
	 */
	public function getInstallFiles() {
		return $this->install;
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
		while ($this->missionExists($finalPath)) {
			//We already have a mission with this name.
			$finalName = $name . "_" . $i;
			preg_replace('/[^a-z0-9_ \-.]/s', '', $finalName);

			$finalPath = "~/data/missions/custom/" . $finalName . ".mis";
			$i ++;
		}

		return [$finalPath, $finalName];
	}

	private function missionExists($gamePath) {
		//What about in the file system?
		$realPath = Paths::getRealPath($gamePath);
		if (is_file($realPath)) {
			return true;
		}

		//What about in the db?
		$mission = Mission::findByGamePath($gamePath, false);
		if ($mission !== null) {
			return true;
		}

		//What about by basename?
		$basename = pathinfo($gamePath, PATHINFO_BASENAME);
		$mission = Mission::find(["baseName" => $basename], [], false);
		if ($mission !== null) {
			return true;
		}

		//Good
		return false;
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
			->from('CLAList\Model\Entity\Mission', 'm')
			->where('m.hash = :hash')
			->setParameter(':hash', $this->mission->getHash())
			->getQuery()
		;
		try {
			$missions = $query->getSingleScalarResult();
		} catch (NoResultException $e) {
			//Really can never happen
			return true;
		} catch (NonUniqueResultException $e) {
			//How the hell
			return true;
		}

		return $missions == 0;
	}

	/**
	 * Get the installed .mis file's game path
	 * @return string
	 */
	public function getGamePath(): string {
		return $this->gamePath;
	}
}
