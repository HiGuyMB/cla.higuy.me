<?php
namespace CLAList\Entity;

use CLAList\Paths;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping\Entity;


use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table("uxwba_shapes")
 */
class Shape extends AbstractGameEntity {
	/**
	 * @ManyToMany(targetEntity="Texture", cascade={"persist", "detach"})
	 * @JoinTable(name="uxwba_shape_textures",
	 *     joinColumns={@JoinColumn(name="shape_id", referencedColumnName="id", onDelete="CASCADE")},
	 *     inverseJoinColumns={@JoinColumn(name="texture_id", referencedColumnName="id", onDelete="CASCADE")})
	 * )
	 */
	private $textures;

	function __construct($gamePath, $realPath = null) {
		parent::__construct($gamePath, $realPath);
		$this->textures = new ArrayCollection();
		$this->loadFile();
	}

	/**
	 * Get the textures used by this interior
	 */
	public function loadFile() {
		if (!is_file($this->getRealPath())) {
			echo("Cannot load shape: file does not exist\n");
			return;
		}

		try {
			$textures = self::loadFileTextures($this->getRealPath());
		} catch (\Exception $e) {
			echo("Cannot load shape: " . $e->getMessage() . "\n");
			return;
		}

		//Convert the names into actual files and check for missing textures
		foreach ($textures as $texture) {
			//Resolve the name
			$image = Texture::resolve(pathinfo($this->getRealPath(), PATHINFO_DIRNAME), $texture);

			if ($image == null) {
				//Didn't work? Just use the default
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
	 * @throws \Exception
	 */
	public static function loadFileTextures($realPath): array {
		//Run DifTests on it
		$descriptors = [
			0 => ["pipe", "r"],
			1 => ["pipe", "w"],
			2 => ["pipe", "w"]
		];

		$command = Paths::getUtilityDir() . "/dtstextures " . escapeshellarg($realPath);
		$process = proc_open($command, $descriptors, $pipes);

		//If it went through...
		if (is_resource($process)) {

			//Get all the output
			$procOutput = stream_get_contents($pipes[1]);
			fclose($pipes[0]);
			fclose($pipes[1]);
			fclose($pipes[2]);

			$returnCode = proc_close($process);
			if ($returnCode !== 0) {
				throw new \Exception("dtstextures returned $returnCode");
			}

			$textures = explode("\n", $procOutput);

			$textures = array_map(function($texture) {
				return strtolower($texture);
			}, $textures);
			$textures = array_filter($textures);
			$textures = array_unique($textures);
			return $textures;
		} else {
			//??
			echo("Could not exec dtstextures\n");
			return [];
		}
	}

	/**
	 * @return Collection
	 */
	public function getTextures(): Collection {
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
