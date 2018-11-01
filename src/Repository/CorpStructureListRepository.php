<?php

namespace App\Repository;

use App\Entity\CorpStructureList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

use App\CouchDB\DocumentManager;

/**
 * @method CorpStructureList|null find($id, $lockMode = null, $lockVersion = null)
 * @method CorpStructureList|null findOneBy(array $criteria, array $orderBy = null)
 * @method CorpStructureList[]    findAll()
 * @method CorpStructureList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CorpStructureListRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CorpStructureList::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null)
    {
        if(is_array($id)){
            $id = $id['_id'];
        }
        $dm = new DocumentManager('users');
        $userDoc = $dm->getById($id);
        if(!empty($userDoc)){
            $user = new User();
            $user->setCharacterId($userDoc['_id']);
            $user->setCharacterName($userDoc['characterName']);
            $user->setRoles($userDoc['roles']);
            $user->setParentCharacterId($userDoc['parentCharacterId']);
            $user->setAccessToken($userDoc['accessToken']);
            $user->setRefreshToken($userDoc['refreshToken']);
            return $user;
        } else {
            return null;
        }
        
    }

//    /**
//     * @return CorpStructureList[] Returns an array of CorpStructureList objects
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
    public function findOneBySomeField($value): ?CorpStructureList
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
