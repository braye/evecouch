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
            $user->setEveRoles($userDoc['eveRoles']);
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

    public function getDirectorsByCorp(string $corpId)
    {
        $dm = new DocumentManager('users');
        $query = $dm->createViewQuery('corpUsers', 'corp-directors');
        $query->setKey($corpId);
        $query->setIncludeDocs(true);
        return $query->execute();
    }
}
