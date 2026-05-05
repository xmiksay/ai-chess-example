<?php

namespace App\Repository;

use App\Entity\Puzzle;
use App\Model\PuzzleFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Puzzle>
 */
class PuzzleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Puzzle::class);
    }

    /**
     * Keyset-paginated search on (rating, id).
     *
     * @return array{items: list<Puzzle>, nextCursor: ?string}
     */
    public function search(PuzzleFilter $f, int $size = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.rating', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->setMaxResults($size + 1);

        if ($f->minRating !== null) {
            $qb->andWhere('p.rating >= :minR')->setParameter('minR', $f->minRating);
        }
        if ($f->maxRating !== null) {
            $qb->andWhere('p.rating <= :maxR')->setParameter('maxR', $f->maxRating);
        }
        if ($f->q) {
            $qb->andWhere('p.id LIKE :q')->setParameter('q', $f->q . '%');
        }

        foreach ($f->themeIds as $i => $tid) {
            $qb->andWhere(sprintf(
                'EXISTS (SELECT 1 FROM App\\Entity\\Puzzle px%1$d JOIN px%1$d.themes t%1$d WHERE px%1$d = p AND t%1$d.id = :tid%1$d)',
                $i
            ))->setParameter('tid' . $i, $tid);
        }

        foreach ($f->openingIds as $i => $oid) {
            $qb->andWhere(sprintf(
                'EXISTS (SELECT 1 FROM App\\Entity\\Puzzle px%1$d JOIN px%1$d.openings o%1$d WHERE px%1$d = p AND o%1$d.id = :oid%1$d)',
                $i + 1000
            ))->setParameter('oid' . ($i + 1000), $oid);
        }

        if ($f->cursor) {
            [$cr, $cid] = explode('_', $f->cursor, 2);
            $qb->andWhere('(p.rating > :curR) OR (p.rating = :curR AND p.id > :curId)')
                ->setParameter('curR', (int) $cr)
                ->setParameter('curId', $cid);
        }

        /** @var list<Puzzle> $rows */
        $rows = $qb->getQuery()->getResult();
        $hasNext = count($rows) > $size;
        if ($hasNext) {
            array_pop($rows);
        }

        $next = null;
        if ($hasNext && !empty($rows)) {
            $last = end($rows);
            $next = $last->getRating() . '_' . $last->getId();
        }

        return ['items' => $rows, 'nextCursor' => $next];
    }
}
