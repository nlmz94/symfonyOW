<?php

namespace App\Repository;

use App\Entity\Character;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    public function findOneByAnilistId(int $id): ?Character
    {
        return $this->findOneBy(['anilistId' => $id]);
    }
}
