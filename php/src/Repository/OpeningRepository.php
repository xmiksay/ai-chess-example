<?php

namespace App\Repository;

use App\Entity\Opening;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Opening>
 */
class OpeningRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Opening::class);
    }

    /** @return list<Opening> */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('o')->orderBy('o.name', 'ASC')->getQuery()->getResult();
    }
}
