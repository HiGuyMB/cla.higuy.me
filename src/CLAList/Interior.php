<?php
namespace CLAList;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;

/**
 * @Entity
 * @Table(name="uxwba_interiors")
 */
class Interior {
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue()
     */
    private $id;
    /** @Column(type="string", length=128, name="base_name") */
    private $baseName;
    /** @Column(type="string", length=256, unique=true, name="file_path") */
    private $filePath;
    /** @Column(type="string", length=128) */
    private $hash;
	/**
	 * @ManyToMany(targetEntity="Texture", cascade={"persist", "detach"})
	 * @JoinTable(name="uxwba_interior_textures",
	 *     joinColumns={@JoinColumn(name="interior_id", referencedColumnName="id")},
	 *     inverseJoinColumns={@JoinColumn(name="texture_id", referencedColumnName="id")})
	 * )
	 */
	private $textures;

    function __construct($filePath) {
    	$this->baseName = basename($filePath);
    	$this->filePath = $filePath;
    	$this->hash = GetHash(GetRealPath($filePath));
	    $this->textures = new ArrayCollection();
	    $this->loadFile();
    }

	/**
	 * Get the textures used by this interior
	 */
	public function loadFile() {
		if (!is_file($this->getRealPath())) {
			echo("Cannot load interior: file does not exist\n");
			return;
		}

		$em = GetEntityManager();

		//Run DifTests on it
		$descriptors = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);

		$command = BASE_DIR . "/util/difutil --textures " . escapeshellarg($this->getRealPath());
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
			foreach ($textures as $texture) {
				//Resolve the name
				$image = ResolveTexture(pathinfo($this->getRealPath(), PATHINFO_DIRNAME), $texture);

				if ($image == null) {
					//Didn't work? Just use the default
					$image = dirname($this->getFilePath()) . "/" . $texture;
				}

				$filePath = GetGamePath($image);

				//Make a texture object for us
				$texObj = $em->getRepository('CLAList\Texture')->findOneBy(["filePath" => $filePath]);
				if ($texObj === null) {
					$texObj = new Texture($filePath);
				}

				$this->textures->add($texObj);
			}
		} else {
			//??
			echo("Could not exec difutil\n");
		}
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
     * @return string
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash) {
        $this->hash = $hash;
    }

	/**
	 * @return Collection
	 */
	public function getTextures(): Collection {
		return $this->textures;
	}
}
