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

	public function __construct() {
		parent::__construct();
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
