<?php
namespace CLAList\Entity;

use CLAList\Entity\Mission;
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
class Field extends AbstractEntity {
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
		parent::__construct();
		$this->mission = $mission;
		$this->name = $name;
		$this->value = $value;
	}

	/**
	 * @return mixed
	 */
	public function getMission() {
		return $this->mission;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}
}
