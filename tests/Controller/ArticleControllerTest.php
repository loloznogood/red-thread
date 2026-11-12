<?php

namespace App\Tests\Controller;

use App\Factory\ArticleFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Factory\CategoryFactory;
use App\Tests\AppWebTestCase;

final class ArticleControllerTest extends AppWebTestCase
{
    public function testAuthorCanCreateArticle(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);
        $category = CategoryFactory::createOne();

        // Act
        $data = $this->createArticle($client, $token, $this->validPayload($category->getId()));

        // Assert
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($author->getUsername(), $data['article']['author']);
        $this->assertSame('les-nouveautes-de-symfony-7-4', $data['article']['slug']);
        $this->assertSame('draft', $data['article']['status']);
    }

    public function testSameTitleGeneratesUniqueSlug(): void
    {
        // Arrange
        $client = static::createClient();
        $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);
        $category = CategoryFactory::createOne();

        // Act
        $this->createArticle($client, $token, $this->validPayload($category->getId()));
        $this->assertResponseStatusCodeSame(201);

        $data = $this->createArticle($client, $token, $this->validPayload($category->getId()));

        // Assert
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('les-nouveautes-de-symfony-7-4-2', $data['article']['slug']);
    }

    public function testCreateArticleWithoutTokenFails(): void
    {
        // Arrange
        $client = static::createClient();
        $category = CategoryFactory::createOne();

        // Act
        $this->createArticle($client, null, $this->validPayload($category->getId()));

        // Assert
        $this->assertResponseStatusCodeSame(401);
    }

    public function testReaderCannotCreateArticle(): void
    {
        // Arrange
        $client = static::createClient();
        $this->createUser('test@mail.com', 'motdepasse123!', []);
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);
        $category = CategoryFactory::createOne();

        // Act
        $this->createArticle($client, $token, $this->validPayload($category->getId()));

        // Assert
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateArticleWithUnknownCategoryFails(): void
    {
        // Arrange
        $client = static::createClient();
        $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);

        // Act
        $data = $this->createArticle($client, $token, $this->validPayload(999));

        // Assert
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('categoryId', $data['violations'][0]['propertyPath']);
    }

    public function testAuthorCanPublishOwnArticle(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);
        $article = ArticleFactory::createOne(['author' => $author]);

        // Act
        $data = $this->publishArticle($client, $token, $article->getId());

        // Assert
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('published', $data['article']['status']);
        $this->assertNotNull($data['article']['publishedAt']);
    }

    public function testCannotPublishArticleTwice(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);
        $article = ArticleFactory::createOne(['author' => $author]);
        $this->publishArticle($client, $token, $article->getId());
        $this->assertResponseStatusCodeSame(200);

        // Act
        $this->publishArticle($client, $token, $article->getId());

        // Assert
        $this->assertResponseStatusCodeSame(409);
    }

    public function testAuthorCannotPublishOtherAuthorArticle(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $this->createUser('admin@mail.com', 'motdepasse123*', ['ROLE_AUTHOR']);
        $adminToken = $this->login($client, ['email' => 'admin@mail.com', 'password' => 'motdepasse123*']);
        $article = ArticleFactory::createOne(['author' => $author]);

        // Act
        $this->publishArticle($client, $adminToken, $article->getId());

        // Assert
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanPublishAnyArticle(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $this->createUser('admin@mail.com', 'motdepasse123*', ['ROLE_ADMIN']);
        $otherToken = $this->login($client, ['email' => 'admin@mail.com', 'password' => 'motdepasse123*']);
        $article = ArticleFactory::createOne(['author' => $author]);

        // Act
        $this->publishArticle($client, $otherToken, $article->getId());

        // Assert
        $this->assertResponseStatusCodeSame(200);
    }

    public function testPublishUnknownArticleFails(): void
    {
        // Arrange
        $client = static::createClient();
        $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);

        // Act
        $this->publishArticle($client, $token, 99999);

        // Assert
        $this->assertResponseStatusCodeSame(404);
    }

    public function testListReturnsOnlyPublishedArticles(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        ArticleFactory::new()->published()->many(2)->create(['author' => $author]);
        $draft = ArticleFactory::createOne(['author' => $author]);

        // Act
        $data = $this->listArticles($client);

        // Assert
        $this->assertResponseStatusCodeSame(200);
        $ids = array_column($data['items'], 'id');
        $this->assertNotContains($draft->getId(), $ids);
    }

    public function testListIsPublic(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        ArticleFactory::new()->published()->create(['author' => $author]);

        // Act
        $this->listArticles($client, []);

        // Assert
        $this->assertResponseStatusCodeSame(200);
    }

    public function testListPagination(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        ArticleFactory::new()->published()->many(25)->create(['author' => $author]);

        // Act
        $data = $this->listArticles($client, ['page' => 2, 'limit' => 20]);

        // Assert
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(5, $data['items']);
        $this->assertSame(2, $data['pagination']['page']);
        $this->assertSame(25, $data['pagination']['total']);
        $this->assertSame(2, $data['pagination']['totalPages']);
    }

    public function testListWithInvalidPageFails(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $this->listArticles($client, ['page' => 0]);

        // Assert
        $this->assertResponseStatusCodeSame(422);
    }

    public function testListWithTooHighLimitFails(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $this->listArticles($client, ['limit' => 1000]);

        // Assert
        $this->assertResponseStatusCodeSame(422);
    }

    public function testAnyoneCanReadPublishedArticle(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $article = ArticleFactory::new()->published()->create(['author' => $author]);

        // Act
        $data = $this->showArticle($client, $article->getSlug());

        // Assert
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame($article->getTitle(), $data['article']['title']);
        $this->assertSame($article->getContent(), $data['article']['content']);
    }

    public function testShowUnknownArticleFails(): void
    {
        // Arrange
        $client = static::createClient();

        // Act
        $this->showArticle($client, 'unknown-article');

        // Assert
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnonymousCannotReadDraft(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $article = ArticleFactory::new()->create(['author' => $author]);

        // Act
        $this->showArticle($client, $article->getSlug());

        // Assert
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAuthorCanReadOwnDraft(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);
        $article = ArticleFactory::new()->create(['author' => $author]);

        // Act
        $this->showArticle($client, $article->getSlug(), $token);

        // Assert
        $this->assertResponseStatusCodeSame(200);
    }

    public function testOtherAuthorCannotReadDraft(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $this->createUser('otherauthor@mail.com', 'motdepasse123*', ['ROLE_AUTHOR']);
        $otherAuthorToken = $this->login($client, ['email' => 'otherauthor@mail.com', 'password' => 'motdepasse123*']);
        $article = ArticleFactory::new()->create(['author' => $author]);

        // Act
        $this->showArticle($client, $article->getSlug(), $otherAuthorToken);

        // Assert
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAdminCanReadAnyDraft(): void
    {
        // Arrange
        $client = static::createClient();
        $author = $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $this->createUser('admin@mail.com', 'motdepasse123*', ['ROLE_ADMIN']);
        $adminToken = $this->login($client, ['email' => 'admin@mail.com', 'password' => 'motdepasse123*']);
        $article = ArticleFactory::new()->create(['author' => $author]);

        // Act
        $this->showArticle($client, $article->getSlug(), $adminToken);

        // Assert
        $this->assertResponseStatusCodeSame(200);
    }

    private function createArticle(KernelBrowser $client, ?string $token, array $payload): array
    {
        $server = ['CONTENT_TYPE' => 'application/json'];

        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $client->request('POST', '/api/articles', server: $server, content: json_encode($payload));

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function validPayload(int $categoryId): array
    {
        return [
            'categoryId' => $categoryId,
            'title' => 'Les nouveautés de Symfony 7.4',
            'content' => 'Beaucoup de corrections de bugs, et plusieurs nouvelles fonctionnalités.',
        ];
    }

    private function publishArticle(KernelBrowser $client, string $token, int $articleId): array
    {
        $client->request(
            'POST',
            "/api/articles/{$articleId}/publish",
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function listArticles(KernelBrowser $client, array $query = []): array
    {
        $client->request(
            'GET',
            '/api/articles',
            $query
        );

        return json_decode($client->getResponse()->getContent(), true);
    }

    private function showArticle(KernelBrowser $client, string $slug, ?string $token = null): array
    {
        $server = [];

        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $client->request(
            'GET',
            "/api/articles/{$slug}",
            server: $server
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
