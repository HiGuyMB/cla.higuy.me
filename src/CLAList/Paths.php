<?php

namespace CLAList;

class Paths {
	/** @var string $contentDir */
	private static $contentDir;
	/** @var string $utilsDir */
	private static $utilsDir;
	/** @var string $officialDir */
	private static $officialDir;

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
	 * @param string $newDir New content base location.
	 *                       Should contain a "data" directory as if the base was the "marble"
	 *                       or "platinum" directory of a mod.
	 */
	public static function setContentDir($newDir) {
		self::$contentDir = $newDir;
	}

	/**
	 * Get the location of where utility executables are located. This should contain at least
	 * `difutil` and `dtstextures` utilities used to parse files.
	 * @return string Utilities location
	 */
	public static function getUtilityDir() {
		return self::$utilsDir;
	}

	/**
	 * Change the location of where utility executables are located. This should contain at least
	 * `difutil` and `dtstextures` utilities used to parse files.
	 * @param string $newDir New utilities location.
	 */
	public static function setUtilityDir($newDir) {
		self::$utilsDir = $newDir;
	}

	/**
	 * @return string
	 */
	public static function getOfficialDir(): string {
		return self::$officialDir;
	}

	/**
	 * @param string $officialDir
	 */
	public static function setOfficialDir(string $officialDir) {
		self::$officialDir = $officialDir;
	}

	/**
	 * Turn a real path (cla-git/data/etc) into a game path (~/data/etc)
	 * @param string $realPath
	 * @return string
	 */
	public static function getGamePath($realPath) {
		if (substr($realPath, 0, 1) === "~")
			return $realPath;
		if (strpos($realPath, self::$contentDir) === false)
			return $realPath;

		$realPath = "~/" . str_replace(array(self::$contentDir . "/", "~/"), "", $realPath);
		$realPath = str_replace("//", "/", $realPath);
		return $realPath;
	}

	/**
	 * Turn a game path (~/data/etc) into a real path (cla-git/data/etc)
	 * @param string $gamePath
	 * @return string
	 */
	public static function getRealPath($gamePath) {
		if (substr($gamePath, 0, 1) !== "~")
			return $gamePath;
		if (strpos($gamePath, self::$contentDir) !== false)
			return $gamePath;

		$full = str_replace("~/", self::$contentDir . "/", $gamePath);
		$full = str_replace("//", "/", $full);
		return $full;
	}

	public static function getOfficialPath($gamePath) {
		if (substr($gamePath, 0, 1) !== "~")
			return $gamePath;
		if (strpos($gamePath, self::$officialDir) !== false)
			return $gamePath;

		$full = str_replace("~/", self::$officialDir . "/", $gamePath);
		$full = str_replace("//", "/", $full);
		return $full;
	}

	/**
	 * String SHA256 hash of a file or null if not exists
	 * @param string $realPath
	 * @return string|null
	 */
	public static function getHash($realPath) {
		return is_file($realPath) ? hash("sha256", file_get_contents($realPath)) : null;
	}

	/**
	 * Compare two paths to see if they are equal
	 * @param string $path1 First path
	 * @param string $path2 Second path
	 * @return bool If they are equal
	 */
	public static function compare($path1, $path2) {
		return strcasecmp($path1, $path2) === 0;
	}
}
