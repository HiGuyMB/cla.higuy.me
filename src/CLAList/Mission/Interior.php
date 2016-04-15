<?php
namespace CLAList\Mission;

class Interior {

	protected $file;
	protected $full;
	protected $textures;
	protected $missingTextures;

	public function __construct($file) {
		$this->file = $file;

		$full = str_replace("~/", "cla-git/", $file);
		$full = BASE_DIR . "/" . $full;
		$this->full = $full;

		$this->textures = null;
		$this->missingTextures = false;
	}

	public function getFile() {
		return $this->file;
	}

	public function getFull() {
		return $this->full;
	}

	public function getTextures() {
		if ($this->textures === null) {
			$this->textures = $this->loadTextures($this->missingTextures);
		}
		return $this->textures;
	}

	public function getMissingTextures() {
		if ($this->textures === null) {
			$this->textures = $this->loadTextures($this->missingTextures);
		}
		return $this->missingTextures;
	}

	/**
	 * Get the textures used by this interior
	 * @return array
	 */
	public function loadTextures(&$missingTextures) {
		if (!is_file($this->full)) {
			return array();
		}
		
		//Run DifTests on it

		$descriptors = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);

		$command = "/usr/bin/DifTests --textures " . escapeshellarg($this->full);
		$process = proc_open($command, $descriptors, $pipes);

		//If it went through...
		if (is_resource($process)) {
			fclose($pipes[0]);

			//Get all the output
			$procoutput = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			fclose($pipes[2]);

			proc_close($process);
			$textures = explode("\n", $procoutput);

			//Strip album names from the textures
			$textures = array_map(function($texture) {
				if (strpos($texture, "/") === FALSE)
					return $texture;
				return substr($texture, strrpos($texture, "/") + 1);
			}, $textures);

			//Filter out the default texture names that don't have files
			$textures = array_filter($textures, function($texture) {
				if ($texture == "NULL") return false;
				if ($texture == "ORIGIN") return false;
				if ($texture == "TRIGGER") return false;
				if ($texture == "FORCEFIELD") return false;
				if ($texture == "EMITTER") return false;
				if ($texture == "") return false;

				return true;
			});

			//Remove duplicates which can happen if there are MPs
			$textures = array_unique($textures);

			//Convert the names into actual files and check for missing textures
			$textures = array_map(function($texture) use(&$missingTextures) {
				//Resolve the name
				$image = $this->resolveTexture(pathinfo($this->getFull(), PATHINFO_DIRNAME), $texture);

				if ($image == null) {
					//Didn't work? Just use the default
					$missingTextures = true;
					$image = $texture;
				} else {
					//Did work, make it pretty
					$image = "~" . str_replace(array(BASE_DIR, "cla-git/"), "", $image);
				}
				return $image;
			}, $textures);

			return array_values($textures);
		} else {
			return array();
		}
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
}
