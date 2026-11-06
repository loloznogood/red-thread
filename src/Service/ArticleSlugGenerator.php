<?php

namespace App\Service;

use App\Repository\ArticleRepository;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ArticleSlugGenerator
{
    public function __construct(
        private SluggerInterface $slugger,
        private ArticleRepository $articleRepository,
    ) {}

    public function generate(string $title): string
    {
        $baseSlug = $this->slugger->slug($title)->lower()->toString();
        $slug = $baseSlug;
        $suffix = 2;

        // While another article already uses this slug, try with a suffix
        while ($this->articleRepository->count(['slug' => $slug]) > 0) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
