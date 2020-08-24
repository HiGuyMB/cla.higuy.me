<?php

namespace CLAList\Model\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="uxwba_mission_ratings")
 */
class Rating extends AbstractEntity {
	/**
	 * @Column(type="string", name="user")
	 */
	private $user;
	/**
	 * @ManyToOne(targetEntity="Mission", inversedBy="ratings")
	 * @JoinColumn(name="mission_id", referencedColumnName="id", onDelete="CASCADE")
	 */
	private $mission;
	/**
	 * @Column(type="integer", name="value")
	 */
	private $value;
	/**
	 * @Column(type="integer", name="weight")
	 */
	private $weight;

	/**
	 * Rating constructor.
	 * @param $user
	 * @param $mission
	 * @param $value
	 * @param $weight
	 */
	public function __construct($user, $mission, $value, $weight) {
		parent::__construct();
		$this->user = $user;
		$this->mission = $mission;
		$this->value = $value;
		$this->weight = $weight;
	}


	/**
	 * @return mixed
	 */
	public function getUser() {
		return $this->user;
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
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return mixed
	 */
	public function getWeight() {
		return $this->weight;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	/**
	 * @param mixed $weight
	 */
	public function setWeight($weight) {
		$this->weight = $weight;
	}

}