<?php
namespace CLAList\Entity;

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
class Skybox extends AbstractGameEntity {
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

			$gamePath = GetGamePath($image);

			//Make a texture object for us
			$texObj = Texture::findByGamePath($gamePath);
			$this->textures->add($texObj);
		}
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

}
