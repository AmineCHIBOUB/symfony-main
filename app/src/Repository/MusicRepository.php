<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Music;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Music>
 */
class MusicRepository extends ServiceEntityRepository
{
    public const PAR_PAGE = 12;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Music::class);
    }

    /**
     * Recherche paginée par texte (titre ou artiste) et par catégorie.
     *
     * @param string $tri "recent", "titre" ou "populaire"
     *
     * @return Paginator<Music>
     */
    public function search(?string $texte, ?Category $category, string $tri = 'recent', int $page = 1): Paginator
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.category', 'c')
            ->addSelect('c');

        if ($texte = trim((string) $texte)) {
            $qb->andWhere('LOWER(m.name) LIKE :q OR LOWER(m.auteur) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($texte).'%');
        }

        if ($category) {
            $qb->andWhere('m.category = :cat')->setParameter('cat', $category);
        }

        match ($tri) {
            'titre' => $qb->orderBy('m.name', 'ASC'),
            'populaire' => $qb->leftJoin('m.favoredBy', 'f')
                ->addSelect('COUNT(f.id) AS HIDDEN nbFavoris')
                ->groupBy('m.id, c.id')
                ->orderBy('nbFavoris', 'DESC')
                ->addOrderBy('m.createdAt', 'DESC'),
            default => $qb->orderBy('m.createdAt', 'DESC')->addOrderBy('m.id', 'DESC'),
        };

        $qb->setFirstResult((max(1, $page) - 1) * self::PAR_PAGE)
            ->setMaxResults(self::PAR_PAGE);

        return new Paginator($qb, fetchJoinCollection: false);
    }

    /**
     * @return Music[]
     */
    public function findLatest(int $limit = 6): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.category', 'c')
            ->addSelect('c')
            ->orderBy('m.createdAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Morceaux les plus ajoutés en favoris.
     *
     * @return array<array{music: Music, total: int}>
     */
    public function findMostLiked(int $limit = 5): array
    {
        $rows = $this->createQueryBuilder('m')
            ->select('m AS music, COUNT(f.id) AS total')
            ->innerJoin('m.favoredBy', 'f')
            ->groupBy('m.id')
            ->orderBy('total', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn (array $r) => ['music' => $r['music'], 'total' => (int) $r['total']], $rows);
    }

    /**
     * Autres morceaux de la même catégorie.
     *
     * @return Music[]
     */
    public function findSimilar(Music $music, int $limit = 4): array
    {
        if (!$music->getCategory()) {
            return [];
        }

        return $this->createQueryBuilder('m')
            ->andWhere('m.category = :cat')
            ->andWhere('m != :music')
            ->setParameter('cat', $music->getCategory())
            ->setParameter('music', $music)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
