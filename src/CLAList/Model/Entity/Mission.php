<?php

namespace CLAList\Model\Entity;


use CLAList\Model\EnumGameType;
use CLAList\Model\EnumModification;
use CLAList\Paths;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;

use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;


use Doctrine\ORM\Mapping\Column;

/**
 * @Entity
 * @Table(name="uxwba_missions")
 */
class Mission extends AbstractGameEntity {
	/** @Column(type="string", name="modification") */
	private $modification;
	/** @Column(type="EnumGameType", name="game_type") */
	private $gameType;
	/** @Column(type="integer") */
	private $gems;
	/** @Column(type="boolean", name="easter_egg") */
	private $easterEgg;
	/**
	 * @OneToMany(targetEntity="Field", mappedBy="mission", cascade={"persist", "remove", "detach"})
	 */
	private $fields;
	/**
	 * @ManyToOne(targetEntity="Texture", cascade={"persist", "remove", "detach"})
	 * @JoinColumn(name="bitmap_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
	 */
	private $bitmap;
	/**
	 * @ManyToMany(targetEntity="GameMode", cascade={"persist"})
	 * @JoinTable(name="uxwba_mission_game_modes",
	 *     joinColumns={@JoinColumn(name="mission_id", referencedColumnName="id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@JoinColumn(name="game_mode_id", referencedColumnName="id", onDelete="CASCADE")})
	 * )
	 */
	private $gameModes;
	/**
	 * @ManyToMany(targetEntity="Interior", cascade={"persist", "detach"})
	 * @JoinTable(name="uxwba_mission_interiors",
	 *     joinColumns={@JoinColumn(name="mission_id", referencedColumnName="id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@JoinColumn(name="interior_id", referencedColumnName="id", onDelete="CASCADE")}
	 * )
	 */
	private $interiors;
	/**
	 * @ManyToMany(targetEntity="Shape", cascade={"persist", "detach"})
	 * @JoinTable(name="uxwba_mission_shapes",
	 *     joinColumns={@JoinColumn(name="mission_id", referencedColumnName="id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@JoinColumn(name="shape_id", referencedColumnName="id", onDelete="CASCADE")}
	 * )
	 */
	private $shapes;
	/**
	 * @ManyToOne(targetEntity="Skybox", cascade={"persist"})
	 * @JoinColumn(name="skybox_id", referencedColumnName="id")
	 */
	private $skybox;

	function __construct($gamePath, $realPath = null) {
		parent::__construct($gamePath, $realPath);
		$this->fields = new ArrayCollection();
		$this->gameModes = new ArrayCollection();
		$this->interiors = new ArrayCollection();
		$this->shapes = new ArrayCollection();
		$this->bitmap = null;

		$this->loadFile();
	}

	public function loadFile() {
		$this->interiors->clear();
		$this->shapes->clear();
		$this->gems = 0;
		$this->easterEgg = false;
		$this->skybox = null;

		$parts = self::loadFileData($this->getRealPath());
		foreach ($parts as $part) {
			switch ($part["type"]) {
				case "field":
					list($key, $value) = $part["data"];
					if (!$this->hasField($key)) {
						$field = new Field($this, $key, $value);
						$this->fields->add($field);
					}
					break;
				case "interior":
					$this->addInterior($part["data"]);
					break;
				case "gem":
					$this->gems ++;
					break;
				case "egg":
					$this->easterEgg = true;
					break;
				case "skybox":
					//Already set it and this is the fallback
					if ($this->getSkybox() !== null)
						continue;

					$this->loadSkybox($part["data"]);

					break;
				case "shape":
					$this->addShape($part["data"]);
					break;
			}
		}

		if ($this->getSkybox() === null) {
			//No skybox, just use the default
			$defaultPath = "~/data/skies/sky_day.dml";
			$this->loadSkybox($defaultPath);
		}

		$this->gameModes->clear();
		if ($this->hasField("gameMode")) {
			$modes = explode(" ", $this->getField("gameMode")->getValue());
			foreach ($modes as $name) {
				$this->addGameMode($name);
			}
		} else {
			$this->addGameMode("null");
		}

		//Try to glean this
		$this->modification = EnumModification::guessModification($this);
		$this->gameType = EnumGameType::guessGameType($this);

		//Try to find an image in the same dir
		$image = Texture::resolve(pathinfo($this->getRealPath(), PATHINFO_DIRNAME), pathinfo($this->getBaseName(), PATHINFO_FILENAME));

		if ($image !== null) {
			$gamePath = Paths::getGamePath($image);

			//Make a texture object for us
			$this->bitmap = Texture::findByGamePath($gamePath);
		}
	}

