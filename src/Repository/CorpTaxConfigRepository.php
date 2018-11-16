<?php

namespace App\Repository;

use App\Entity\CorpTaxConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\CouchDB\DocumentManager;

/**
 * @method CorpTaxConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method CorpTaxConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method CorpTaxConfig[]    findAll()
 * @method CorpTaxConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CorpTaxConfigRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CorpTaxConfig::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null)
    {
        if(is_array($id)){
            $id = $id['_id'];
        }
        $dm = new DocumentManager('corp_tax_config');
        $doc = $dm->getById($id);
        if(!empty($doc)){
            $obj = new CorpTaxConfig();
            $obj->setAllianceId($doc['_id']);
            $obj->setTaxRate($doc['taxRate']);
            return $obj;
        } else {
            return null;
        }
    }

    /*
    public function findOneBySomeField($value): ?CorpTaxConfig
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
