<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\CouchDB\CouchEntity;


/**
 * @ORM\Entity(repositoryClass="App\Repository\CorpTaxableTransactionRepository")
 */
class CorpTaxableTransaction extends CouchEntity
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    protected $_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $amount;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $refType;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $corporationId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $month;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $year;

    public function getTransactionId(): ?int
    {
        return $this->_id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getRefType(): ?string
    {
        return $this->refType;
    }

    public function setRefType(string $refType): self
    {
        $this->refType = $refType;

        return $this;
    }

    public function getCorporationId(): ?string
    {
        return $this->corporationId;
    }

    public function setCorporationId(string $corporationId): self
    {
        $this->corporationId = $corporationId;

        return $this;
    }

    public function setTransactionId(string $_id): self
    {
        $this->_id = $_id;

        return $this;
    }

    public function getMonth(): ?string
    {
        return $this->month;
    }

    public function setMonth(string $month): self
    {
        $this->month = $month;

        return $this;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(string $year): self
    {
        $this->year = $year;

        return $this;
    }
}
