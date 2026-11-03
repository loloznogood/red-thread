<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Dto\RegisterUserDto;
use App\Entity\User;

final class RegistrationController extends AbstractController
{
    #[Route('/api/register', methods: ['POST'], name: 'api_register', format: 'json')]
    public function register(
        #[MapRequestPayload] RegisterUserDTO $registerUserDTO,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = new User();
        $user->setEmail($registerUserDTO->email);
        $user->setUsername($registerUserDTO->username);

        // Hash the password
        $user->setPassword($passwordHasher->hashPassword($user, $registerUserDTO->password));

        // Save in database
        $entityManager->persist($user);
        $entityManager->flush();

        // 201 HTTP Response (Created)
        return $this->json([
            'message' => 'User registered!',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail()
            ]
        ], Response::HTTP_CREATED);
    }
}
