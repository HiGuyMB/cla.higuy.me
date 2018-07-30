<?php

namespace CLAList\Model\Entity;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;

/** @MappedSuperclass */
abstract class AbstractEntity {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue()
	 */
	protected $id;

	/**
	 * @Column(type="datetime", name="add_time")
	 */
	protected $addTime;

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	protected $constructed = false;
	private static $unflushed = [];

	public function __construct() {
		$this->constructed = true;
		$this->addTime = new DateTime();
	}

	public function isConstructed() {
		return $this->constructed;
	}

	protected static function findUnflushed(array $mapping) {
		$class = static::class;
		if (!array_key_exists($class, self::$unflushed)) {
			return null;
		}

		//Check unflushed for this
		foreach (self::$unflushed[$class] as $item) {
			//Check all fields for this item
			if (self::matchFields($item, $mapping)) {
				return $item;
			}
		}
		return null;
	}

	protected static function matchFields($item, array $mapping) {
		foreach ($mapping as $field => $value) {
			$fieldGetter = \Closure::bind(function ($item) use($field) {
				return $item->$field;
			}, null, get_class($item));
			if ($fieldGetter($item) !== $value) {
				return false;
			}
		}
		return true;
	}

	public static function find(array $mapping, array $constructorArgs = [], $construct = true) {
		$em = GetEntityManager();

		$class = static::class;
		//See if the db has it first
		$obj = $em->getRepository($class)->findOneBy($mapping);
		//No? See if it's waiting to be entered
		if ($obj === null) {
			$obj = static::findUnflushed($mapping);
		}
		//Then I guess we get to make a new one
		if ($obj === null && $construct) {
			$obj = static::construct($constructorArgs);
		}

		return $obj;
	}

	public static function construct(array $constructorArgs = []) {
		$em = GetEntityManager();
		$class = static::class;

		//This is apparently possible in php
		$obj = new $class(...$constructorArgs);
		$em->persist($obj);

		//Record the new obj for later
		if (!array_key_exists($class, self::$unflushed)) {
			self::$unflushed[$class] = [];
		}
		self::$unflushed[$class][] = $obj;

		return $obj;
	}

	public static function destruct($item) {
		//Remove it from the db at the next flush
		$em = GetEntityManager();
		$em->remove($item);

		//Remove it from the cache lists
		$class = get_class($item);
		if (array_key_exists($class, self::$unflushed)) {
			$array = &self::$unflushed[$class];
			if (($index = array_search($item, $array)) !== false) {
				array_splice($array, $index, 1);
			}
		}
	}
}
