<?php
namespace CLAList\Upload;

use CLAList\Filesystem;

class UploadedFile {

	const UPLOAD_ERR_BAD_ZIP = 1001;
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

		if ($this->error !== 0) {
			return false;
		}

		//See if we need to decompress this
		$zipTypes = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
		if (in_array($this->type, $zipTypes)) {
			if (!$this->decompress()) {
				$this->error = self::UPLOAD_ERR_BAD_ZIP;
				return false;
			}
		}

		return true;
	}

	public function loadDirectory($dir) {
		$this->name     = pathinfo($dir, PATHINFO_FILENAME);
		$this->type     = "directory";
		$this->path     = $dir;
		$this->error    = 0;
		$this->size     = 0;
		$this->contents = [];
		$this->hash     = null;

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
				$obj->loadDirectory($path);
			} else {
				$obj->loadFile($path);
			}

			$this->contents[] = $obj;
		}

		return true;
	}

	public function loadFile($file) {
		$this->name     = pathinfo($file, PATHINFO_BASENAME);
		$this->type     = self::getFileType($file);
		$this->path     = $file;
		$this->error    = 0;
		$this->size     = filesize($file);
		$this->contents = null;
		$this->hash     = GetHash($file);

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

	protected function decompress() {
		$zip = new \ZipArchive();
		if (!$zip->open($this->path)) {
			echo("Zip open failed");
			return false;
		}

		$dest = tempnam(sys_get_temp_dir(), "clazip");
		//Since tempnam() creates a file
		unlink($dest);

		if (!$zip->extractTo($dest)) {
			echo("Zip extract failed");
			return false;
		}
		if (!$zip->close()) {
			echo("Zip close failed??\n");
			return false;
		}

		//Now we're here
		unlink($this->path);
		return $this->loadDirectory($dest);
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
			default:
				return 'Unknown error';
		}
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
			default: return \GuzzleHttp\Psr7\mimetype_from_extension($ext);
		}
	}
}