	/**
	 * Add a game mode to this mission's list of game modes
	 * @param string $name
	 */
	public function addGameMode($name) {
		$mode = GameMode::find(["name" => $name], [$name]);
		$this->gameModes->add($mode);
	}

	/**
	 * Add an interior to the mission's interior list.
	 * Interiors aren't duplicated so this will not add any duplicates.
	 * @param string $gamePath
	 */
	public function addInterior($gamePath) {
		$gamePath = $this->resolvePath($gamePath);
		if (!is_file(Paths::getRealPath($gamePath))) {
			//echo("Missing interior: $gamePath\n");
		}

		//If we don't already have this interior
		if (!$this->interiors->exists(function($index, Interior $interior) use($gamePath) {
			return Paths::compare($interior->getGamePath(), $gamePath);
		})) {
			$interior = Interior::findByGamePath($gamePath);
			$this->interiors->add($interior);
		}
	}

	/**
	 * Add an shape to the mission's shape list.
	 * Shapes aren't duplicated so this will not add any duplicates.
	 * @param string $gamePath
	 */
	public function addShape($gamePath) {
		$gamePath = $this->resolvePath($gamePath);
		if (!is_file(Paths::getRealPath($gamePath))) {
			//echo("Missing shape: $gamePath\n");
		}

		//If we don't already have this shape
		if (!$this->shapes->exists(function($index, Shape $shape) use($gamePath) {
			return Paths::compare($shape->getGamePath(), $gamePath);
		})) {
			$shape = Shape::findByGamePath($gamePath);
			$this->shapes->add($shape);
		}
	}

	/**
	 * Set the mission's skybox object, loading from the given file.
	 * If the file does not exist, the skybox will be set to NULL
	 * @param string $gamePath
	 */
	public function loadSkybox($gamePath) {
		$gamePath = $this->resolvePath($gamePath);
		if (!is_file(Paths::getRealPath($gamePath))) {
//			echo("Missing skybox: $gamePath\n");
		}

		$skybox = Skybox::findByGamePath($gamePath);
		$this->setSkybox($skybox);
	}

