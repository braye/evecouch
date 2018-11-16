<?php

namespace App\Repository;

use App\Entity\CorpTaxableTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\CouchDB\DocumentManager;

/**
 * @method CorpTaxableTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method CorpTaxableTransaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method CorpTaxableTransaction[]    findAll()
 * @method CorpTaxableTransaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CorpTaxableTransactionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CorpTaxableTransaction::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null)
    {
        if(is_array($id)){
            $id = $id['_id'];
        }
        $dm = new DocumentManager('corp_tax');
        $doc = $dm->getById($id);
        if(!empty($doc)){
            $obj = new CorpStructureList();
            $obj->setTransactionId($doc['_id']);
            $obj->setAmount($doc['amount']);
            $obj->setMonth($doc['month']);
            $obj->setYear($doc['year']);
            $obj->setRefType($doc['refType']);
            $obj->setCorporationId($doc['corporationId']);
            return $obj;
        } else {
            return null;
        }
    }

    public function getAllCorpTaxView($allianceId, $year, $month)
    {
        $dm = new DocumentManager('corp_tax');

    }

//    /**
//     * @return CorpTaxableTransaction[] Returns an array of CorpTaxableTransaction objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CorpTaxableTransaction
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
