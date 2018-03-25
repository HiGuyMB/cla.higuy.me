<?php

namespace CLAList\Entity;

use CLAList\Entity\AbstractEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;

/** @MappedSuperclass */
abstract class AbstractGameEntity extends AbstractEntity {
	/** @Column(type="string", length=128, name="base_name") */
	protected $baseName;
	/** @Column(type="string", length=256, unique=true, name="game_path") */
	protected $gamePath;
	/** @Column(type="string", length=128) */
	protected $hash;

	public function __construct($gamePath) {
		parent::__construct();
		$this->gamePath = $gamePath;
		$this->baseName = basename($gamePath);
		$this->hash = GetHash(GetRealPath($gamePath));
	}

	/**
	 * Find or construct an instance of this game entity with the given game path
	 * @param string $gamePath Game path
	 * @return null|object
	 */
	public static function findByGamePath($gamePath) {
		return self::find(["gamePath" => $gamePath], [$gamePath]);
	}

	/**
	 * @return string
	 */
	public function getBaseName() {
		return $this->baseName;
	}

	/**
	 * @return string
	 */
	public function getGamePath() {
		return $this->gamePath;
	}

	/**
	 * @return string
	 */
	public function getRealPath() {
		return GetRealPath($this->gamePath);
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * If the Shape actually exists on disk
	 * @return bool
	 */
	public function exists() {
		return is_file($this->getRealPath());
	}
}
