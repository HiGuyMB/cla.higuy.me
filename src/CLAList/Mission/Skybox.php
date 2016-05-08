<?php
namespace CLAList\Mission;

use CLAList\Database;

class Skybox {
	protected $id;
	protected $file;
	protected $full;
	protected $database;
	protected $textures;
	protected $missingTextures;

	public function __construct(Database $database, $fullPath) {
		$this->id       = -1;
		$this->file     = $database->convertPathToRelative($fullPath);
		$this->database = $database;
		$this->full     = $fullPath;
		$this->textures = [];

		$this->missingTextures = false;
	}

	public static function loadFile(Database $database, $fullPath) {
		$skybox = new Skybox($database, $fullPath);

		//Get the contents of the DML file
		$conts = file_get_contents($skybox->full);
		//Clean it up a bit
		$conts = str_replace(array("\r", "\r\n", "\n"), "\n", $conts);
		$skybox->textures = explode("\n", $conts);
		$skybox->textures = array_filter($skybox->textures);

		//Resolve the full paths of all the textures
		$skybox->textures = array_map(function ($texture) use($skybox) {
			//Resolve the name
			$image = $skybox->resolveTexture(pathinfo($skybox->getFull(), PATHINFO_DIRNAME), $texture);

			if ($image == null) {
				echo("Can't find {$texture} in " . pathinfo($skybox->getFull(), PATHINFO_DIRNAME) . "\n");
				//Didn't work? Just use the default

				//Common environment map textures that are often missing
				if ($texture !== "enviro_map" && $texture !== "7")
					$skybox->missingTextures = true;

				$image = $texture;
			} else {
				//Did work, make it pretty
				$image = $skybox->database->convertPathToRelative($image);
			}
			return $image;
		}, $skybox->textures);
		$skybox->textures = array_values($skybox->textures);

		return $skybox;
	}

	public static function loadDatabaseId(Database $database, $id) {
		$query = $database->prepare("SELECT * FROM `@_skyboxes` WHERE `id` = :id");
		$query->bindParam(":id", $id);
		$query->execute();

		if ($query->rowCount()) {
			$row = $query->fetch(\PDO::FETCH_ASSOC);
			return self::loadDatabaseRow($database, $row);
		}
		return null;
	}

	public static function loadDatabasePath(Database $database, $fullPath) {
		$query = $database->prepare("SELECT `id` FROM `@_skyboxes` WHERE `full_path` = :full");
		$query->bindParam(":full", $fullPath);
		$query->execute();

		if ($query->rowCount()) {
			return self::loadDatabaseId($database, $query->fetchColumn(0));
		} else {
			$skybox = new Skybox($database, $fullPath);
			$skybox->addToDatabase();
			return $skybox;
		}
	}

	public static function loadDatabaseRow(Database $database, $row) {
		$skybox = new Skybox($database, "");
		$skybox->full = $row["full_path"];
		$skybox->file = $row["file_path"];
		$skybox->id = $row["id"];
		$skybox->textures = json_decode($row["skybox_textures"], true);
		$skybox->missingTextures = $row["missing_skybox_textures"];

		return $skybox;
	}


	public function addToDatabase() {
		$query = $this->database->prepare("INSERT INTO `@_skyboxes` SET `file_path` = :file, `full_path` = :full, `skybox_textures` = :textures, `missing_skybox_textures` = :missing");
		$query->bindParam(":file", $this->file);
		$query->bindParam(":full", $this->full);
		$query->bindParam(":textures", json_encode($this->textures));
		$query->bindParam(":missing", $this->missingTextures);
		$query->execute();

		$this->id = $this->database->lastInsertId();
	}

	protected function resolveTexture($base, $texture) {
		$test = $base . "/" . $texture;

		//Test a whole bunch of image types
		if (is_file("{$test}.png")) {
			$image = "{$test}.png";
		} else if (is_file("{$test}.jpg")) {
			$image = "{$test}.jpg";
		} else if (is_file("{$test}.jpeg")) {
			$image = "{$test}.jpeg";
		} else if (is_file("{$test}.bmp")) {
			$image = "{$test}.bmp";
		} else {
			//Try to recurse
			$sub = pathinfo($base, PATHINFO_DIRNAME);
			if ($sub === BASE_DIR || $sub === "" || $sub === "/" || $sub === ".")
				return null;
			$image = $this->resolveTexture($sub, $texture);
		}
		return $image;
	}

	public function getTextures() {
		return $this->textures;
	}

	public function getFull() {
		return $this->full;
	}

	public function getFile() {
		return $this->file;
	}

	public function getMissingTextures() {
		return $this->missingTextures;
	}

	public function getId() {
		return $this->id;
	}
}
