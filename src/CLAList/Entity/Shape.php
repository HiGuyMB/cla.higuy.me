<?php
namespace CLAList\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
 * @Table("uxwba_shapes")
 */
class Shape extends AbstractGameEntity {
	/**
	 * @ManyToMany(targetEntity="Texture", cascade={"persist", "detach"})
	 * @JoinTable(name="uxwba_shape_textures",
	 *     joinColumns={@JoinColumn(name="shape_id", referencedColumnName="id")},
	 *     inverseJoinColumns={@JoinColumn(name="texture_id", referencedColumnName="id")})
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

		$em = GetEntityManager();

		//Run DifTests on it
		$descriptors = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);

		$command = BASE_DIR . "/util/dtstextures " . escapeshellarg($this->getRealPath());
		$process = proc_open($command, $descriptors, $pipes);

		//If it went through...
		if (is_resource($process)) {

			//Get all the output
			$procOutput = stream_get_contents($pipes[1]);
			fclose($pipes[0]);
			fclose($pipes[1]);
			fclose($pipes[2]);

			proc_close($process);

			$textures = explode("\n", $procOutput);

			$textures = array_filter($textures);

			//Convert the names into actual files and check for missing textures
			foreach ($textures as $texture) {
				//Resolve the name
				$image = Texture::resolve(pathinfo($this->getRealPath(), PATHINFO_DIRNAME), $texture);

				if ($image == null) {
					//Didn't work? Just use the default
					$image = dirname($this->getGamePath()) . "/" . $texture;
				}

				$gamePath = GetGamePath($image);

				//Make a texture object for us
				$texObj = Texture::findByGamePath($gamePath);
				$this->textures->add($texObj);
			}
		} else {
			//??
			echo("Could not exec dtstextures\n");
		}
	}

	/**
	 * @return Collection
	 */
	public function getTextures(): Collection {
		return $this->textures;
	}
}