	/**
	 * @param $realPath
	 * @return array Array of items of structure ["type" => <string>, "data" => <misc>]
	 */
	public static function loadFileData($realPath): array {
		$conts = file_get_contents($realPath);
		$conts = str_replace(";", ";\n", $conts);
		$lines = explode("\n", $conts);

		//Are we currently reading the info?
		$inInfoBlock = false;

		$readItems = [];

		//Read the mission, line by line, until the end
		foreach ($lines as $line) {
			//Ignore trailing whitespace
			$line = trim($line);

			//Ignore blank lines
			if (!strlen($line))
				continue;

			//TODO: PQ mcs files

			//Is it the start of the mission's info? If so, then start reading data.
			if ($line == "new ScriptObject(MissionInfo) {") {
				$inInfoBlock = true;
				//Ignore this line
				continue;
			} else if ($inInfoBlock && $line == "};") {
				//End of the mission info, stop here
				$inInfoBlock = false;
			}
			//Are we currently reading data?
			if ($inInfoBlock) {
				//Is the line a mission field (not extraneous data)?
				if (strpos($line, "=") !== false) {
					//Extract the information out of the line
					list($key, $value) = ExtractField($line);

					//If we actually got something...
					if ($key !== "" && $value !== "") {

						//Something that SQL can handle, also the game can handle
						$key = mb_convert_encoding($key, "ISO-8859-1");
						$value = mb_convert_encoding($value, "ISO-8859-1");

						//Check if it's an array
//						if (strpos($key, "[") !== false) {
//							//Extract name and index
//							$name = trim(substr($key, 0, strpos($key, "[")));
//							$index = trim(substr($key, strpos($key, "[") + 1, strpos($key, "[") - strpos($key, "]") - 1));
//
//							//If the index is a string, strip any quotes on it
//							if (substr($index, 0, 1) === "\"") {
//								$index = substr($index, 1, strlen($index) - 2);
//							}
//
//							//Append the value to the existing array
//							//TODO: array fields
//							$info->setFieldArray($name, $index, $value);
//						} else {
							//Basic value
							$readItems[] = ["type" => "field", "data" => [$key, $value]];
//						}
					}
					continue;
				}
			} else if (stripos($line, "interiorFile") !== false ||
				stripos($line, "interiorResource") !== false) {
				//Extract the information out of the line
				list($key, $value) = ExtractField($line);

				//If we actually got something...
				if ($key !== "" && $value !== "") {
					$readItems[] = ["type" => "interior", "data" => $value];
				}
			} else if (stripos($line, "datablock") !== false &&
				stripos($line, "GemItem") !== false) {
				$readItems[] = ["type" => "gem"];
			} else if (stripos($line, "datablock") !== false &&
				stripos($line, "EasterEgg") !== false) {
				$readItems[] = ["type" => "egg"];
			} else if (stripos($line, "materialList") !== false) {
				//Skybox data

				//Extract the information out of the line
				list($key, $value) = ExtractField($line);

				if ($key !== "" && $value !== "") {
					//Make sure it's not a variable or something stupid
					if (stripos($value, "$") !== false) {
						//Yes it is. What dicks. Let's just assume they did the smart thing and made it auto detect
						// if you have the sky or not.
						continue;
					}

					$readItems[] = ["type" => "skybox", "data" => $value];
				}
			} else if (stripos($line, "\$skyPath") !== false) {
				//Some people do this with their skyboxes

				//Extract the information out of the line
				list($key, $value) = ExtractField($line);

				if ($key !== "" && $value !== "") {
					//Make sure it's not a variable or something stupid
					if (stripos($value, "$") !== false) {
						//Yes it is. What dicks. Let's just assume they did the smart thing and made it auto detect
						// if you have the sky or not.
						continue;
					}

					$readItems[] = ["type" => "skybox", "data" => $value];
				}
			} else if ((stripos($line, "shapeName") !== false) ||
				(stripos($line, "shapeFile") !== false)) {
				//Shape names

				//Extract the information out of the line
				list($key, $value) = ExtractField($line);

				if ($key !== "" && $value !== "") {
					$readItems[] = ["type" => "shape", "data" => $value];
				}
			}
		}

		//Clean up
		unset($lines);
		unset($conts);

		return $readItems;
	}

	protected function resolvePath($gamePath) {
		if (is_file(Paths::getRealPath($gamePath))) {
			return $gamePath;
		}
		//Check for shenanigans
		if (($gamePath[0] == "~") && ($gamePath[0] == "/")) {
			//That's not a real path!
			return $gamePath;
		}
		//Try relative paths
		if (($gamePath[0] == ".") && ($gamePath[1] == "/")) {
			echo("Found relative gamePath $gamePath\n");
			//It's relative?
			$dir = dirname($this->getGamePath());
			$gamePath = $dir . substr($gamePath, 1);
			if (is_file(Paths::getRealPath($gamePath))) {
				echo("Resolved relative skybox path: $gamePath\n");
			} else {
				echo("Missing skybox: $gamePath\n");
			}
		}
		return $gamePath;
	}

