<?php
namespace CLAList;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="uxwba_mission_fields")
 */
class Field {
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	private $id;
	/**
	 * @ManyToOne(targetEntity="Mission", inversedBy="fields")
	 * @JoinColumn(name="mission_id", referencedColumnName="id")
	 */
	private $mission;
	/** @Column(length=64) */
	private $name;
	/** @Column(type="text") */
	private $value;

	public function __construct(Mission $mission, $name, $value) {
		$this->mission = $mission;
		$this->name = $name;
		$this->value = $value;
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
	public function getMission() {
		return $this->mission;
	}

	/**
	 * @param mixed $mission
	 */
	public function setMission($mission) {
		$this->mission = $mission;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}
}
