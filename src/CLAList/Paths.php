<?php

namespace CLAList;

class Paths {
	/** @var string $contentDir */
	private static $contentDir;

	/**
	 * Get the location of where real game content is located. Any calls to GetRealPath
	 * will be based off this variable. Any calls to GetGamePath will be relative to this.
	 * @return string Content base location
	 */
	public static function getContentDir() {
		return self::$contentDir;
	}

	/**
	 * Change the location of where real game content is located. Any calls to GetRealPath
	 * will be based off this variable. Any calls to GetGamePath will be relative to this.
	 * @param string $newDir New content base location
	 */
	public static function setContentDir($newDir) {
		self::$contentDir = $newDir;
	}

	/**
	 * Turn a real path (cla-git/data/etc) into a game path (~/data/etc)
	 * @param string $realPath
	 * @return string
	 */
	public static function GetGamePath($realPath) {
		if (substr($realPath, 0, 1) === "~")
			return $realPath;
		if (strpos($realPath, BASE_DIR) === false)
			return $realPath;

		$realPath = "~/" . str_replace(array(self::$contentDir . "/", BASE_DIR . "/", "~/"), "", $realPath);
		$realPath = str_replace("//", "/", $realPath);
		return $realPath;
	}

	/**
	 * Turn a game path (~/data/etc) into a real path (cla-git/data/etc)
	 * @param string $gamePath
	 * @return string
	 */
	public static function GetRealPath($gamePath) {
		if (substr($gamePath, 0, 1) !== "~")
			return $gamePath;
		if (strpos($gamePath, BASE_DIR) !== false)
			return $gamePath;

		$full = str_replace("~/", self::$contentDir . "/", $gamePath);
		$full = str_replace("//", "/", $full);
		return $full;
	}

	/**
	 * String SHA256 hash of a file or null if not exists
	 * @param string $realPath
	 * @return string|null
	 */
	public static function GetHash($realPath) {
		return is_file($realPath) ? hash("sha256", file_get_contents($realPath)) : null;
	}
}
