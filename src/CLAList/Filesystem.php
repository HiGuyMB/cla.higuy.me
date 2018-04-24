<?php

namespace CLAList;

class Filesystem {
	public static $test = false;
	public static $logging = false;

	/**
	 * Recursively remove a directory
	 * @param string $dir
	 * @return bool If successful
	 */
	static function rmdir($dir) {
		if (is_dir($dir)) {
			self::echo("Remove directory $dir\n");
			$files = scandir($dir);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					if (!self::rmdir("$dir/$file")) {
						return false;
					}
				}
			}
			return rmdir($dir);
		} else if (file_exists($dir)) {
			return unlink($dir);
		} else {
			return false;
		}
	}

	/**
	 * Recursively copy a directory to another place
	 * @param string $src Directory to copy
	 * @param string $dst Destination path
	 * @return bool If successful
	 */
	static function copy($src, $dst) {
		if (file_exists($dst)) {
			if (!self::rmdir($dst)) {
				return false;
			}
		}
		if (is_dir($src)) {
			self::echo("Copying directory $src\n");
			if (!mkdir($dst)) {
				return false;
			}
			$files = scandir($src);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					if (!self::copy("$src/$file", "$dst/$file")) {
						return false;
					}
				}
			}
			return true;
		} else if (file_exists($src)) {
			//Check for directories
			if (!is_dir(pathinfo($dst, PATHINFO_DIRNAME))) {
				//Make some directories
				if (!mkdir($dst, 0775, true)) {
					return false;
				}
			}
			return copy($src, $dst);
		} else {
			return false;
		}
	}

	/**
	 * Move a file or directory
	 * @param string $src Path to move
	 * @param string $dst Destination path
	 * @return boolean True if moved successfully
	 */
	static function move($src, $dst) {
		self::echo("Move $src to $dst\n");
		return rename($src, $dst);
	}

	/**
	 * Remove all files in a directory matching a regex pattern
	 * @param string  $dir     Directory to search
	 * @param string  $pattern Pattern to delete files that match
	 * @param boolean $recurse If the deletion should recurse directories
	 */
	static function deleteMatching($dir, $pattern, $recurse = true) {
		if (is_dir($dir)) {
			$files = scandir($dir);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					if ($recurse) {
						self::deleteMatching("$dir/$file", $pattern, $recurse);
					} else if (file_exists($file)) {
						if (preg_match($pattern, $file) === 1) {
							self::delete($file);
						}
					}
				}
			}
		} else if (file_exists($dir)) {
			if (preg_match($pattern, $dir) === 1) {
				self::delete($dir);
			}
		}
	}

	/**
	 * Apply a callback to every matching file in a directory
	 * @param string $dir     Directory to search for files
	 * @param string $pattern Pattern to match files against
	 * @param mixed  $func    Function that is called for every matching file
	 * @throws \Exception
	 */
	static function filterForEach($dir, $pattern, $func) {
		if (is_dir($dir)) {
			$files = scandir($dir);
			foreach ($files as $file) {
				if ($file != "." && $file != "..") {
					self::filterForEach("$dir/$file", $pattern, $func);
				}
			}
		} else if (file_exists($dir)) {
			if (preg_match($pattern, $dir) === 1) {
				$func($dir);
			}
		} else {
			throw new \Exception("Invalid path: $dir");
		}
	}

	/**
	 * Delete a file or directory
	 * @param string $path Path to delete
	 */
	static function delete($path) {
		if (!is_file($path) && !is_dir($path))
			return;

		self::echo("Delete $path\n");

		if (!self::$test) {
			self::rmdir($path);
		}
	}

	static function echo($text) {
		if (self::$logging) {
			echo($text);
		}
	}
}
