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
}
