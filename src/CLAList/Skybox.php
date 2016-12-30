<?php
namespace CLAList;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="uxwba_skyboxes")
 */
class Skybox {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue()
	 */
	private $id;
	/** @Column(type="string", length=128, name="base_name") */
	private $baseName;
	/**
	 * @Column(type="string", length=256, unique=true, name="file_path")
	 */
	private $filePath;
	/** @Column(type="boolean", name="has_env_map") */
	private $hasEnvMap;
	/**
	 * @ManyToMany(targetEntity="Texture", cascade={"persist"})
	 * @JoinTable(name="uxwba_skybox_textures",
	 *     joinColumns={@JoinColumn(name="skybox_id", referencedColumnName="id")},
	 *     inverseJoinColumns={@JoinColumn(name="texture_id", referencedColumnName="id")})
	 * )
	 */
	private $textures;

	public function __construct($gamePath) {
		$this->textures = new ArrayCollection();

		$this->filePath = $gamePath;
		$this->baseName = basename($gamePath);
		$this->loadFile();
	}

	public function loadFile() {
		$em = GetEntityManager();

		//Get the contents of the DML file
		$conts = file_get_contents($this->getRealPath());
		//Clean it up a bit
		$conts = str_replace(array("\r", "\r\n", "\n"), "\n", $conts);
		$textures = explode("\n", $conts);
		$textures = array_filter($textures);

		$this->hasEnvMap = count($textures) > 6;

		//Resolve the full paths of all the textures
		foreach ($textures as $texture) {
			//Resolve the name
			$image = $this->resolveTexture(pathinfo($this->getRealPath(), PATHINFO_DIRNAME), $texture);

			if ($image == null) {
				echo("Can't find {$texture} in " . pathinfo($this->getRealPath(), PATHINFO_DIRNAME) . "\n");
				//Didn't work? Just use the default

				//Common environment map textures that are often missing
				if ($texture !== "enviro_map" && $texture !== "7") {
					//Just say we don't have an env map and we're good
					$this->hasEnvMap = false;
				}

				$image = $texture;
			}

			$filePath = GetGamePath($image);

			//Make a texture object for us
			$texObj = $em->getRepository('CLAList\Texture')->findOneBy(["filePath" => $filePath]);
			if ($texObj === null) {
				$texObj = new Texture($filePath);
			}

			$this->textures->add($texObj);
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

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getBaseName() {
		return $this->baseName;
	}

	/**
	 * @param string $baseName
	 */
	public function setBaseName($baseName) {
		$this->baseName = $baseName;
	}

	/**
	 * @return string
	 */
	public function getFilePath() {
		return $this->filePath;
	}

	/**
	 * @param string $filePath
	 */
	public function setFilePath($filePath) {
		$this->filePath = $filePath;
	}

	/**
	 * @return string
	 */
	public function getRealPath() {
		return GetRealPath($this->filePath);
	}

	/**
	 * @param $realPath
	 */
	public function setRealPath($realPath) {
		$this->filePath = GetGamePath($realPath);
	}

	/**
	 * @return bool
	 */
	public function getHasEnvMap() {
		return $this->hasEnvMap;
	}

	/**
	 * @param bool $hasEnvMap
	 */
	public function setHasEnvMap($hasEnvMap) {
		$this->hasEnvMap = $hasEnvMap;
	}


	/**
	 * @return Collection
	 */
	public function getTextures() {
		return $this->textures;
	}
}
