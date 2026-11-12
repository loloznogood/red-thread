<?php

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Bundle\SecurityBundle\Security;
use App\Enum\ArticleStatus;
use App\Entity\Article;
use App\Entity\User;

final class ArticleVoter extends Voter
{
    public const PUBLISH = 'ARTICLE_PUBLISH';
    public const VIEW = 'ARTICLE_VIEW';

    public function __construct(
        private Security $security,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::PUBLISH, self::VIEW], true)
            && $subject instanceof Article;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Article $subject */
        $article = $subject;

        // A published article can be read by everyone, even anonymous visitors
        if ($attribute === self::VIEW && $article->getStatus() === ArticleStatus::Published) {
            return true;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // Otherwise, only the author of this article
        return $article->getAuthor()->getId() === $user->getId();
    }
}
