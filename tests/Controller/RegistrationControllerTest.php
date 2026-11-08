<?php

namespace App\Tests\Cont;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class RegistrationControllerTest extends WebTestCase
{
    public function testRegisterCreatesUser(): void
    {
        // Arrange: create a fake HTTP client
        $client = static::createClient();

        // Act: send the registration request
        $data = $this->register($client, [
            'email' => 'test@mail.com',
            'password' => 'motdepasse123',
            'username' => 'testeur',
        ]);

        // Assert: check the response
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('test@mail.com', $data['user']['email']);
        $this->assertArrayNotHasKey('password', $data['user']);

        // Assert: check the database
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'test@mail.com']);

        $this->assertNotNull($user);
        $this->assertNotSame('motdepasse123', $user->getPassword()); // the password is hashed
    }

    public function testRegisterWithShortPasswordFails(): void
    {
        // Arrange: create a fake HTTP client
        $client = static::createClient();

        // Act: send the registration request
        $data = $this->register($client, [
            'email' => 'test@mail.com',
            'password' => 'mot',
            'username' => 'testeur',
        ]);

        // Assert: check the response
        $this->assertResponseStatusCodeSame(422);

        // Check violation
        $this->assertArrayHasKey('violations', $data);
        $this->assertNotEmpty($data['violations']);

        // Access error and check propertyPath
        $firstViolation = $data['violations'][0];

        $this->assertArrayHasKey('propertyPath', $firstViolation);

        // Assert email
        $this->assertSame('password', $firstViolation['propertyPath']);
    }

    public function testRegisterWithExistingEmailFails(): void
    {
        // Arrange: create a fake HTTP client
        $client = static::createClient();

        // Act: send the registration request
        $this->register($client, [
            'email' => 'test@mail.com',
            'password' => 'motdepasse123',
            'username' => 'testeur',
        ]);

        // Assert: check the response
        $this->assertResponseStatusCodeSame(201);

        // Act: send the registration request
        $data = $this->register($client, [
            'email' => 'test@mail.com',
            'password' => 'motdepasse123',
            'username' => 'jean',
        ]);

        // Assert: check the response
        $this->assertResponseStatusCodeSame(422);

        // Check violation
        $this->assertArrayHasKey('violations', $data);
        $this->assertNotEmpty($data['violations']);

        // Access error and check propertyPath
        $firstViolation = $data['violations'][0];
        $this->assertArrayHasKey('propertyPath', $firstViolation);

        // Assert email
        $this->assertSame('email', $firstViolation['propertyPath']);
    }

    public function testRegisterWithExistingUsernameFails(): void
    {
        // Arrange: create a fake HTTP client
        $client = static::createClient();

        // Act: send the registration request
        $this->register($client, [
            'email' => 'test@mail.com',
            'password' => 'motdepasse123',
            'username' => 'testeur',
        ]);

        // Assert: check the response
        $this->assertResponseStatusCodeSame(201);

        // Act: send the registration request
        $data = $this->register($client, [
            'email' => 'john@mail.com',
            'password' => 'motdepasse123',
            'username' => 'testeur',
        ]);

        // Assert: check the response
        $this->assertResponseStatusCodeSame(422);

        // Check violation
        $this->assertArrayHasKey('violations', $data);
        $this->assertNotEmpty($data['violations']);

        // Access error and check propertyPath
        $firstViolation = $data['violations'][0];
        $this->assertArrayHasKey('propertyPath', $firstViolation);

        // Assert username
        $this->assertSame('username', $firstViolation['propertyPath']);
    }

    private function register(KernelBrowser $client, array $payload): array
    {
        $client->request(
            'POST',
            '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );

        return json_decode($client->getResponse()->getContent(), true);
    }
}
