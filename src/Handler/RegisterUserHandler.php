<?php

namespace App\Handler;

use App\Dto\RegisterUserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    ) {}

    public function handle(RegisterUserDto $registerUserDto): User
    {
        $user = new User();
        $user->setEmail($registerUserDto->email);
        $user->setUsername($registerUserDto->username);

        // Hash the password
        $user->setPassword($this->passwordHasher->hashPassword($user, $registerUserDto->password));

        // Save in database
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
