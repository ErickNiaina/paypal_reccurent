<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function updateUserHaveSubscribe($payer_id,$profile_id,$user_id){//update champs payer_id et profile_id
        $qb = $this->createQueryBuilder('u');
        $q = $qb->update(User::class, 'u')
            ->set('u.payer_id', $qb->expr()->literal($payer_id))
            ->set('u.profile_id', $qb->expr()->literal($profile_id))
            ->where('u.id = '.$user_id)
            ->getQuery();
            $q->execute();
        //var_dump($q);die;
    }

    public function updateEndSubscriptionUser($end_subscription,$user_id){//update champs endsubscription
        $qb = $this->createQueryBuilder('u');
        $q = $qb->update(User::class, 'u')
            ->set('u.end_subscription', $qb->expr()->literal($end_subscription))
            ->where('u.id = '.$user_id)
            ->getQuery();
            $q->execute();
        
    }

    public function updateProfileId($profile_idNull,$user_id){//update champs 
        $qb = $this->createQueryBuilder('u');
        $q = $qb->update(User::class, 'u')
            ->set('u.profile_id',$qb->expr()->literal($profile_idNull))
            ->where('u.id = '.$user_id)
            ->getQuery();
           $q->execute();
        
    }
}
