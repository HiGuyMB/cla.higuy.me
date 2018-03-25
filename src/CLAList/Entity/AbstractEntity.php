<?php

namespace CLAList\Entity;

use Doctrine\ORM\EntityManager;
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
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	protected $constructed;
	private static $unflushed = [];

	public function __construct() {
		$this->constructed = true;
	}

	public function isConstructed() {
		return $this->constructed;
	}

	private static function findUnflushed(array $mapping) {
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
	}

	private static function matchFields($item, array $mapping) {
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

	public static function find(array $mapping, array $constructorArgs = []) {
		$em = GetEntityManager();

		$class = static::class;
		//See if the db has it first
		$obj = $em->getRepository($class)->findOneBy($mapping);
		//No? See if it's waiting to be entered
		if ($obj === null) {
			$obj = self::findUnflushed($mapping);
		}
		//Then I guess we get to make a new one
		if ($obj === null) {
			//This is apparently possible in php
			$obj = new $class(...$constructorArgs);
			$em->persist($obj);

			//Record the new obj for later
			if (!array_key_exists($class, self::$unflushed)) {
				self::$unflushed[$class] = [];
			}
			self::$unflushed[$class][] = $obj;
		} else {
			print("Unflused got one!\n");
		}

		return $obj;
	}

}
