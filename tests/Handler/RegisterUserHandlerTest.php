<?php

namespace App\Tests\Handler;

use App\Dto\RegisterUserDto;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Handler\RegisterUserHandler;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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

        $email = 'test@mail.com';
        $password = 'motdepasse123!';
        $username = 'testeur';

        $registerUserHandler = new RegisterUserHandler($hasher, $entityManager);
        $registerUserDto = new RegisterUserDto(email: $email, password: $password, username: $username);

        // Act: create the user
        $user = $registerUserHandler->handle($registerUserDto);

        // Assert: user created
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($hashedPassword, $user->getPassword());
        $this->assertNotSame($password, $user->getPassword());
        $this->assertSame($username, $user->getUsername());
    }

    public function testHandleThrowsWhenUserAlreadyExists(): void
    {
        // Arrange: flush() fails because the email or username is already taken
        $hasher = $this->createStub(UserPasswordHasherInterface::class);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('flush')
            ->willThrowException($this->createStub(UniqueConstraintViolationException::class));

        $handler = new RegisterUserHandler($hasher, $entityManager);
        $dto = new RegisterUserDto(email: 'test@mail.com', password: 'motdepasse123!', username: 'testeur');

        // Assert (before the Act: the exception stops the test)
        $this->expectException(UserAlreadyExistsException::class);

        // Act
        $handler->handle($dto);
    }
}
