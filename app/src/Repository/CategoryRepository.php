<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Catégories triées par nom, avec leur nombre de morceaux.
     *
     * @return array<array{category: Category, total: int}>
     */
    public function findAllWithCount(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c AS category, COUNT(m.id) AS total')
            ->leftJoin('c.musics', 'm')
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn (array $r) => ['category' => $r['category'], 'total' => (int) $r['total']], $rows);
    }
}
