<?php

namespace App\Dto;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\User;

#[UniqueEntity(
    fields: ['email'],
    entityClass: User::class,
    message: 'Cette adresse e-mail est déjà utilisée.',
    errorPath: 'email',
)]
#[UniqueEntity(
    fields: ['username'],
    entityClass: User::class,
    message: 'Ce pseudonyme est déjà utilisé.',
    errorPath: 'username',
)]

class RegisterUserDto
{
    public function __construct(
        #[Assert\NotBlank(message: "L'email est obligatoire.")]
        #[Assert\Email(message: "L'email « {{ value }} » n'est pas un email valide.")]
        #[Assert\Length(max: 180, maxMessage: "L'email ne doit pas dépasser {{ limit }} caractères.")]
        public readonly ?string $email = null,

        #[Assert\NotBlank(message: "Le mot de passe est obligatoire.")]
        #[Assert\Length(
            min: 12,
            max: 4096,
            minMessage: "Le mot de passe doit faire au moins {{ limit }} caractères.",
            maxMessage: "Le mot de passe ne doit pas dépasser {{ limit }} caractères.",
        )]
        public readonly ?string $password = null,

        #[Assert\NotBlank(message: "Le nom d'utilisateur est obligatoire.")]
        #[Assert\Length(
            min: 3,
            max: 50,
            minMessage: "Le nom d'utilisateur doit faire au moins {{ limit }} caractères.",
            maxMessage: "Le nom d'utilisateur ne doit pas dépasser {{ limit }} caractères.",
        )]
        public readonly ?string $username = null,
    ) {}
}
