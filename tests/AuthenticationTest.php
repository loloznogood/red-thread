<?php

namespace App\Tests\Controller;

use App\Tests\AppWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class AuthenticationTest extends AppWebTestCase
{
    public function testLoginReturnsToken(): void
    {
        $client = static::createClient();
        $this->createUser('test@mail.com', 'motdepasse123!');

        $data = $this->sendLoginRequest($client, 'test@mail.com', 'motdepasse123!');

        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginWithWrongPasswordFails(): void
    {
        $client = static::createClient();
        $this->createUser('test@mail.com', 'motdepasse123!');

        $this->sendLoginRequest($client, 'test@mail.com', 'mauvais-mot-de-passe');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeWithoutTokenFails(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeWithTokenReturnsUser(): void
    {
        $client = static::createClient();
        $this->createUser('test@mail.com', 'motdepasse123!');
        $token = $this->login($client, ['email' => 'test@mail.com', 'password' => 'motdepasse123!']);

        $client->request('GET', '/api/me', server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('test@mail.com', $data['user']['email']);
    }

    private function sendLoginRequest(KernelBrowser $client, string $email, string $password): array
    {
        $client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => $password]),
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
