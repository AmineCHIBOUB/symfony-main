<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Music;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i=1; $i <= 10; $i++) {
            $music = new Music();
            $music->setName("Music" . $i);
            $music->setUrl("url" . $i);
            $music->setAuteur($faker->name());
            
            $manager->persist($music);
        }

        $manager->flush();
    }
}