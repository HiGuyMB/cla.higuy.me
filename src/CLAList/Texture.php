<?php
namespace CLAList;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Column;

/**
 * @Entity
 * @Table(name="uxwba_textures")
 */
class Texture {
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
	/** @Column(type="string", length=128) */
	private $hash;

	public function __construct($gamePath) {
		$this->filePath = $gamePath;
		$this->baseName = basename($gamePath);
		$this->hash = GetHash(GetRealPath($gamePath));
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getBaseName() {
		return $this->baseName;
	}

	/**
	 * @param mixed $baseName
	 */
	public function setBaseName($baseName) {
		$this->baseName = $baseName;
	}

	/**
	 * @return mixed
	 */
	public function getFilePath() {
		return $this->filePath;
	}

	/**
	 * @param mixed $filePath
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
	 * @return mixed
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @param mixed $hash
	 */
	public function setHash($hash) {
		$this->hash = $hash;
	}
}
