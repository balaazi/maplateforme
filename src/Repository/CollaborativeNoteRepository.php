<?php

namespace App\Repository;

use App\Entity\CollaborativeNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollaborativeNote>
 *
 * @method CollaborativeNote|null find($id, $lockMode = null, $lockVersion = null)
 * @method CollaborativeNote|null findOneBy(array $criteria, array $orderBy = null)
 * @method CollaborativeNote[]    findAll()
 * @method CollaborativeNote[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollaborativeNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollaborativeNote::class);
    }

    public function findByEvent($event)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.event = :event')
            ->setParameter('event', $event)
            ->orderBy('n.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 