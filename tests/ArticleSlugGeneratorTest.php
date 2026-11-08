<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Repository\ArticleRepository;
use App\Service\ArticleSlugGenerator;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ArticleSlugGeneratorTest extends TestCase
{
    public function testGenerateSlugFromTitle(): void
    {
        // Arrange: a fake repository that says "this slug is free"
        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository->method('count')->willReturn(0);

        $generator = new ArticleSlugGenerator(new AsciiSlugger(), $articleRepository);

        // Act
        $slug = $generator->generate('Les nouveautés de Symfony 7.4');

        // Assert
        $this->assertSame('les-nouveautes-de-symfony-7-4', $slug);
    }

    public function testGenerateSlugAddsSuffixWhenTaken(): void
    {
        // Arrange: a fake repository that says "this slug is free"
        $articleRepository = $this->createMock(ArticleRepository::class);
        $articleRepository->method('count')->willReturn(1, 0);

        $generator = new ArticleSlugGenerator(new AsciiSlugger(), $articleRepository);

        // Act
        $slug = $generator->generate('Les nouveautés de Symfony 7.4');

        // Assert
        $this->assertSame('les-nouveautes-de-symfony-7-4-2', $slug);
    }
}
