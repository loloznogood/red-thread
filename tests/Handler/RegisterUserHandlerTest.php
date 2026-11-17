<?php

namespace App\Tests\Handler;

use App\Dto\RegisterUserDto;
use App\Entity\User;
use App\Handler\RegisterUserHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegisterUserHandlerTest extends TestCase
{
    public function testHandleCreatesUserWithHashedPassword(): void
    {
        // Arrange: create a fake hasher and entitymanager
        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hashedPassword = 'hashed-password';
        $hasher->method('hashPassword')->willReturn($hashedPassword);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));
        $entityManager->expects($this->once())->method('flush');
        $registerUserHandler = new RegisterUserHandler($hasher, $entityManager);
        $email = 'test@mail.com';
        $password = 'motdepasse123!';
        $username = 'testeur';
        $registerUserDto = new RegisterUserDto(email: $email, password: $password, username: $username);

        // Act: call the handle method
        $user = $registerUserHandler->handle($registerUserDto);

        // Assert: user created
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($hashedPassword, $user->getPassword());
        $this->assertNotSame($password, $user->getPassword());
        $this->assertSame($username, $user->getUsername());
    }
}
