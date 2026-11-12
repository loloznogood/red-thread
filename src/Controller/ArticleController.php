<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CategoryRepository;
use App\Service\ArticleSlugGenerator;
use App\Security\Voter\ArticleVoter;
use App\Dto\ArticleListQuery;
use App\Dto\CreateArticleDto;
use App\Entity\Article;
use App\Entity\User;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;

final class ArticleController extends AbstractController
{
    #[Route('/api/articles', name: 'api_article_create', methods: ['POST'], format: 'json')]
    #[IsGranted('ROLE_AUTHOR')]
    public function create(
        #[MapRequestPayload] CreateArticleDto $createArticleDto,
        #[CurrentUser] User $user,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        ArticleSlugGenerator $slugGenerator
    ): JsonResponse {
        $article = new Article();
        $category = $categoryRepository->find($createArticleDto->categoryId);
        $article->setAuthor($user);
        $article->setCategory($category);
        $article->setTitle($createArticleDto->title);
        $article->setSlug($slugGenerator->generate($createArticleDto->title));
        $article->setDescription($createArticleDto->description);
        $article->setContent($createArticleDto->content);

        $entityManager->persist($article);
        $entityManager->flush();

        return $this->json([
            'message' => 'Article created!',
            'article' => [
                'id' => $article->getId(),
                'author' => $user->getUsername(),
                'category' => $category->getName(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'description' => $article->getDescription(),
                'content' => $article->getContent(),
                'status' => $article->getStatus()->value,
                'createdAt' => $article->getCreatedAt()
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/articles/{id}/publish', name: 'api_article_publish', methods: ['POST'], format: 'json')]
    #[IsGranted(ArticleVoter::PUBLISH, subject: 'article')]
    public function publish(
        Article $article,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        try {
            $article->publish();
        } catch (\LogicException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Article published!',
            'article' => [
                'id' => $article->getId(),
                'status' => $article->getStatus()->value,
                'publishedAt' => $article->getPublishedAt()
            ]
        ]);
    }

    #[Route('/api/articles', name: 'api_article_list', methods: ['GET'], format: 'json')]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] ArticleListQuery $articleList,
        ArticleRepository $articleRepository,
    ): JsonResponse {

        $items = [];

        try {
            $page = $articleRepository->findPublishedPaginated($articleList->page, $articleList->limit);
        } catch (\LogicException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_CONFLICT);
        }

        foreach ($page as $article) {
            $items[] = [
                'id' => $article->getId(),
                'author' => $article->getAuthor()->getUsername(),
                'category' => $article->getCategory()->getName(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'description' => $article->getDescription(),
                'publishedAt' => $article->getPublishedAt(),
            ];
        }

        return $this->json([
            'items' => $items,
            'pagination' => [
                'page' => $articleList->page,
                'limit' => $articleList->limit,
                'total' => $page->getTotalCount(),
                'totalPages' => (int) ceil($page->getTotalCount() / $articleList->limit),
            ],
        ]);
    }

    #[Route('/api/articles/{slug}', name: 'api_article_show', methods: ['GET'], format: 'json')]
    #[IsGranted(ArticleVoter::VIEW, subject: 'article', statusCode: 404)]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Article $article,
    ): JsonResponse {
        return $this->json([
            'article' => [
                'id' => $article->getId(),
                'author' => $article->getAuthor()->getUsername(),
                'category' => $article->getCategory()->getName(),
                'title' => $article->getTitle(),
                'slug' => $article->getSlug(),
                'description' => $article->getDescription(),
                'content' => $article->getContent(),
                'status' => $article->getStatus()->value,
                'publishedAt' => $article->getPublishedAt(),
                'updatedAt' => $article->getUpdatedAt(),
            ],
        ]);
    }
}
