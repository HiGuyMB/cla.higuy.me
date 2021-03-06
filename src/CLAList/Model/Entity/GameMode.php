<?php
namespace CLAList\Model\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;


use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="uxwba_game_modes")
 */
class GameMode extends AbstractEntity {
	/**
	 * @Column(length=32, unique=true)
	 */
	private $name;

	public function __construct($name) {
		parent::__construct();
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}
