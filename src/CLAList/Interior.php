<?php
namespace CLAList;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;

/**
 * @Entity
 * @Table(name="uxwba_interiors")
 */
class Interior {
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue()
     */
    private $id;
    /** @Column(type="string", length=128, name="base_name") */
    private $baseName;
    /** @Column(type="string", length=256, unique=true, name="file_path") */
    private $filePath;
    /** @Column(type="string", length=128) */
    private $hash;

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getBaseName() {
        return $this->baseName;
    }

    /**
     * @param string $baseName
     */
    public function setBaseName($baseName) {
        $this->baseName = $baseName;
    }

    /**
     * @return string
     */
    public function getFilePath() {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath) {
        $this->filePath = $filePath;
    }

    /**
     * @return string
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash) {
        $this->hash = $hash;
    }
}
