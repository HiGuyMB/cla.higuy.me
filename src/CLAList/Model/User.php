<?php


namespace CLAList\Model;

use CLAList\Model\Entity\AbstractEntity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="uxwba_users")
 */
class User extends AbstractEntity {
	/**
	 * @Column(type="string", name="username")
	 */
	private $username;
	/**
	 * @Column(type="string", name="password")
	 */
	private $password;

	public function __construct($username) {
		parent::__construct();
	}
}