<?php

namespace CLAList\Entity;

use CLAList\Paths;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappedSuperclass;

/** @MappedSuperclass */
abstract class AbstractGameEntity extends AbstractEntity {
	/** @Column(type="string", length=128, name="base_name") */
	protected $baseName;
	/** @Column(type="string", length=256, unique=true, name="game_path") */
	protected $gamePath;
	/** @Column(type="string", length=128, nullable=true) */
	protected $hash = null;
	/** @Column(type="boolean") */
	protected $official = false;

	protected $realPath;

	public function __construct($gamePath, $realPath = null) {
		parent::__construct();
		$this->gamePath = $gamePath;
		$this->realPath = $realPath ?? Paths::getRealPath($gamePath);
		$this->baseName = basename($gamePath);
		$this->hash = Paths::getHash($this->realPath);
	}

	private static $unflushed = [];

	protected static function findUnflushed(array $mapping) {
		if (count(array_keys($mapping)) === 1 && array_keys($mapping)[0] === "gamePath") {
			$class = static::class;
			$path = strtolower($mapping["gamePath"]);
			if (array_key_exists($class, self::$unflushed) &&
				array_key_exists($path, self::$unflushed[$class])) {

				//Check unflushed for this
				foreach (self::$unflushed[$class][$path] as $item) {
					/** @var AbstractGameEntity $item */

					if (strtolower($item->getGamePath()) === $path) {
						return $item;
					}
				}
			}
		}
		return parent::findUnflushed($mapping);
	}

	public static function construct(array $constructorArgs = []) {
		/** @var AbstractGameEntity $obj */
		$obj = parent::construct($constructorArgs);

		$class = static::class;
		$path = strtolower($obj->getGamePath());

		//Record the new obj for later
		if (!array_key_exists($class, self::$unflushed)) {
			self::$unflushed[$class] = [];
		}
		if (!array_key_exists($path, self::$unflushed[$class])) {
			self::$unflushed[$class][$path] = [$obj];
		}

		return $obj;
	}

	public static function destruct($item) {
		/** @var AbstractGameEntity $item */

		//Remove it from the cache lists
		$class = get_class($item);
		$path = strtolower($item->getGamePath());
		if (array_key_exists($class, self::$unflushed) &&
			array_key_exists($path, self::$unflushed[$class])) {
			$array = &self::$unflushed[$class][$path];
			if (($index = array_search($item, $array)) !== false) {
				array_splice($array, $index, 1);
			}
		}

		parent::destruct($item);
	}


	/**
	 * Find or construct an instance of this game entity with the given game path
	 * @param string $gamePath Game path
	 * @param boolean $construct If a new entity should be created if one does not exist
	 * @return null|AbstractGameEntity
	 */
	public static function findByGamePath($gamePath, $construct = true) {
		return self::find(["gamePath" => $gamePath], [$gamePath], $construct);
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
		if ($this->realPath === null) {
			$this->realPath = Paths::getRealPath($this->getGamePath());
		}
		return $this->realPath;
	}

	/**
	 * @return string
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @return mixed
	 */
	public function getOfficial() {
		return $this->official;
	}

	/**
	 * @param mixed $official
	 */
	public function setOfficial($official) {
		$this->official = $official;
	}

	/**
	 * If the Shape actually exists on disk
	 * @return bool
	 */
	public function exists() {
		return is_file($this->getRealPath());
	}
}
