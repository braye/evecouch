<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\CouchDB\DocumentManager;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
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
            $user->setCorporationId($userDoc['corporationId']);
            return $user;
        } else {
            return null;
        }
        
    }

    public function findOneBy(array $criteria, array $orderBy = null)
    {
        if(!empty($criteria['_id'])){
            return $this->find($criteria['_id']);
        } else {
            return null;
        }
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
