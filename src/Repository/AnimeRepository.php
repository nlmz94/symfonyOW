<?php

namespace App\Repository;

use App\Entity\Anime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Anime>
 */
class AnimeRepository extends ServiceEntityRepository
{
    public const int MAX_LIMIT = 100;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anime::class);
    }

    /**
     * @return array{items: list<Anime>, total: int, pages: int, page: int, limit: int}
     */
    public function paginateAll(int $page = 1, ?string $searchTerm = null, int $limit = 50): array
    {
        $page = max(1, $page);
        $limit = max(1, min(self::MAX_LIMIT, $limit));

        $qb = $this->createListQueryBuilder()
            ->orderBy('a.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($searchTerm !== null && $searchTerm !== '') {
            $qb->andWhere('LOWER(a.title) LIKE LOWER(:searchTerm) OR LOWER(a.titleEnglish) LIKE LOWER(:searchTerm)')
                ->setParameter('searchTerm', '%'.addcslashes($searchTerm, '%_\\').'%');
        }

        $query = $qb->getQuery();
        $query->enableResultCache(36000);
        $paginator = new Paginator($query, true);
        $total = count($paginator);
        $pages = (int) max(1, ceil($total / $limit));

        /** @var list<Anime> $items */
        $items = iterator_to_array($paginator, false);

        return [
            'items' => $items,
            'total' => $total,
            'pages' => $pages,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Loads everything the detail endpoint serializes in a handful of queries
     * instead of one per character / staff member.
     */
    public function findDetail(int $id): ?Anime
    {
        /** @var Anime|null $anime */
        $anime = $this->createQueryBuilder('a')
            ->addSelect('p', 'g', 'pr', 's')
            ->leftJoin('a.pegi', 'p')
            ->leftJoin('a.genres', 'g')
            ->leftJoin('a.producers', 'pr')
            ->leftJoin('a.studios', 's')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if ($anime === null) {
            return null;
        }

        // Separate queries: joining both collections at once would multiply rows.
        $this->createQueryBuilder('a')
            ->select('a', 'ac', 'c', 'va')
            ->leftJoin('a.characters', 'ac')
            ->leftJoin('ac.character', 'c')
            ->leftJoin('ac.voiceActor', 'va')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();

        $this->createQueryBuilder('a')
            ->select('a', 'ast', 'st')
            ->leftJoin('a.staff', 'ast')
            ->leftJoin('ast.staff', 'st')
            ->where('a.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();

        return $anime;
    }

    /** @return list<Anime> */
    public function findTrending(int $limit = 12): array
    {
        return $this->fetchList(
            $this->createListQueryBuilder()
                ->where('a.status = :status')
                ->setParameter('status', 'RELEASING')
                ->orderBy('a.popularity', 'DESC'),
            $limit,
        );
    }

    /** @return list<Anime> */
    public function findTopRated(int $limit = 12): array
    {
        return $this->fetchList(
            $this->createListQueryBuilder()
                ->where('a.averageScore IS NOT NULL')
                ->orderBy('a.averageScore', 'DESC')
                ->addOrderBy('a.popularity', 'DESC'),
            $limit,
        );
    }

    /** @return list<Anime> */
    public function findRecentlyAired(int $limit = 12): array
    {
        return $this->fetchList(
            $this->createListQueryBuilder()
                ->where('a.startDate <= :today')
                ->setParameter('today', new \DateTimeImmutable('today'))
                ->orderBy('a.startDate', 'DESC'),
            $limit,
        );
    }

    /**
     * Everything AnimeSerializer::baseSerialize() touches, fetch-joined.
     */
    private function createListQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->addSelect('p', 'g')
            ->leftJoin('a.pegi', 'p')
            ->leftJoin('a.genres', 'g');
    }

    /** @return list<Anime> */
    private function fetchList(QueryBuilder $qb, int $limit): array
    {
        $query = $qb->setMaxResults($limit)->getQuery();
        $query->enableResultCache(3600);

        /** @var list<Anime> */
        return iterator_to_array(new Paginator($query, true), false);
    }
}
