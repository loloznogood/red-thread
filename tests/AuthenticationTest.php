<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

final class AuthenticationTest extends WebTestCase
{
    public function testLoginReturnsToken(): void
    {
        // Arrange: create a fake HTTP client
        $client = static::createClient();

        $this->createUser('test@mail.com', 'motdepasse123!');

        $data = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);

        $this->assertResponseStatusCodeSame(200);

        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginWithWrongPasswordFails(): void
    {
        // Arrange: create a fake HTTP client
        $client = static::createClient();

        $this->createUser('test@mail.com', 'motdepasse123!');

        $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!!']);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeWithoutTokenFails(): void
    {
        // Arrange: create a fake HTTP client
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/me',
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeWithTokenReturnsUser(): void
    {
        // Arrange: create a fake HTTP client
        $client = static::createClient();

        $this->createUser('test@mail.com', 'motdepasse123!');

        $data = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);

        $this->assertResponseStatusCodeSame(200);

        $client->request(
            'GET',
            '/api/me',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $data['token']],
        );

        $this->assertResponseStatusCodeSame(200);

        $meData = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('test@mail.com', $meData['user']['email']);
    }

    private function createUser(string $email, string $password): User
    {
        $container = static::getContainer();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $entityManager = $container->get(EntityManagerInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setUsername('testeur');
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function login(KernelBrowser $client, array $payload): array
    {
        $client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
