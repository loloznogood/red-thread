<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Repository\CategoryRepository;

final class CategoryExistsValidator extends ConstraintValidator
{
    public function __construct(
        private CategoryRepository $categoryRepository,
    ) {}

    /**
     * @param CategoryExists $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        if ($this->categoryRepository->count(['id' => $value]) === 0) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', (string) $value)
                ->addViolation()
            ;
        }
    }
}
