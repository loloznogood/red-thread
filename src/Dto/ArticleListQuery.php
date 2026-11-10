<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ArticleListQuery
{
    public function __construct(
        #[Assert\Positive(message: 'Le numéro de page doit être un nombre positif.')]
        public readonly int $page = 1,

        #[Assert\Range(
            min: 1,
            max: 50,
            notInRangeMessage: "Le nombre d'articles par page doit être compris entre {{ min }} et {{ max }}."
        )]
        public readonly int $limit = 20,
    ) {}
}
