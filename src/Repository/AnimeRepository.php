<?php

namespace App\Repository;

use App\Entity\Anime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Anime>
 */
class AnimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anime::class);
    }

    /**
     * @return array{items: list<Anime>, total: int, pages: int, page: int, limit: int}
     */
    public function paginateAll(int $page = 1, ?string $searchTerm = null, ?int $limit = 50): array
    {
        $page = max(1, $page);
        $qb = $this->createQueryBuilder('a')
            ->orderBy('a.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($searchTerm !== null && $searchTerm !== '') {
            error_log(var_export('%'.$searchTerm.'%', true));
            $qb->andWhere('LOWER(a.title) LIKE LOWER(:searchTerm)')
                ->andWhere('LOWER(a.titleEnglish) LIKE LOWER(:searchTerm)')
                ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }

        $paginator = new Paginator($qb->getQuery(), true);
        $total = count($paginator);
        $pages = (int) max(1, ceil($total / $limit));
        $animes = [];

        foreach ($paginator as $row) {
            $animes[] = $row;
        }

        return [
            'animes' => $animes,
            'total' => $total,
            'pages' => $pages,
            'page'  => min($page, $pages),
            'limit' => $limit,
        ];
    }
}