	/**
	 * Get a list of all files that this mission references
	 * @return array List of info arrays about the files,
	 *               ["path" => path, "hash" => string, "type" => string, "official" => bool]
	 */
	public function getFiles() {
		function addFile(&$files, AbstractGameEntity $object, $type = "data") {
			$file = $object->getGamePath();
			foreach ($files as $entry) {
				if ($entry["path"] === $file)
					return;
			}
			$array = [
				"path" => $file,
				"hash" => $object->getHash(),
				"official" => $object->getOfficial(),
				"type" => $type,
			];
			if ($object->getHash() === null)
				$array["missing"] = true;
			$files[] = $array;
		}

		$files = [];

		//.mis file and preview bitmap
		addFile($files, $this, "mission");
		addFile($files, $this->getBitmap(), "bitmap");

		//Interiors and their textures
		foreach ($this->getInteriors() as $interior) {
			/* @var Interior $interior */
			if ($interior->getOfficial()) {
				continue;
			}
			addFile($files, $interior);

			foreach ($interior->getTextures() as $texture) {
				/* @var Texture $texture */
				if ($texture->getOfficial()) {
					continue;
				}
				addFile($files, $texture);
			}
		}

		foreach ($this->getShapes() as $shape) {
			/* @var Shape $shape */
			if ($shape->getOfficial()) {
				continue;
			}
			addFile($files, $shape);

			foreach ($shape->getTextures() as $texture) {
				/* @var Texture $texture */
				if ($texture->getOfficial()) {
					continue;
				}
				addFile($files, $texture);
			}
		}

		if (!$this->getSkybox()->getOfficial()) {
			addFile($files, $this->getSkybox());
			foreach ($this->getSkybox()->getTextures() as $texture) {
				/* @var Texture $texture */
				if ($texture->getOfficial()) {
					continue;
				}
				addFile($files, $texture);
			}
		}

		$files = array_unique($files, SORT_REGULAR);

		return $files;
	}

	//Autogenerated getters and setters beyond this point

	/**
	 * @return Collection
	 */
	public function getFields(): Collection {
		return $this->fields;
	}

	/**
	 * @return string
	 */
	public function getModification() {
		return $this->modification;
	}

	/**
	 * @param string $modification
	 */
	public function setModification($modification) {
		$this->modification = $modification;
	}

	/**
	 * @return string
	 */
	public function getGameType() {
		return $this->gameType;
	}

	/**
	 * @param string $gameType
	 */
	public function setGameType($gameType) {
		$this->gameType = $gameType;
	}

	/**
	 * @return Texture
	 */
	public function getBitmap() {
		return $this->bitmap;
	}

	/**
	 * @param Texture $bitmap
	 */
	public function setBitmap($bitmap) {
		$this->bitmap = $bitmap;
	}

	/**
	 * @return int
	 */
	public function getGems() {
		return $this->gems;
	}

	/**
	 * @param int $gems
	 */
	public function setGems($gems) {
		$this->gems = $gems;
	}

	/**
	 * @return bool
	 */
	public function getEasterEgg() {
		return $this->easterEgg;
	}

	/**
	 * @param bool $easterEgg
	 */
	public function setEasterEgg($easterEgg) {
		$this->easterEgg = $easterEgg;
	}

	/**
	 * @param string $name
	 * @return Field|null
	 */
	public function getField($name) {
		for ($i = 0; $i < $this->fields->count(); $i ++) {
			$field = $this->fields[$i];
			/* @var Field $field */
			if (strcasecmp($field->getName(), $name) === 0) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * @param string $name
	 * @return string|null
	 */
	public function getFieldValue($name) {
		$field = $this->getField($name);
		return $field === null ? null : $field->getValue();
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasField($name): bool {
		return $this->fields->exists(function($index, Field $field) use($name) {
			return strcasecmp($field->getName(), $name) === 0;
		});
	}

	/**
	 * @return Collection
	 */
	public function getGameModes(): Collection {
		return $this->gameModes;
	}

	/**
	 * @return Collection
	 */
	public function getInteriors(): Collection {
		return $this->interiors;
	}

	/**
	 * @return Collection
	 */
	public function getShapes(): Collection {
		return $this->shapes;
	}

	/**
	 * @return Skybox
	 */
	public function getSkybox() {
		return $this->skybox;
	}

	/**
	 * @param Skybox $skybox
	 */
	public function setSkybox($skybox) {
		$this->skybox = $skybox;
	}

}
