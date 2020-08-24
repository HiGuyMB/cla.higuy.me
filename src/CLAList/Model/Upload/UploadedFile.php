<?php
namespace CLAList\Model\Upload;

use CLAList\Model\Filesystem;
use CLAList\Paths;

class UploadedFile {

	const UPLOAD_ERR_BAD_ZIP = 1001;
	const UPLOAD_UNSUPPORTED_ZIP_NEST = 1002;

	static $disallowed_files = [
		".",
		"..",
		".DS_Store",
		"Thumbs.db",
		"__MACOSX",
		".git",
		".svn",
		".hg"
	];

	protected $name;
	protected $type;
	protected $path;
	protected $error;
	protected $size;
	protected $contents;
	protected $hash;
	protected $rootPath;
	protected $parent;

	public function __destruct() {
		//Remove any files we've uploaded
		if ($this->type === "directory") {
			unset($this->contents);

			if (is_dir($this->path)) {
				Filesystem::rmdir($this->path);
			}
		} else if (is_file($this->path)) {
			unlink($this->path);
		}
	}

	public function loadFilesArray($index) {
		$this->name     = $_FILES["mission"]["name"][$index];
		$this->path     = $_FILES["mission"]["tmp_name"][$index];
		$this->error    = $_FILES["mission"]["error"][$index];
		$this->size     = $_FILES["mission"]["size"][$index];
		$this->type     = self::getFileType($this->name);
		$this->contents = null;
		$this->hash     = null;
		$this->parent   = null;
		$this->rootPath = pathinfo($this->path, PATHINFO_DIRNAME);

		if ($this->error !== 0) {
			return false;
		}

		//See if we need to decompress this
		if ($this->isZipArchive()) {
			if (!$this->decompress(true)) {
				return false;
			}
		}

		return true;
	}

	public function loadFilesArrayData($data) {
		$this->name     = $data["name"];
		$this->path     = $data["tmp_name"];
		$this->error    = $data["error"];
		$this->size     = $data["size"];
		$this->type     = self::getFileType($this->name);
		$this->contents = null;
		$this->hash     = null;
		$this->parent   = null;
		$this->rootPath = pathinfo($this->path, PATHINFO_DIRNAME);

		if ($this->error !== 0) {
			return false;
		}

		//See if we need to decompress this
		if ($this->isZipArchive()) {
			if (!$this->decompress(true)) {
				return false;
			}
		}

		return true;
	}

	public function loadDirectory($dir, $root, $setParent = true) {
		$this->name     = pathinfo($dir, PATHINFO_FILENAME);
		$this->type     = "directory";
		$this->path     = $dir;
		$this->error    = 0;
		$this->size     = 0;
		$this->contents = [];
		$this->hash     = null;
		$this->parent   = null;
		$this->rootPath = $root;

		//Scour dir for contents
		if (($handle = opendir($this->path)) === false) {
			return false;
		}
		while (($file = readdir($handle)) !== false) {
			if (in_array($file, self::$disallowed_files))
				continue;

			$this->size ++;

			$obj = new UploadedFile();
			$path = $dir . '/' . $file;
			if (is_dir($path)) {
				$obj->loadDirectory($path, $root);
			} else {
				$obj->loadFile($path, $root);
			}

			if ($setParent) {
				$obj->parent = $this;
			}
			$this->contents[] = $obj;
		}

		return true;
	}

	public function loadFile($file, $root = null) {
		$this->name     = pathinfo($file, PATHINFO_BASENAME);
		$this->type     = self::getFileType($file);
		$this->path     = $file;
		$this->error    = 0;
		$this->size     = filesize($file);
		$this->contents = null;
		$this->hash     = Paths::getHash($file);
		$this->parent   = null;
		$this->rootPath = $root;

		if ($this->isZipArchive()) {
			if (!$this->decompress($root === null)) {
				return false;
			}
		}

		return true;
	}

	public function collectFiles(&$list) {
		if ($this->type === "directory") {
			foreach ($this->contents as $file) {
				/* @var UploadedFile $file */
				$file->collectFiles($list);
			}
		} else {
			$list[] = $this;
		}
	}

