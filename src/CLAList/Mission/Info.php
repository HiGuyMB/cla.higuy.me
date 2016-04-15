<?php

namespace CLAList\Mission;

use CLAList\Database;

class Info {
	/* @var Database $database */
	protected $database;
	protected $fields;
	protected $file;
	protected $interiors;
	protected $gems;
	protected $image;
	protected $hash;
	protected $id;

	/**
	 * Construct from a given file
	 * @param string $fullPath The file to read
	 * @return Info An info object
	 */
	public static function loadFile(Database $database, $fullPath) {
		$info = new Info($database, $database->convertPathToRelative($fullPath));

		//Try to open the file. If we can't, just return the blank array.
		$handle = fopen($fullPath, "r");
		if ($handle === false) {
			//Failure
			return null;
		}

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
					$key = trim(substr($line, 0, strpos($line, "=")));
					$value = stripslashes(trim(substr($line, strpos($line, "=") + 1, strlen($line))));

					//If we actually got something...
					if ($key !== "" && $value !== "") {
						//Strip semicolon and quotes from the line
						$value = substr($value, 1, strlen($value) - 3);

						//Ignore blank values
						if ($value === "")
							continue;

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
							$info->setFieldArray($name, $index, $value);
						} else {
							//Basic value
							$info->setField($key, $value);
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

					$info->addInterior(new Interior($value));
				}
			} else if (stripos($line, "datablock") !== false &&
			           stripos($line, "GemItem") !== false) {
				$info->gems ++;
			}
		}

		//Clean up
		fclose($handle);

		//Try to find an image in the same dir
		$base = pathinfo($fullPath, PATHINFO_DIRNAME) . "/" . pathinfo($fullPath, PATHINFO_FILENAME);

		if (is_file("{$base}.png")) {
			$info->image = "{$base}.png";
		} else if (is_file("{$base}.jpg")) {
			$info->image = "{$base}.jpg";
		} else if (is_file("{$base}.jpeg")) {
			$info->image = "{$base}.jpeg";
		} else if (is_file("{$base}.bmp")) {
			$info->image = "{$base}.bmp";
		} else {
			$info->image = null;
		}

		if ($info->image != null) {
			$info->image = str_replace("cla-git/", "~/", $info->image);
		}

		$info->hash = hash("sha256", file_get_contents($fullPath));

		return $info;
	}

	/**
	 * Construct from a file in the database
	 * @param string $filePath The file to search
	 * @return Info An info object
	 */
	public static function loadMySQL(Database $database, $file) {
		//Try to find the mission in the database
		$query = $database->prepare("SELECT * FROM `@_missions` WHERE `file` = :file");
		$query->bindParam(":file", $file);
		$query->execute();

		//If the mission exists
		if ($query->rowCount()) {
			//Found it, construct it
			$info = new Info($database, $file);

			//Fill the Info with information from the database
			$row = $query->fetch(\PDO::FETCH_ASSOC);
			$info->setField("name", $row["name"]);
			$info->setField("desc", $row["desc"]);
			$info->setField("artist", $row["artist"]);
			$info->setField("hash", $row["hash"]);

			$info->gems = $row["gems"];
			$info->image = $row["image"];

			//Get this mission's id
			$id = $row["id"];

			//Load the interiors for this mission
			$query = $database->prepare("SELECT * FROM `@_mission_interiors` WHERE `missionId` = :id");
			$query->bindParam(":id", $id);
			$query->execute();
			$interiors = $query->fetchAll(\PDO::FETCH_ASSOC);

			//Add each interior to the info
			foreach ($interiors as $row) {
				/* @var array $row */
				$path = $row["path"];
				$info->addInterior($path);
			}

			return $info;
		} else {
			//No such mission, why are we here?
			return null;
		}
	}

	/**
	 * Construct from a file in the database
	 * @return Info An info object
	 */
	public static function loadMySQLRow(Database $database, $row) {
		//Found it, construct it
		$info = new Info($database, $row["file"]);
		$info->id = $row["id"];

		//Fill the Info with information from the database
		$info->setField("name", $row["name"]);
		$info->setField("desc", $row["desc"]);
		$info->setField("artist", $row["artist"]);
		$info->setField("missingInteriors", $row["missing_interiors"]);
		$info->setField("missingTextures", $row["missing_textures"]);

		$info->gems = $row["gems"];
		$info->image = $row["image"];
		$info->hash = $row["hash"];

		//Interiors
		$info->interiors = json_decode($row["interiors"], true);
		$info->textures = json_decode($row["textures"], true);

		return $info;
	}

	public function __construct(Database $database, $filePath) {
		$this->database = $database;
		$this->fields = array();
		$this->file = $filePath;
		$this->interiors = array();
		$this->gems = 0;
		$this->hash = "";
		$this->id = 0;
	}

	/**
	 * Add an interior to the info
	 * @param string $file The interior's file
	 */
	public function addInterior($file) {
		if (array_search($file, $this->interiors) === FALSE)
			$this->interiors[] = $file;
	}

	/**
	 * Set a value in the info
	 * @param string $name The name of the value
	 * @param string $value The value to set
	 */
	public function setField($name, $value) {
		$this->fields[$name] = $value;
	}

	/**
	 * Set the value of an index in a field array
	 * @param string $name The name of the array field
	 * @param string $index The index in the array
	 * @param string $value The value to set
	 */
	public function setFieldArray($name, $index, $value) {
		if (!isset($this->fields[$name])) {
			$this->fields[$name] = array();
		}
		$this->fields[$name][$index] = $value;
	}

	/**
	 * Get a specific field from the mission's info
	 * @param string $name The name of the field
	 * @return string The field's value
	 */
	public function getField($name) {
		if (array_key_exists($name, $this->fields))
			return $this->fields[$name];
		return "";
	}
	/**
	 * Get all the mission's fields
	 * @return array The array of all the fields
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * Get the mission's name
	 * @return string The mission's name
	 */
	public function getName() {
		return $this->getField("name");
	}

	/**
	 * Get the mission's file
	 * @return string The mission's file
	 */
	public function getFile() {
		return $this->file;
	}

	/**
	 * Get the mission's description
	 * @return string The mission's description
	 */
	public function getDesc() {
		return $this->getField("desc");
	}

	/**
	 * Get the mission's artist
	 * @return string The mission's artist
	 */
	public function getArtist() {
		return $this->getField("artist");
	}

	/**
	 * Get the mission's list of interiors
	 * @return array The interior list
	 */
	public function getInteriors() {
		return $this->interiors;
	}

	public function getGems() {
		return $this->gems;
	}

	public function getImage() {
		return $this->image;
	}

	public function getHash() {
		return $this->hash;
	}

	protected function postUpdate($type, $data = "") {
		$query = $this->database->prepare("INSERT INTO `@_mission_updates` SET `mission_id` = :id, `update_type` = :type, `update_data` = :data");
		$query->bindParam(":id", $this->id);
		$query->bindParam(":type", $type);
		$query->bindParam(":data", $data);
		$query->execute();
	}

	public function addToDatabase() {
		/* @var Info $data */
		$query = $this->database->prepare("INSERT INTO `@_missions` SET `name` = :name, `file` = :file, `hash` = :hash, `desc` = :desc, `artist` = :artist, `gems` = :gems, `image` = :image, `fields` = :fields");
		$query->bindParam(":name",   $this->getName());
		$query->bindParam(":file",   $this->getFile());
		$query->bindParam(":hash",   $this->getHash());
		$query->bindParam(":desc",   $this->getDesc());
		$query->bindParam(":artist", $this->getArtist());
		$query->bindParam(":gems",   $this->getGems());
		$query->bindParam(":image",  $this->getImage());
		$query->bindParam(":fields", json_encode($this->getFields()));
		$query->execute();
		$this->id = $this->database->lastInsertId();

		$missingInteriors = false;
		$missingTextures = false;

		$interiors = $this->getInteriors();

		$interiorPaths = [];
		$interiorTextures = [];
		foreach ($interiors as $interior) {
			/* @var \CLAList\Mission\Interior $interior */
			$interiorPaths[] = $interior->getFile();

			if (!is_file($interior->getFull())) {
				$missingInteriors = true;
			}

			$textures = $interior->getTextures();
			$interiorTextures = array_merge($interiorTextures, $textures);

			if ($interior->getMissingTextures()) {
				$missingTextures = true;
			}
		}

		//Don't store any dupes
		$interiorTextures = array_values(array_unique($interiorTextures));

		$query = $this->database->prepare("INSERT INTO `@_mission_interiors` SET `mission_id` = :id, `interiors` = :interiors, `missing_interiors` = :missingInteriors, `textures` = :textures, `missing_textures` = :missingTextures");
		$query->bindParam(":id", $this->id);
		$query->bindParam(":interiors", json_encode($interiorPaths));
		$query->bindParam(":missingInteriors", $missingInteriors);
		$query->bindParam(":textures", json_encode($interiorTextures));
		$query->bindParam(":missingTextures", $missingTextures);
		$query->execute();

		$this->postUpdate("added");
	}

	public function deleteFromDatabase() {
		$delete = $this->database->prepare("DELETE FROM `@_missions` WHERE `id` = :id");
		$delete->bindParam(":id", $this->id);
		$delete->execute();

		$delete = $this->database->prepare("DELETE FROM `@_mission_interiors` WHERE `mission_id` = :id");
		$delete->bindParam(":id", $this->id);
		$delete->execute();

		$this->postUpdate("deleted", $this->file);
	}

	public function updateDatabase() {
		$query = $this->database->prepare("UPDATE `@_missions` SET `name` = :name, `file` = :file, `hash` = :hash, `desc` = :desc, `artist` = :artist, `gems` = :gems, `image` = :image, `fields` = :fields WHERE `id` = :id");
		$query->bindParam(":name",   $this->getName());
		$query->bindParam(":file",   $this->getFile());
		$query->bindParam(":hash",   $this->getHash());
		$query->bindParam(":desc",   $this->getDesc());
		$query->bindParam(":artist", $this->getArtist());
		$query->bindParam(":gems",   $this->getGems());
		$query->bindParam(":image",  $this->getImage());
		$query->bindParam(":fields", json_encode($this->getFields()));
		$query->bindParam(":id",     $this->id);
		$query->execute();

		$this->postUpdate("updated");
	}

	public function setFile($file) {
		$this->postUpdate("moved", $this->file);
		$this->file = $file;
		$this->updateDatabase();
	}

	public function setHash($hash) {
		$this->hash = $hash;
		$this->updateDatabase();
	}
}
