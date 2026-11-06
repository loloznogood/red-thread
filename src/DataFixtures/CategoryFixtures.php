<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Factory\CategoryFactory;

class CategoryFixtures extends Fixture
{
    public function __construct(
        private SluggerInterface $slugger,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $names = ['Développement', 'Matériel', 'Jeux vidéo', 'Cybersécurité'];

        foreach ($names as $name) {
            CategoryFactory::createOne([
                'name' => $name,
                'slug' => $this->slugger->slug($name)->lower(),
            ]);
        }
    }
}