	protected function decompress($isRoot) {
		if (!$isRoot) {
			$this->error = self::UPLOAD_UNSUPPORTED_ZIP_NEST;
			return false;
		}

		$zip = new \ZipArchive();
		if (!$zip->open($this->path)) {
			$this->error = self::UPLOAD_ERR_BAD_ZIP;
			return false;
		}

		$dest = tempnam(sys_get_temp_dir(), "clazip");
		//Since tempnam() creates a file
		unlink($dest);

		if (!$zip->extractTo($dest)) {
			$this->error = self::UPLOAD_ERR_BAD_ZIP;
			Filesystem::rmdir($dest);
			return false;
		}
		if (!$zip->close()) {
			$this->error = self::UPLOAD_ERR_BAD_ZIP;
			Filesystem::rmdir($dest);
			return false;
		}

		//Now we're here
		unlink($this->path);
		return $this->loadDirectory($dest, $this->rootPath, false);
	}

	/**
	 * File basename
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Type of file (interior, mission, shape, skybox, or mime-type)
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Full path on disk
	 * @return string
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * Error code (or 0 if none)
	 * @return int
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Size in bytes of file, or number of items in directory
	 * @return int
	 */
	public function getSize() {
		return $this->size;
	}

	/**
	 * Directory subfiles
	 * @return array
	 */
	public function getContents() {
		return $this->contents;
	}

	/**
	 * SHA256 hash of the file, null for directories
	 * @return string|null
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * Gets the parent file that contains this file, or null if this is a root file
	 * @return UploadedFile|null
	 */
	public function getParent() {
		return $this->parent;
	}

	/**
	 * Root directory of the uploaded file's tree
	 * @return string
	 */
	public function getRootPath() {
		return $this->rootPath;
	}

	/**
	 * Get this file's path relative to its root
	 * @return string
	 */
	public function getRelativePath() {
		if ($this->getParent() === null) {
			return "/" . $this->getName();
		}
		return $this->getParent()->getRelativePath() . "/" . $this->getName();
	}

	public function getErrorString() {
		switch ($this->error) {
			case UPLOAD_ERR_OK:
				return '';
			case UPLOAD_ERR_NO_FILE:
				return 'No file sent.';
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return 'Exceeded filesize limit.';
			case UPLOAD_ERR_PARTIAL:
				return 'Only received partial content';
			case UPLOAD_ERR_NO_TMP_DIR:
				return 'No temp directory';
			case UPLOAD_ERR_CANT_WRITE:
				return 'File write error';
			case UPLOAD_ERR_EXTENSION:
				return 'Bad file extension';
			case self::UPLOAD_ERR_BAD_ZIP:
				return 'Zip decompression failed.';
			case self::UPLOAD_UNSUPPORTED_ZIP_NEST:
				return 'Zip nesting is unsupported.';
			default:
				return 'Unknown error';
		}
	}

	public function isZipArchive() {
		$zipTypes = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed'];
		return in_array($this->type, $zipTypes);
	}

	public function install($installPath) {
		if (!Filesystem::copy($this->path, $installPath)) {
			echo("Could not copy {$this->path} to {$installPath}\n");
		}
	}

	protected static function getFileType($file) {
		if (is_dir($file)) {
			return "directory";
		}
		$ext = pathinfo($file, PATHINFO_EXTENSION);

		switch ($ext) {
			case "dif": return "interior";
			case "mis": return "mission";
			case "dts": return "shape";
			case "dml": return "skybox";
			case "dds": return "image/dds";
			default: return \GuzzleHttp\Psr7\mimetype_from_extension($ext);
		}
	}

	/**
	 * @param array $files Possible files
	 * @param string $findPath Path to compare against
	 * @param string $type Optional: only check files matching this type
	 * @return UploadedFile|null
	 */
	public static function findClosestFile($files, $findPath, $type = "") {
		$files = array_filter($files, function (UploadedFile $file) use ($type) {
			return strpos($file->getType(), $type) !== false;
		});
		usort($files, function (UploadedFile $file1, UploadedFile $file2) use ($findPath) {
			/* @var UploadedFile $file1 */
			/* @var UploadedFile $file2 */
			$path1 = strtolower($file1->getRelativePath());
			$path2 = strtolower($file2->getRelativePath());

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
		//Try case insensitive
		foreach ($files as $closest) {
			/* @var UploadedFile $closest */
			if (strtolower(pathinfo($closest->getPath(), PATHINFO_FILENAME)) === strtolower(pathinfo($findPath, PATHINFO_FILENAME))) {
				return $closest;
			}
		}
		//No? Try the same basename
		foreach ($files as $closest) {
			/* @var UploadedFile $closest */
			if (strtolower(pathinfo($closest->getPath(), PATHINFO_BASENAME)) === strtolower(pathinfo($findPath, PATHINFO_BASENAME))) {
				return $closest;
			}
		}
		//No this is clearly not working
		return null;
	}

}
