<?php

namespace CLAList;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;

/**
 * @Entity
 * @Table(name="uxwba_missions")
 */
class Mission {
	/**
	 * @Id @Column(type="integer")
	 * @GeneratedValue
	 */
	private $id;
	/** @Column(type="string", length=128, name="base_name") */
	private $baseName;
	/** @Column(type="string", length=256, unique=TRUE, name="file_path") */
	private $filePath;
	/** @Column(type="string", length=16) */
	private $modification;
	/** @Column(type="EnumGameType", name="game_type") */
	private $gameType;
	/** @Column(type="string", length=256) */
	private $bitmap;
	/** @Column(type="string", length=128) */
	private $hash;
	/** @Column(type="integer") */
	private $gems;
	/** @Column(type="boolean", name="easter_egg") */
	private $easterEgg;
	/**
	 * @OneToMany(targetEntity="Field", mappedBy="mission")
	 */
	private $fields;
	/**
	 * @ManyToMany(targetEntity="GameMode")
	 * @JoinTable(name="uxwba_mission_game_modes",
	 *     joinColumns={@JoinColumn(name="mission_id", referencedColumnName="id")},
	 *     inverseJoinColumns={@JoinColumn(name="game_mode_id", referencedColumnName="id")})
	 * )
	 */
	private $gameModes;

	public function __construct() {
		$this->fields = new ArrayCollection();
		$this->gameModes = new ArrayCollection();
	}

	public function loadFile() {
		$em = GetEntityManager();

		//Try to open the file. If we can't, just return the blank array.
		$handle = fopen($this->filePath, "r");
		if ($handle === false) {
			//Failure
			return null;
		}

		$this->gems = 0;
		$this->easterEgg = false;

		//Are we currently reading the info?
		$inInfoBlock = false;

		//Read the mission, line by line, until the end
		while (($line = fgets($handle)) !== false) {
			//Ignore trailing whitespace
			$line = trim($line);

			//Ignore blank lines
			if (!strlen($line))
				continue;

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
					$key = strtolower(trim(substr($line, 0, strpos($line, "="))));
					$value = stripslashes(trim(substr($line, strpos($line, "=") + 1, strlen($line))));

					//If we actually got something...
					if ($key !== "" && $value !== "") {
						//Strip semicolon and quotes from the line
						$value = substr($value, 1, strlen($value) - 3);

						//Ignore blank values
						if ($value === "")
							continue;

						//Something that SQL can handle, also the game can handle
						$key = mb_convert_encoding($key, "ISO-8859-1");
						$value = mb_convert_encoding($value, "ISO-8859-1");

						//Check if it's an array
						if (strpos($key, "[") !== false) {
							//Extract name and index
							$name = trim(substr($key, 0, strpos($key, "[")));
							$index = trim(substr($key, strpos($key, "[") + 1, strpos($key, "[") - strpos($key, "]") - 1));

							//If the index is a string, strip any quotes on it
							if (substr($index, 0, 1) === "\"") {
								$index = substr($index, 1, strlen($index) - 2);
							}

							//Append the value to the existing array
							//TODO: array fields
//							$info->setFieldArray($name, $index, $value);
						} else {
							//Basic value
							if (!$this->hasField($key)) {
								$field = new Field($this, $key, $value);
								$em->persist($field);
								$this->fields->add($field);
							}
						}
					}
					continue;
				}
			} else if (stripos($line, "interiorFile") !== false ||
				stripos($line, "interiorResource") !== false) {
				//Extract the information out of the line
				$key = trim(substr($line, 0, strpos($line, "=")));
				$value = stripslashes(trim(substr($line, strpos($line, "=") + 1, strlen($line))));

				//Sometimes people do this
				$value = str_replace(array("\$usermods @ \"/", "usermods @\"/", "userMods @ \"", "\"marble/", "\"platinum/", "\"platinumbeta/"), "\"~/", $value);

				//If we actually got something...
				if ($key !== "" && $value !== "") {
					//Strip semicolon and quotes from the line
					$value = substr($value, 1, strlen($value) - 3);

					//Ignore blank values
					if ($value === "") {
						continue;
					}

//					$info->addInterior(new Interior($database, $value));
				}
			} else if (stripos($line, "datablock") !== false &&
				stripos($line, "GemItem") !== false) {
				$this->gems ++;
			} else if (stripos($line, "datablock") !== false &&
				stripos($line, "EasterEgg") !== false) {
				$this->easterEgg = true;
			} else if (stripos($line, "materialList") !== false) {
				//Skybox data

				//Extract the information out of the line
				$key = trim(substr($line, 0, strpos($line, "=")));
				$value = stripslashes(trim(substr($line, strpos($line, "=") + 1, strlen($line))));

				//Sometimes people do this
				$value = str_replace(array("\$usermods @ \"/", "usermods @\"/", "userMods @ \"", "\"marble/", "\"platinum/", "\"platinumbeta/"), "\"~/", $value);

				if ($key !== "" && $value !== "") {
					//Make sure it's not a variable or something stupid
					if (stripos($value, "$") !== false) {
						//Yes it is. What dicks. Let's just assume they did the smart thing and made it auto detect
						// if you have the sky or not.
						continue;
					}

					//Hopefully it's quoted, or this will blow up
					$value = substr($value, 1, strlen($value) - 3);

					//Not sure how you could have a blank sky but hey why not
					if ($value === "") {
						continue;
					}

//					$info->setSkybox($database->convertPathToAbsolute($value));
				}
			} else if (stripos($line, "\$skyPath") !== false) {
				//Some people do this with their skyboxes

				//Already set it and this is the fallback
//				if ($info->getSkybox() !== null)
					continue;

				//Extract the information out of the line
				$key = trim(substr($line, 0, strpos($line, "=")));
				$value = stripslashes(trim(substr($line, strpos($line, "=") + 1, strlen($line))));

				//Sometimes people do this
				$value = str_replace(array("\$usermods @ \"/", "usermods @\"/", "userMods @ \"", "\"marble/", "\"platinum/", "\"platinumbeta/"), "\"~/", $value);

				if ($key !== "" && $value !== "") {
					//Make sure it's not a variable or something stupid
					if (stripos($value, "$") !== false) {
						//Yes it is. What dicks. Let's just assume they did the smart thing and made it auto detect
						// if you have the sky or not.
						continue;
					}

					//Hopefully it's quoted, or this will blow up
					$value = substr($value, 1, strlen($value) - 3);

					//Not sure how you could have a blank sky but hey why not
					if ($value === "") {
						continue;
					}

//					$info->setSkybox($database->convertPathToAbsolute($value));
				}
			}
		}

		//Clean up
		fclose($handle);

