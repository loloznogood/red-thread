<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Factory\CategoryFactory;
use App\Tests\AppWebTestCase;

final class ArticleControllerTest extends AppWebTestCase
{
    public function testAuthorCanCreateArticle(): void
    {
        // Arrange
        $client = static::createClient();
        $this->createUser('test@mail.com', 'motdepasse123!', ['ROLE_AUTHOR']);
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);
        $category = CategoryFactory::createOne();

        // Act
        $data = $this->createArticle($client, $token, $this->validPayload($category->getId()));

        // Assert
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('testeur', $data['article']['author']);
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
}
