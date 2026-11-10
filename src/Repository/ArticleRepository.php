<?php

namespace App\Repository;

use App\Entity\Article;
use App\Enum\ArticleStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
use Doctrine\ORM\Tools\Pagination\WindowPage;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findPublishedPaginated(int $page, int $limit = 20): WindowPage
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->leftJoin('a.author', 'u')
            ->addSelect('u')
            ->leftJoin('a.category', 'c')
            ->addSelect('c')
            ->andWhere('a.status = :status')
            ->setParameter('status', ArticleStatus::Published)
            ->orderBy('a.publishedAt', 'DESC');

        $paginator = new OffsetPaginator(fetchJoinCollection: false);

        return $paginator->paginate($queryBuilder, Window::fromPageNumberAndSize($page, $limit));
    }
}