//		if ($info->getSkybox() === null) {
//			//No skybox, just use the default
//			$info->setSkybox($database->convertPathToAbsolute("~/data/skies/sky_day.dml"));
//		}

		//Try to glean this
		$this->modification = $this->guessModification();
		$this->gameType = (stripos($this->filePath, "multiplayer/") !== false) ? EnumGameType::MULTIPLAYER : EnumGameType::SINGLE_PLAYER;

		$this->gameModes->clear();
		if ($this->hasField("gameMode")) {
			$modes = explode(" ", $this->getField("gameMode")->getValue());
			foreach ($modes as $name) {
				$mode = $em->getRepository('CLAList\GameMode')->findOneBy(["name" => $name]);
				if ($mode === null) {
					$mode = new GameMode();
					$mode->setName($name);
					$em->persist($mode);
					$em->flush();
				}
				$this->gameModes->add($mode);
			}
		} else {
			$mode = $em->getRepository('CLAList\GameMode')->findOneBy(["name" => "null"]);
			if ($mode === null) {
				$mode = new GameMode();
				$mode->setName("null");
				$em->persist($mode);
				$em->flush();
			}
			$this->gameModes->add($mode);
		}


		//Try to find an image in the same dir
		$base = pathinfo($this->filePath, PATHINFO_DIRNAME) . "/" . pathinfo($this->filePath, PATHINFO_FILENAME);

		if (is_file("{$base}.png")) {
			$this->bitmap = "{$base}.png";
		} else if (is_file("{$base}.jpg")) {
			$this->bitmap = "{$base}.jpg";
		} else if (is_file("{$base}.jpeg")) {
			$this->bitmap = "{$base}.jpeg";
		} else if (is_file("{$base}.bmp")) {
			$this->bitmap = "{$base}.bmp";
		} else {
			$this->bitmap = null;
		}

		$this->hash = hash("sha256", file_get_contents($this->filePath));
	}

	public function guessModification() {
		//Easy one
		if ($this->hasField("game")) return $this->getField("game")->getValue();

		//Some basic indicators
		if ($this->hasField("ultimateTime")) return "platinum";
		if ($this->hasField("ultimateScore")) return "platinum";
		if ($this->easterEgg) return "platinum";

//		//Check interiors
//		foreach ($this->interiors as $interior) {
//			/* @var Interior $interior */
//
//			$file = $interior->getFile();
//			if (stripos($file, "mbp_") !== false) return "platinum";
//			if (stripos($file, "interiors_mbp") !== false) return "platinum";
//			if (stripos($file, "fubargame") !== false) return "fubar";
//
//			$textures = $interior->getTextures();
//
//			foreach ($textures as $texture) {
//				if (stripos($texture, "mbp_") !== false) return "platinum";
//				if (stripos($texture, "mbu_") !== false) return "platinum";
//			}
//		}


		return "gold";
	}

	//Autogenerated getters and setters beyond this point

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getBaseName() {
		return $this->baseName;
	}

	/**
	 * @param mixed $baseName
	 */
	public function setBaseName($baseName) {
		$this->baseName = $baseName;
	}

	/**
	 * @return mixed
	 */
	public function getFilePath() {
		return $this->filePath;
	}

	/**
	 * @param mixed $filePath
	 */
	public function setFilePath($filePath) {
		$this->filePath = $filePath;
	}

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
	 * @return string
	 */
	public function getBitmap() {
		return $this->bitmap;
	}

	/**
	 * @param string $bitmap
	 */
	public function setBitmap($bitmap) {
		$this->bitmap = $bitmap;
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @param string $hash
	 */
	public function setHash($hash) {
		$this->hash = $hash;
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
		$match = $this->fields->filter(function(Field $field) use($name) {
			return strcasecmp($field->getName(), $name) === 0;
		});
		//If we found one
		if ($match->count() > 0) {
			return $match->first();
		} else {
			return null;
		}
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

}
