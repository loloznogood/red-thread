<?php

namespace App\Exception;

use DomainException;

final class ArticleAlreadyPublishedException extends DomainException
{

    public function __construct(int $articleId)
    {
        parent::__construct(
            sprintf("L'article %d est déjà publié.", $articleId)
        );
    }
}
