<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CategoryRepository;
use App\Service\ArticleSlugGenerator;
use App\Security\Voter\ArticleVoter;
use App\Dto\CreateArticleDto;
use App\Entity\Article;
use App\Entity\User;

final class ArticleController extends AbstractController
{
    #[Route('/api/articles', name: 'api_article_create', methods: ['POST'], format: 'json')]
    #[IsGranted('ROLE_AUTHOR')]
    public function createArticle(
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
        ], Response::HTTP_OK);
    }
}
