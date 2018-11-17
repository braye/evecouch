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
        $dm = new DocumentManager('corp_taxes');
        $doc = $dm->getById($id);
        if(!empty($doc)){
            $obj = new CorpTaxableTransaction();
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

    public function getMultiCorpTaxView(array $corpIds, int $year, int $month)
    {
        $keys = [];
        foreach($corpIds as $corp){
            $keys[] = [$corp, $year, $month];
        }

        $dm = new DocumentManager('corp_taxes');
        $query = $dm->createViewQuery('taxesByMonth', 'corp-taxes-by-month');
        $query->setReduce(true);
        $query->setGroup(true);
        $query->setKeys($keys);
        return $query->execute();
    }

    public function getCorpTaxView(int $corpId, int $year, int $month)
    {
        $dm = new DocumentManager('corp_taxes');
        $query = $dm->createViewQuery('taxesByMonth', 'corp-taxes-by-month');
        $query->setReduce(true);
        $query->setKey([$corpId, $year, $month]);
        return $query->execute();
    }

}
