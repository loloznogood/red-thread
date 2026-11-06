<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\CategoryExists;

class CreateArticleDto
{
    public function __construct(
        #[Assert\NotBlank(message: "La catégorie est obligatoire.")]
        #[Assert\Positive(message: "L'identifiant de la catégorie doit être un nombre positif.")]
        #[CategoryExists]
        public readonly ?int $categoryId = null,

        #[Assert\NotBlank(message: "Le titre est obligatoire.")]
        #[Assert\Length(
            min: 5,
            max: 255,
            minMessage: "Le titre doit faire au moins {{ limit }} caractères.",
            maxMessage: "Le titre ne doit pas dépasser {{ limit }} caractères.",
        )]
        public readonly ?string $title = null,

        #[Assert\Length(
            min: 5,
            max: 500,
            minMessage: "La description doit faire au moins {{ limit }} caractères.",
            maxMessage: "La description ne doit pas dépasser {{ limit }} caractères.",
        )]
        public readonly ?string $description = null,

        #[Assert\NotBlank(message: "Le contenu est obligatoire.")]
        #[Assert\Length(
            min: 50,
            max: 50000,
            minMessage: "Le contenu doit faire au moins {{ limit }} caractères.",
            maxMessage: "Le contenu ne doit pas dépasser {{ limit }} caractères.",
        )]
        public readonly ?string $content = null,
    ) {}
}
