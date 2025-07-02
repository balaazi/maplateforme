<?php

namespace App\Repository;

use App\Entity\Departement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Departement>
 */
class DepartementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Departement::class);
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('d.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCode(string $code): ?Departement
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }
} 