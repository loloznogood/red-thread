<?php

namespace App\Exception;

use DomainException;

final class UserAlreadyExistsException extends DomainException
{

    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Un compte existe déjà avec cet email ou ce pseudo.', 0, $previous);
    }
}
