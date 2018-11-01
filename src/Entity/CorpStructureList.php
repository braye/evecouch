<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\CouchDB\CouchEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CorpStructureListRepository")
 */
class CorpStructureList extends CouchEntity
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    protected $_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $updated;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $structures;

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function getUpdated(): ?string
    {
        return $this->updated;
    }

    public function setUpdated(string $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getCorporationId(): ?string
    {
        return $this->_id;
    }

    public function setCorporationId(string $_id): self
    {
        $this->_id = $_id;

        return $this;
    }

    public function getStructures(): ?array
    {
        return $this->structures;
    }

    public function setStructures(array $structures): self
    {
        $this->structures = $structures;

        return $this;
    }
}
