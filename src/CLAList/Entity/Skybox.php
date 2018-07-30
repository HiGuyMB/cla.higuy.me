<?php
namespace CLAList\Entity;

use CLAList\Paths;
use Doctrine\Common\Collections\ArrayCollection;


use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;


use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="uxwba_skyboxes")
 */
class Skybox extends AbstractGameEntity {
	/** @Column(type="boolean", name="has_env_map") */
	private $hasEnvMap;
	/**
	 * @ManyToMany(targetEntity="Texture", cascade={"persist"})
	 * @JoinTable(name="uxwba_skybox_textures",
	 *     joinColumns={@JoinColumn(name="skybox_id", referencedColumnName="id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@JoinColumn(name="texture_id", referencedColumnName="id", onDelete="CASCADE")})
	 * )
	 */
	private $textures;

	function __construct($gamePath, $realPath = null) {
		parent::__construct($gamePath, $realPath);
		$this->hasEnvMap = false;
		$this->textures = new ArrayCollection();
		$this->loadFile();
	}

	public function loadFile() {
		if (!is_file($this->getRealPath())) {
			echo("Cannot load skybox: file does not exist\n");
			return;
		}

		$textures = self::loadFileTextures($this->getRealPath());
		$this->hasEnvMap = count($textures) > 6;

		//Resolve the full paths of all the textures
		foreach ($textures as $texture) {
			//Resolve the name
			$image = Texture::resolve(pathinfo($this->getRealPath(), PATHINFO_DIRNAME), $texture);

			if ($image == null) {
				echo("Can't find {$texture} in " . pathinfo($this->getRealPath(), PATHINFO_DIRNAME) . "\n");
				//Common environment map textures that are often missing
				if ($texture !== "enviro_map" && $texture !== "7") {
					//Just say we don't have an env map and we're good
					$this->hasEnvMap = false;
				}

				//Just use the default
				$image = dirname($this->getGamePath()) . "/" . $texture;
			}

			$gamePath = Paths::getGamePath($image);

			//Make a texture object for us
			$texObj = Texture::findByGamePath($gamePath);
			$this->textures->add($texObj);
		}
	}

	/**
	 * @param $realPath
	 * @return array
	 */
	public static function loadFileTextures($realPath): array {
		//Get the contents of the DML file
		$conts = file_get_contents($realPath);
		//Clean it up a bit
		$conts = str_replace(["\r", "\r\n", "\n"], "\n", $conts);
		$textures = explode("\n", $conts);
		$textures = array_map(function($texture) {
			return strtolower($texture);
		}, $textures);
		$textures = array_filter($textures);
		$textures = array_unique($textures);
		return $textures;
	}

	/**
	 * @return bool
	 */
	public function getHasEnvMap() {
		return $this->hasEnvMap;
	}

	/**
	 * @return mixed
	 */
	public function getTextures() {
		return $this->textures;
	}

	/**
	 * @param mixed $official
	 */
	public function setOfficial($official) {
		parent::setOfficial($official);

		//If this is official, all of its textures must be as well.
		// Note the inverse does not also hold.
		if ($official) {
			foreach ($this->textures as $texture) {
				/* @var Texture $texture */
				$texture->setOfficial(true);
			}
		}
	}
}
