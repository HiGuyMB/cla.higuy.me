<?php
namespace CLAList\Model\Entity;

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
 * @Table(name="uxwba_interiors")
 */
class Interior extends AbstractGameEntity {
	/**
	 * @ManyToMany(targetEntity="Texture", cascade={"persist", "detach"})
	 * @JoinTable(name="uxwba_interior_textures",
	 *     joinColumns={@JoinColumn(name="interior_id", referencedColumnName="id", onDelete="CASCADE")},
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
//			echo("Cannot load interior: file does not exist\n");
			return;
		}

		try {
			$textures = self::loadFileTextures($this->getRealPath());
		} catch (\Exception $e) {
			return;
		}

		//Convert the names into actual files and check for missing textures
		foreach ($textures as $texture) {
			$this->addTexture($texture);
		}
	}

	protected function addTexture($textureName) {
		//Resolve the name
		$image = Texture::resolve(pathinfo($this->getRealPath(), PATHINFO_DIRNAME), $textureName);

		if ($image == null) {
			//Didn't work? Just use the default
			$image = dirname($this->getGamePath()) . "/" . $textureName;
		}

		$gamePath = Paths::getGamePath($image);

		//If we don't already have this shape
		if (!$this->textures->exists(function($index, Texture $texture) use($gamePath) {
			return Paths::compare($texture->getGamePath(), $gamePath);
		})) {
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

		$command = Paths::getUtilityDir() . "/difutil --textures " . escapeshellarg($realPath);
		$process = proc_open($command, $descriptors, $pipes);

		//If it went through...
		if (is_resource($process)) {

			//Get all the output
			$procOutput = stream_get_contents($pipes[1]);
			fclose($pipes[0]);
			fclose($pipes[1]);
			fclose($pipes[2]);

			$responseCode = proc_close($process);

			if ($responseCode !== 0) {
				throw new \Exception("Load failed!");
			}

			$textures = explode("\n", $procOutput);

			//Strip album names from the textures
			$textures = array_map(function ($texture) {
				if (strpos($texture, "/") === false)
					return $texture;
				return substr($texture, strrpos($texture, "/") + 1);
			}, $textures);

			//Filter out the default texture names that don't have files
			$textures = array_filter($textures, function ($texture) {
				if ($texture == "NULL") return false;
				if ($texture == "ORIGIN") return false;
				if ($texture == "TRIGGER") return false;
				if ($texture == "FORCEFIELD") return false;
				if ($texture == "EMITTER") return false;
				if ($texture == "") return false;

				return true;
			});
			$textures = array_map(function($texture) {
				return strtolower($texture);
			}, $textures);

			//Remove duplicates which can happen if there are MPs
			$textures = array_unique($textures);
			return $textures;
		} else {
			//??
			echo("Could not exec difutil\n");
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
