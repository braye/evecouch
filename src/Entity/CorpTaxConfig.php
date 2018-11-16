<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\CouchDB\CouchEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CorpTaxConfigRepository")
 */
class CorpTaxConfig extends CouchEntity
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    protected $_id;

    /**
     * @ORM\Column(type="float")
     */
    protected $taxRate;

    public function getAllianceId(): ?string
    {
        return $this->_id;
    }

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function setId(string $id): self
    {
        $this->_id = $id;

        return $this;
    }

    public function setAllianceId(string $id): self
    {
        $this->_id = $id;

        return $this;
    }

    public function getTaxRate(): ?float
    {
        return $this->taxRate;
    }

    public function setTaxRate(float $taxRate): self
    {
        $this->taxRate = $taxRate;

        return $this;
    }
}
