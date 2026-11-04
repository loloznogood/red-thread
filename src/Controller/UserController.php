<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

final class UserController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'], format: 'json')]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json([
            'message' => 'You are logged!',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'roles' => $user->getRoles()
            ]
        ]);
    }
}
