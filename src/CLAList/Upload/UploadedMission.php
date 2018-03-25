<?php

namespace CLAList\Upload;

use CLAList\Entity\Interior;
use CLAList\Entity\Mission;

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

		$mission = new Mission($this->mission->getPath());
		foreach ($mission->getInteriors() as $interior) {
			/* @var Interior $interior */
			$closest = self::findClosestFile($this->files, $interior->getGamePath(), "interior");

			if ($closest !== null) {
				if ($interior->getHash() === null) {
					//It's an interior we need, see if we have it
					echo("Found missing interior {$closest->getPath()}\n");
					$this->addInstallFile($closest, $interior->getRealPath());
					$this->addInterior($closest, $this->files);
				} else if ($interior->getHash() !== $closest->getHash()) {
					echo("Found existing interior (with non-matching hash) {$interior->getGamePath()}\n");
					echo("Existing: {$interior->getHash()} closest: {$closest->getHash()}\n");
					//TODO: Upload this interior to a unique path and replace it in the mission
				} else {
					echo("Found existing interior (with matching hash) {$interior->getGamePath()}\n");
				}
			} else {
				if ($interior->getHash() !== null) {
					echo("Found existing interior (with no uploaded counterpart) {$interior->getGamePath()}\n");
				} else {
					echo("Missing interior {$interior->getGamePath()}\n");
					return false;
				}
			}
		}

		return true;
	}

	private function addInterior(UploadedFile $file, array $files) {
		//TODO: Add this interior's textures
		$interior = Interior::find(["gamePath" => $file->getPath()], [$file->getPath()]);

		//Check its textures

		print_r($interior);
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
			return strpos($file->getType(), $type) !== FALSE;
		});
		usort($files, function(UploadedFile $file1, UploadedFile $file2) use ($findPath) {
			/* @var UploadedFile $file1 */
			/* @var UploadedFile $file2 */
			$path1 = $file1->getPath();
			$path2 = $file2->getPath();

			$dist1 = levenshtein($path1, $findPath);
			$dist2 = levenshtein($path2, $findPath);
			if (pathinfo($path1, PATHINFO_FILENAME) === pathinfo($findPath, PATHINFO_FILENAME))
				$dist1 -= 10;
			if (pathinfo($path2, PATHINFO_FILENAME) === pathinfo($findPath, PATHINFO_FILENAME))
				$dist2 -= 10;

			return $dist1 <=> $dist2;
		});

		//Try to find a file with the same pathname
		foreach ($files as $closest) {
			/* @var UploadedFile $closest */
			if (pathinfo($closest->getPath(), PATHINFO_FILENAME) === pathinfo($findPath, PATHINFO_FILENAME)) {
				return $closest;
			}
		}
		//No? Try the same basename
		foreach ($files as $closest) {
			/* @var UploadedFile $closest */
			if (pathinfo($closest->getPath(), PATHINFO_BASENAME) === pathinfo($findPath, PATHINFO_BASENAME)) {
				return $closest;
			}
		}
		//No this is clearly not working
		return null;
	}
}
